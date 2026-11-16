#!/usr/bin/env bash
# Pengukuran waktu respons nyata terhadap server MySQL yang sedang berjalan.
# Menghasilkan CSV mentah (5 percobaan per halaman) dan pemeriksaan HTTP keamanan.
set -uo pipefail

BASE="${BASE:-http://127.0.0.1:8000}"
OUT_DIR="${OUT_DIR:-docs/sprint-3/pengumpulan/evidence}"
COOKIE_DIR="storage/app/sprint3-measure"
RAW_CSV="$OUT_DIR/performance_raw.csv"
SECURITY_TXT="$OUT_DIR/security_http_checks.txt"

mkdir -p "$OUT_DIR" "$COOKIE_DIR"
ADMIN_JAR="$COOKIE_DIR/admin.cookies"
OWNER_JAR="$COOKIE_DIR/owner.cookies"
GUEST_JAR="$COOKIE_DIR/guest.cookies"
rm -f "$ADMIN_JAR" "$OWNER_JAR" "$GUEST_JAR"

login() {
    local jar="$1" email="$2" password="$3" role="$4"
    local token
    token=$(curl -s -c "$jar" "$BASE/login" | grep -o 'name="_token" value="[^"]*"' | head -1 | sed 's/.*value="//;s/"$//')
    if [ -z "$token" ]; then
        echo "Gagal mengambil token CSRF untuk $email" >&2
        return 1
    fi
    curl -s -o /dev/null -b "$jar" -c "$jar" -X POST "$BASE/login" \
        --data-urlencode "_token=$token" \
        --data-urlencode "email=$email" \
        --data-urlencode "password=$password" \
        --data-urlencode "role=$role"
}

ms() {
    awk -v v="$1" 'BEGIN{printf "%.1f", v*1000}'
}

measure() {
    local label="$1" path="$2" jar="$3"
    local i code ttfb total
    for i in 1 2 3 4 5; do
        read -r code ttfb total < <(curl -s -o /dev/null -b "$jar" -w '%{http_code} %{time_starttransfer} %{time_total}' "$BASE$path")
        printf '%s|%s|%d|%s|%s|%s\n' "$label" "$path" "$i" "$code" "$(ms "$ttfb")" "$(ms "$total")" >> "$RAW_CSV"
    done
}

login "$ADMIN_JAR" "admin@fixpoint.test" "Admin12345" "admin" || exit 1
login "$OWNER_JAR" "pemilik@fixpoint.test" "Pemilik12345" "pemilik" || exit 1

echo "label|path|run|http_code|ttfb_ms|total_ms" > "$RAW_CSV"

measure "Ketersediaan Jadwal" "/ketersediaan-jadwal" "$GUEST_JAR"
measure "Halaman Booking" "/booking" "$GUEST_JAR"
measure "Cek Status" "/cek-status" "$GUEST_JAR"
measure "Dashboard Admin" "/dashboard-admin" "$ADMIN_JAR"
measure "Kelola Jadwal Servis" "/admin/jadwal-servis" "$ADMIN_JAR"
measure "Riwayat Servis (filter)" "/admin/riwayat-servis?from=2026-11-01&to=2026-11-30" "$ADMIN_JAR"
measure "Data Customer" "/admin/data-customer" "$ADMIN_JAR"
measure "Laporan Pemasukan (Pemilik)" "/pemilik/laporan-pemasukan" "$OWNER_JAR"

{
    echo "Pemeriksaan HTTP keamanan pada server berjalan"
    echo "Waktu pemeriksaan: $(date '+%Y-%m-%d %H:%M:%S %Z')"
    echo "Base URL: $BASE"
    echo

    echo "1) Guest membuka /dashboard-admin"
    curl -s -o /dev/null -w '   HTTP %{http_code} -> %{redirect_url}\n' "$BASE/dashboard-admin"

    echo "2) Pemilik Usaha membuka /admin/jadwal-servis (harus 403)"
    curl -s -o /dev/null -b "$OWNER_JAR" -w '   HTTP %{http_code}\n' "$BASE/admin/jadwal-servis"

    echo "3) Pemilik Usaha mengirim DELETE jadwal tanpa token CSRF (ditolak di lapisan CSRF)"
    curl -s -o /dev/null -b "$OWNER_JAR" -X DELETE -w '   HTTP %{http_code}\n' "$BASE/admin/jadwal-servis/1"

    # Dengan token CSRF valid, permintaan melewati lapisan CSRF dan harus
    # ditolak oleh middleware role (403).
    owner_token=$(curl -s -b "$OWNER_JAR" -c "$OWNER_JAR" "$BASE/pemilik/riwayat-servis" | grep -o 'name="_token" value="[^"]*"' | head -1 | sed 's/.*value="//;s/"$//')
    echo "3b) Pemilik Usaha mengirim DELETE jadwal dengan token CSRF valid (harus 403 karena role)"
    curl -s -o /dev/null -b "$OWNER_JAR" -X DELETE -H "X-CSRF-TOKEN: $owner_token" -w '   HTTP %{http_code}\n' "$BASE/admin/jadwal-servis/1"

    echo "4) Admin mengirim POST pembuatan jadwal tanpa token CSRF (harus 419)"
    curl -s -o /dev/null -b "$ADMIN_JAR" -X POST -w '   HTTP %{http_code}\n' \
        --data-urlencode "schedule_date=2026-12-01" \
        --data-urlencode "start_time=09:00" \
        --data-urlencode "end_time=10:00" \
        --data-urlencode "max_booking=2" \
        "$BASE/admin/jadwal-servis"

    echo "5) Admin mengirim PATCH status jadwal tanpa token CSRF (harus 419)"
    curl -s -o /dev/null -b "$ADMIN_JAR" -X PATCH -w '   HTTP %{http_code}\n' "$BASE/admin/jadwal-servis/1/status"

    echo "6) Upload bukti dengan file PHP (harus redirect dengan error validasi, bukan 500)"
    echo "   Dibuktikan otomatis oleh tests/Feature/SecurityVerificationTest.php (SEC-04)."

    echo
    echo "Lingkungan:"
    php -v | head -1 | sed 's/^/   /'
    php artisan --version | sed 's/^/   Laravel /'
    php artisan tinker --execute="echo '   Database: '.DB::connection()->getDatabaseName().' ('.DB::connection()->getDriverName().')'.PHP_EOL;" 2>/dev/null | tail -1
    echo "   Server: PHP built-in development server (php artisan serve), 1 worker"
} | tee "$SECURITY_TXT"

echo "CSV pengukuran: $RAW_CSV"
