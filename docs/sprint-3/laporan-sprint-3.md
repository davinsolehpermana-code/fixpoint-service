# Laporan Sprint 3 — FixPoint Service

Tanggal pengerjaan: 6 November 2026
Lingkungan: Laravel 13.36, PHP 8.4.26 (XAMPP), MySQL `fixpoint_service`, Blade, Windows 11.

Dokumen ini merangkum audit Sprint 3 (S3-01 … S3-07), hasil implementasi laporan
riwayat servis & pemasukan, serta pekerjaan yang belum dapat diselesaikan.

---

## 1. Ringkasan Status Tugas SOP

| Kode | Tugas | Status | File yang Dibuat/Diubah | Bukti Pengujian |
|---|---|---|---|---|
| S3-01 | Authentication & Authorization | Selesai | `routes/web.php`, `resources/views/admin/partials/sidebar.blade.php`, `tests/Feature/Sprint3AcceptanceTest.php` | FT-08 … FT-12, FT-27 (22 test otorisasi/laporan PASS) |
| S3-02 | Global Error Handling | Selesai | `resources/views/errors/{layout,403,404,419,500,503}.blade.php`, `bootstrap/app.php`, `.env`, `.env.example` | FT-28, FT-29; `APP_DEBUG=false` |
| S3-03 | Functional Testing | Selesai (sebagian bukti visual belum) | `tests/Feature/Sprint3AcceptanceTest.php`, `docs/sprint-3/Functional_Testing_Report.xlsx` | `php artisan test` → 30 passed (129 assertions) |
| S3-04 | Bug Fixing & Issue Resolution | Selesai | `tests/Feature/AdminBookingUpdateTest.php`, `tests/Feature/BookingPaymentMethodTest.php`, `tests/Feature/ExampleTest.php`, `app/Http/Controllers/ReportController.php`, `docs/sprint-3/Issue_Tracking_Log.xlsx` | 8 isu dicatat, seluruhnya Closed |
| S3-05 | Responsive & Mobile Quality Check | Sebagian (audit CSS, verifikasi visual belum) | `resources/views/admin/riwayat-servis.blade.php`, `resources/views/admin/laporan-pemasukan.blade.php` | Lihat bagian E |
| S3-06 | Basic Security Implementation | Selesai | Audit kode (tanpa perubahan besar; validasi upload & CSRF sudah memadai) | FT-30, inspeksi kode |
| S3-07 | Performance & Usability Refinement | Sebagian | `database/migrations/2026_11_06_000000_add_reporting_indexes.php` | Migration berjalan pada database uji; belum diterapkan ke MySQL lokal (server mati saat pengujian) |

---

## 2. Hasil Audit Implementasi yang Sudah Ada

### Sudah ada dan sesuai (dipertahankan)
- Autentikasi email/password dengan pilihan role (`admin` / `pemilik`) dan redirect per role (`AuthController`).
- Middleware `role` (`App\Http\Middleware\RoleMiddleware`) yang memeriksa role di sisi server dan `abort(403)`.
- Grup route admin (`auth` + `role:ADMIN`) dan pemilik (`auth` + `role:PEMILIK_USAHA`).
- Pelanggan tanpa akun, memakai Service ID (`/cek-status`, `/cek-status/result`).
- Booking dengan penguncian jadwal (`lockForUpdate`) dan pengurangan kuota atomik.
- Pembayaran manual CASH/QRIS, verifikasi bukti QRIS, struk pembayaran.
- Riwayat servis admin (sebelumnya tanpa filter tanggal).
- Validasi upload bukti pembayaran: `image`, `mimes:jpeg,png,jpg,webp`, `max:2048`, disimpan via `store()` dengan nama acak.
- Seluruh form POST/PUT/PATCH memakai `@csrf`; tidak ada pemakaian `{!! !!}` di Blade (audit grep).

### Sudah ada tetapi belum lengkap (dilengkapi)
- Riwayat servis: belum ada filter rentang tanggal, tombol cetak/PDF/Excel, dan ringkasan jumlah data.
- Belum ada laporan pemasukan sama sekali.
- Belum ada halaman error kustom (403/404/500/419/503).
- `APP_DEBUG` masih `true`.
- Indeks kolom filter laporan belum ada.
- Test lama gagal karena payload tidak sesuai kontrak validasi saat ini.

### Belum ada (dibuat)
- `ReportController` (halaman + PDF + Excel untuk riwayat servis dan pemasukan).
- `App\Support\SimpleXlsx` (penulis XLSX tanpa dependensi tambahan).
- View export PDF (2 berkas) dan halaman laporan pemasukan.
- Route laporan untuk Admin dan Pemilik Usaha, termasuk route ekspor.
- Migration indeks pelaporan.

### Sudah ada tetapi bermasalah (diperbaiki)
- Test suite gagal (3 test) → diperbaiki, sekarang 30 test PASS.
- Query laporan sempat memakai kolom yang salah (`bookings.complaint` → `devices.complaint`), diperbaiki sebelum rilis.

### Duplikasi yang sengaja dihindari
- Tidak membuat tabel/model/controller baru untuk pemasukan: laporan memakai tabel `payments` yang ada.
- Tidak membuat ulang halaman riwayat servis: route lama `admin.service-history` dipertahankan namanya, hanya target controller dipindah ke `ReportController`.
- Tidak membuat sistem akun pelanggan.

---

## 3. Library PDF & Excel

- **PDF**: `barryvdh/laravel-dompdf` v3.1.2 (sudah ditambahkan pada `composer.json`). Alasan: library stabil, tanpa ekstensi PHP tambahan, mendukung layout A4 landscape dan CSS dasar yang dipakai laporan.
- **Excel**: **tanpa library baru**. `phpoffice/phpspreadsheet` (dan `maatwebsite/excel`) tidak dapat dipasang karena `ext-gd` tidak aktif pada XAMPP ini. Karena itu `app/Support/SimpleXlsx.php` menulis berkas `.xlsx` asli (paket OOXML + ZipArchive, ext-zip tersedia). Hasilnya bukan HTML yang di-rename, melainkan ZIP berisi `[Content_Types].xml`, `xl/workbook.xml`, `xl/styles.xml`, dan `xl/worksheets/sheet1.xml`.
- Dampak: tidak ada dependensi berat/tambahan; ekspor tetap menghasilkan `.xlsx` yang dapat dibuka Excel/LibreOffice.

Constraint dompdf sudah dirapikan menjadi `^3.1` pada `composer.json` dan `composer validate` menyatakan berkas valid.

---

## A. Laporan Riwayat Servis

**Status: selesai dan teruji.**

- Halaman: `/admin/riwayat-servis` (Admin) dan `/pemilik/riwayat-servis` (Pemilik Usaha, read-only).
- Filter: `from`, `to` (inklusif), pencarian `q`, tombol **Terapkan Filter** dan **Reset Filter**.
- Dasar filter: `service_records.completed_at` (tanggal servis selesai) — sesuai definisi riwayat saat ini (hanya `service_status = COMPLETED`).
- Jika rentang diisi terbalik (from > to), controller menukar otomatis.
- Ringkasan: jumlah servis selesai + total biaya akhir pada periode tersebut.
- Tombol: **Cetak** (PDF inline, `?print=1`), **Download PDF**, **Export Excel** — semuanya membawa filter aktif.
- Isi laporan: judul “Laporan Riwayat Servis FixPoint Service”, periode, Service ID, nama pelanggan, nomor telepon, perangkat, layanan, keluhan awal, tanggal booking, status servis, diagnosis, estimasi, biaya akhir, metode & status pembayaran.
- Format Rupiah (`Rp 200.000`) dan tanggal Indonesia (`translatedFormat('d F Y')`).
- Nama berkas: `laporan-riwayat-servis-04-november-2026-07-november-2026.pdf` / `.xlsx` (otomatis `semua` bila filter kosong).

Bukti: FT-20, FT-21, FT-22, FT-23. Berkas uji nyata juga dihasilkan saat verifikasi manual (`%PDF-1.7`, XLSX berisi baris data sesuai filter).

## B. Laporan Pemasukan

**Status: selesai dan teruji.**

- Halaman: `/admin/laporan-pemasukan` dan `/pemilik/laporan-pemasukan`.
- Sumber data: tabel `payments` (tidak ada tabel/keuangan baru).
- Perhitungan: hanya `payment_status = 'PAID'`, berdasarkan `payment_date` (tanggal pembayaran tercatat), bukan tanggal booking. UNPAID/WAITING_VERIFICATION tidak dihitung.
- Filter: `from`, `to`, `method` (Semua / CASH / QRIS), tombol Terapkan & Reset.
- Ringkasan: total pemasukan, total QRIS (+jumlah transaksi), total Cash (+jumlah transaksi), jumlah transaksi berhasil.
- Tabel: No, Tanggal Pembayaran, Service ID, Pelanggan, Perangkat, Metode, Jumlah, Status.
- Ekspor PDF/Excel mengikuti filter tanggal **dan** metode yang aktif; PDF memuat total keseluruhan, per metode, jumlah transaksi, serta tanggal cetak.

Bukti: FT-24, FT-25, FT-26, FT-27.

## C. Database

- Tabel yang dipakai (tidak ada tabel baru): `users`, `customers`, `devices`, `services`, `schedules`, `bookings`, `service_records`, `payments`.
- Migration baru (aditif, aman, tidak menghapus data): `database/migrations/2026_11_06_000000_add_reporting_indexes.php`
  - `payments(payment_status, payment_date)`, `payments(payment_method)`
  - `service_records(service_status, completed_at)`
  - `bookings(status)`, `bookings(booking_date)`
  - `schedules(schedule_date, status)`
  - `customers(phone_number)`
  - Migration memeriksa indeks yang sudah ada (termasuk indeks otomatis dari foreign key) agar tidak membuat indeks duplikat.
- Tidak ada migration duplikat; tidak ada `migrate:fresh`.
- **Catatan**: migration ini sudah divalidasi pada database uji (SQLite in-memory) lewat `RefreshDatabase`, tetapi belum dijalankan pada MySQL lokal karena server MySQL XAMPP tidak aktif saat pengujian. Jalankan `php artisan migrate` saat MySQL menyala.

## D. File yang Dikerjakan

### File baru
1. `app/Http/Controllers/ReportController.php`
2. `app/Support/SimpleXlsx.php`
3. `resources/views/admin/laporan-pemasukan.blade.php`
4. `resources/views/admin/exports/riwayat-servis-pdf.blade.php`
5. `resources/views/admin/exports/laporan-pemasukan-pdf.blade.php`
6. `resources/views/errors/layout.blade.php`
7. `resources/views/errors/403.blade.php`
8. `resources/views/errors/404.blade.php`
9. `resources/views/errors/419.blade.php`
10. `resources/views/errors/500.blade.php`
11. `resources/views/errors/503.blade.php`
12. `database/migrations/2026_11_06_000000_add_reporting_indexes.php`
13. `tests/Feature/Sprint3AcceptanceTest.php`
14. `docs/sprint-3/laporan-sprint-3.md`
15. `docs/sprint-3/Functional_Testing_Report.xlsx`
16. `docs/sprint-3/Issue_Tracking_Log.xlsx`

### File diperbarui
1. `routes/web.php` — route laporan admin & pemilik (halaman, PDF, Excel); `admin.service-history` tetap ada.
2. `app/Http/Controllers/AdminPanelController.php` — method `serviceHistory()` lama dihapus (dipindah ke `ReportController`).
3. `resources/views/admin/riwayat-servis.blade.php` — filter rentang tanggal, ringkasan, tombol cetak/PDF/Excel.
4. `resources/views/admin/partials/sidebar.blade.php` — menu “Laporan Pemasukan”, menu menyesuaikan role (Pemilik tidak melihat menu admin).
5. `resources/views/layouts/admin.blade.php` — tidak diubah (dipakai ulang oleh Pemilik Usaha untuk halaman laporan).
6. `bootstrap/app.php` — render khusus `QueryException` (tanpa detail teknis saat debug mati).
7. `.env`, `.env.example` — `APP_DEBUG=false`.
8. `composer.json`, `composer.lock` — `barryvdh/laravel-dompdf`.
9. `tests/Feature/AdminBookingUpdateTest.php`, `BookingPaymentMethodTest.php`, `ExampleTest.php` — disesuaikan dengan kontrak validasi & skema saat ini.

### File tidak diubah karena sudah sesuai
- `app/Http/Middleware/RoleMiddleware.php` (otorisasi server-side sudah benar).
- `app/Http/Controllers/AuthController.php`, `BookingController.php`, `StatusController.php`, `PaymentProofController.php`.
- `resources/views/layouts/app.blade.php`, halaman publik pelanggan, dashboard Pemilik (selain tautan laporan di panel admin/pemilik).
- Struktur tabel utama (tanpa perubahan kolom).

## E. Pekerjaan yang Belum Selesai

1. **Screenshot evidence (S3-01, S3-02, S3-05)** belum diambil. Penyebab: MySQL XAMPP tidak aktif saat sesi pengujian sehingga halaman dinamis tidak dapat dirender; halaman admin/pemilik juga butuh login manual di browser. Semua pengujian otorisasi & error handling sudah dibuktikan lewat feature test (`assertForbidden`, `assertNotFound`, `assertSee`). Langkah: nyalakan MySQL, jalankan `php artisan serve`, login `admin@fixpoint.test` / `Admin12345` (sesuai seeder), lalu ambil screenshot 390/768/1280 px.
2. **Verifikasi visual responsif (S3-05)**: hanya audit CSS. Perbaikan yang sudah dilakukan: grid filter laporan memakai `repeat(auto-fit, minmax(160px, 1fr))`; tabel laporan berada di `.admin-table-wrap` yang sudah `overflow-x:auto`; sidebar punya toggle di ≤800 px. Masih perlu dicek mata pada perangkat nyata.
3. **`php artisan migrate` pada MySQL** untuk migration indeks baru.
4. **Dokumentasi audit pemodelan** (`docs/analisis-pemodelan-fixpoint/08-kesimpulan-validasi.md`) belum ditulis; tidak termasuk lingkup fitur ini.

---

## Cara Menjalankan & Menguji

```bash
# 1. Pastikan MySQL XAMPP aktif
php artisan migrate            # menerapkan migration indeks pelaporan
php artisan serve

# 2. Login
#    Admin  : admin@fixpoint.test  / Admin12345 (sesuai DatabaseSeeder)
#    Pemilik: pemilik@fixpoint.test / Pemilik12345

# 3. Halaman laporan
#    /admin/riwayat-servis?from=2026-11-04&to=2026-11-07
#    /admin/riwayat-servis/pdf?from=2026-11-04&to=2026-11-07
#    /admin/riwayat-servis/excel?from=2026-11-04&to=2026-11-07
#    /admin/laporan-pemasukan?from=2026-11-04&to=2026-11-07&method=QRIS

# 4. Seluruh pengujian otomatis
php artisan test
```

Hasil terakhir: **30 passed (129 assertions)**, tanpa kegagalan.
