# API DOCUMENTATION - FIXPOINT SERVICE

**Proyek:** FixPoint Service - Sistem Informasi Booking dan Manajemen Jasa Servis Laptop dan PC
**Tahap:** S4-05 API Documentation
**Tanggal:** 15 November 2026
**Versi aplikasi:** Laravel Framework 13.36.0, PHP 8.3+

---

## 1. Kesimpulan Audit Arsitektur

Pernyataan yang jujur dan perlu dibaca lebih dahulu:

**FixPoint Service adalah aplikasi Laravel Web Monolithic berbasis Blade, bukan aplikasi REST API.**

Bukti dari repository:

| Pemeriksaan | Hasil |
| --- | --- |
| Jumlah route aplikasi | 42 route web (`routes/web.php`) dan 1 route JSON (`routes/api.php`); `php artisan route:list` menampilkan 48 route termasuk 5 route bawaan framework |
| Cara antarmuka berkomunikasi | Submit form HTML biasa (POST/PUT/PATCH) dengan `@csrf`, diikuti redirect dan session flash |
| Autentikasi | Session guard Laravel (`Auth::attempt`), bukan token API |
| Sanctum | Terpasang dari skeleton, tetapi tidak ada token yang diterbitkan atau dipakai |
| Penggunaan `fetch`/AJAX di view | Tidak ada |
| Controller API | Hanya `Api\ApiBookingController@store` yang benar-benar diimplementasikan |

Karena itu dokumen ini dibuat sebagai **Data Contract dan Controller Action Reference** untuk aplikasi web monolitik, sesuai alternatif yang diperbolehkan pada SOP. Satu endpoint JSON yang benar-benar ada (`POST /api/bookings`) juga didokumentasikan lengkap beserta contoh respons, dan Postman Collection yang disertakan hanya memuat endpoint tersebut - tidak ada endpoint rekaan.

> Catatan Sprint 4: tiga route lama `GET /api/schedules`, `GET /api/bookings/{code}`, dan `PUT /api/bookings/{code}/status` dihapus dari `routes/api.php` karena menunjuk method controller yang tidak ada dan menghasilkan HTTP 500. Rinciannya ada pada `docs/01-release/BUG_FIXING_LOG.md` tiket S4-01-02.

## 2. Konvensi Umum

| Aspek | Ketentuan |
| --- | --- |
| Format respons web | HTML (Blade) dirender server |
| Format respons JSON | Hanya pada `POST /api/bookings` dan pada error yang diminta sebagai JSON (`Accept: application/json`) |
| Autentikasi | Session cookie Laravel untuk area Admin dan Pemilik Usaha |
| Perlindungan CSRF | Wajib untuk seluruh request web non-GET (`_token` pada form atau header `X-CSRF-TOKEN`) |
| Bahasa pesan | Bahasa Indonesia |
| Nomor halaman/daftar | Paginasi 15 baris per halaman pada tabel admin dan laporan |

## 3. Tabel Route Lengkap (Data Contract)

Kolom **Hak Akses**: `Publik` berarti tanpa login; `Admin` berarti middleware `auth` + `role:ADMIN`; `Pemilik` berarti middleware `auth` + `role:PEMILIK_USAHA`.

| No | Route | HTTP Method | Controller | Controller Method | Parameter Input | Validasi | Hak Akses | Hasil |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 1 | `/` | GET | Closure (`routes/web.php`) | - | - | - | Publik | View `beranda.welcome` dengan 3 jadwal tersedia terdekat |
| 2 | `/ketersediaan-jadwal` | GET | Closure | - | - | - | Publik | View `jadwal.ketersediaan-jadwal` berisi semua jadwal hari ini dan setelahnya |
| 3 | `/booking` | GET | Closure | - | - | - | Publik | View `booking.booking` dengan jadwal tersedia dan layanan aktif |
| 4 | `/booking` | POST | `BookingController` | `store` | `nama`, `telepon`, `jenis_perangkat`, `merek_model`, `service_id`, `keluhan`, `schedule_id`, `payment_method` | wajib; regex nama & telepon; `in:Laptop,PC Desktop`; `exists` layanan aktif; `exists` jadwal; `in:CASH,QRIS` | Publik | Redirect ke `/booking` + flash `success` dan `service_id_code`; gagal jadwal = flash `error`; gagal validasi = redirect dengan `$errors` |
| 5 | `/cek-status` | GET | `StatusController` | `index` | `code` (opsional pada query) | - | Publik | View `status.cek-status` (form pencarian) |
| 6 | `/cek-status/result` | GET | `StatusController` | `show` | `code` | `required`, `string`, `max:50` | Publik | View `status.cek-status` dengan `booking`, `payment`, `serviceRecord`; kode tidak ada = redirect + flash `error` |
| 7 | `/cek-status/upload-proof` | POST | `StatusController` | `uploadProof` | `service_id_code`, `payment_proof` (file) | `exists:bookings,service_id_code`; `file`,`image`,`mimes:jpeg,png,jpg,webp`,`mimetypes`,`max:2048` | Publik | Redirect ke halaman cek status + flash `success` atau `error` |
| 8 | `/cek-status/{code}/cancel` | POST | `StatusController` | `cancel` | `cancellation_reason` | `required`, `string`, `max:500` | Publik | Redirect ke halaman cek status + flash; status booking menjadi `CANCELLED` |
| 9 | `/cek-status/{code}/bukti-pembayaran` | GET | `PaymentProofController` | `showForCustomer` | `code` (parameter URL) | booking harus `COMPLETED`, pembayaran harus ada dan metode QRIS | Publik | Berkas gambar inline; 404 bila tidak memenuhi syarat |
| 10 | `/login` | GET | `AuthController` | `showLogin` | - | - | Publik | View `login.login` |
| 11 | `/login` | POST | `AuthController` | `login` | `email`, `password`, `role` | `email`, `required`; `in:admin,pemilik` | Publik | Redirect ke `admin.dashboard` atau `pemilik.dashboard`; gagal = redirect `/login` + `$errors` |
| 12 | `/logout` | POST | `AuthController` | `logout` | - | - | Login (semua role) | Redirect `/login`, session diinvalidasi |
| 13 | `/dashboard-admin` | GET | `AdminPanelController` | `dashboard` | - | - | Admin | View `admin.dashboard-admin` dengan `stats`, `recentBookings`, `upcomingSchedules`, `recentQrisProofs` |
| 14 | `/admin/jadwal-servis` | GET | `AdminPanelController` | `schedules` | `page` | - | Admin | View `admin.jadwal-servis`, paginasi 15 |
| 15 | `/admin/jadwal-servis` | POST | `AdminPanelController` | `storeSchedule` | `schedule_date`, `start_time`, `end_time`, `max_booking` | `date`,`after_or_equal:today`; `date_format:H:i`; `after:start_time`; `integer`,`min:1`; tolak jadwal bertabrakan | Admin | Redirect ke daftar jadwal + flash `success` atau error validasi |
| 16 | `/admin/jadwal-servis/{scheduleId}/status` | PATCH | `AdminPanelController` | `updateScheduleStatus` | `scheduleId` (URL) | jadwal harus ada | Admin | Status berubah `CLOSED` atau `AVAILABLE`/`FULL` + flash |
| 17 | `/admin/kelola-proses-servis` | GET | `AdminPanelController` | `serviceProcess` | `status` (`active`,`waiting`,`completed`,`all`), `q`, `page` | nilai status di luar daftar dikembalikan ke `active` | Admin | View `admin.kelola-proses-servis` |
| 18 | `/admin/booking/{id}/verify` | PUT | `BookingController` | `verifyBooking` | `id` (URL) | booking harus `PENDING`/`WAITING_VERIFICATION` | Admin | Membuat `service_records`, status menjadi `CONFIRMED`, flash `success` |
| 19 | `/admin/booking/update` | POST | `BookingController` | `updateBookingStatus` | `service_id_code`, `service_status`, `diagnosis`, `estimated_duration`, `estimated_cost`, `final_cost`, `service_notes` | `exists` kode; `in:DIAGNOSIS,PROCESS,COMPLETED`; numeric min 0 maks 9.999.999.999,99; `COMPLETED` wajib `final_cost` > 0; status tidak boleh mundur | Admin | Redirect + flash; memperbarui `service_records`, `bookings`, dan `payments.amount` |
| 20 | `/admin/booking/{id}/cancel` | POST | `AdminPanelController` | `cancelBooking` | `cancellation_reason` | `required`, `max:500`; booking bukan `PROCESS`/`COMPLETED` | Admin | Status `CANCELLED`, slot dikembalikan, flash `success`/`error` |
| 21 | `/admin/booking/{id}/mark-paid` | PUT | `AdminPanelController` | `markPaid` | `cash_received` (wajib untuk CASH) | booking `COMPLETED`; QRIS wajib `WAITING_VERIFICATION` + bukti; CASH wajib angka >= tagihan | Admin | `payment_status` menjadi `PAID`, flash `success`/`error` |
| 22 | `/admin/pembayaran` | GET | `AdminPanelController` | `payments` | `status` (`all`,`paid`,`unpaid`), `q`, `page` | - | Admin | View `admin.pembayaran` |
| 23 | `/admin/pembayaran/{paymentId}/bukti` | GET | `PaymentProofController` | `show` | `paymentId` | metode QRIS dan berkas ada | Admin | Berkas gambar inline |
| 24 | `/admin/pembayaran/{paymentId}/minta-upload-ulang` | POST | `PaymentProofController` | `requestReupload` | `note` | `nullable`, `max:200`; status harus `WAITING_VERIFICATION` | Admin | Status kembali `UNPAID`, catatan tersimpan, flash |
| 25 | `/admin/pembayaran/{paymentId}/struk` | GET | `AdminPanelController` | `paymentReceipt` | `paymentId` | pembayaran harus `PAID` | Admin | View `admin.struk-pembayaran`; belum lunas = redirect + flash `error` |
| 26 | `/admin/data-customer` | GET | `AdminPanelController` | `customers` | `q`, `page` | - | Admin | View `admin.data-customer` |
| 27 | `/admin/data-perangkat` | GET | `AdminPanelController` | `devices` | `q`, `page` | - | Admin | View `admin.data-perangkat` |
| 28 | `/admin/data-service` | GET | `AdminPanelController` | `services` | `q`, `page` | - | Admin | View `admin.data-service` |
| 29 | `/admin/data-service` | POST | `AdminPanelController` | `storeService` | `name`, `description`, `estimated_price`, `is_active` | `required`,`max:255`; `nullable`,`max:2000`; `numeric`,`min:0` | Admin | Insert layanan baru + flash `success` |
| 30 | `/admin/data-service/{id}` | PUT | `AdminPanelController` | `updateService` | `name`, `description`, `estimated_price`, `is_active` | sama dengan no. 29; 404 bila layanan tidak ada | Admin | Update layanan + flash `success` |
| 31 | `/admin/data-service/{id}/toggle` | PATCH | `AdminPanelController` | `toggleService` | `id` | layanan harus ada | Admin | `is_active` dibalik + flash `success` |
| 32 | `/admin/riwayat-servis` | GET | `ReportController` | `serviceHistory` | `from`, `to`, `q`, `page` | tanggal tidak valid diabaikan; rentang tertukar dibalik | Admin | View `admin.riwayat-servis` + ringkasan jumlah dan total biaya |
| 33 | `/admin/riwayat-servis/pdf` | GET | `ReportController` | `serviceHistoryPdf` | `from`, `to`, `q`, `print` | sama dengan no. 32 | Admin | Unduhan PDF (atau inline bila `print=1`) |
| 34 | `/admin/riwayat-servis/excel` | GET | `ReportController` | `serviceHistoryExcel` | `from`, `to`, `q` | sama dengan no. 32 | Admin | Unduhan XLSX |
| 35 | `/dashboard-pemilik` | GET | Closure | - | `date` | - | Pemilik | View `pemilik.dashboard-pemilik` dengan `stats`, `bookings`, `serviceHistory`, `paymentProofs` |
| 36 | `/pemilik/riwayat-servis` | GET | `ReportController` | `serviceHistory` | `from`, `to`, `q`, `page` | - | Pemilik | Halaman laporan yang sama dengan no. 32, tautan mengarah ke prefix `pemilik` |
| 37 | `/pemilik/riwayat-servis/pdf` | GET | `ReportController` | `serviceHistoryPdf` | `from`, `to`, `q`, `print` | - | Pemilik | Unduhan PDF |
| 38 | `/pemilik/riwayat-servis/excel` | GET | `ReportController` | `serviceHistoryExcel` | `from`, `to`, `q` | - | Pemilik | Unduhan XLSX |
| 39 | `/pemilik/laporan-pemasukan` | GET | `ReportController` | `income` | `from`, `to`, `method`, `page` | `method` hanya `CASH`/`QRIS`, selain itu diabaikan | Pemilik | View `admin.laporan-pemasukan` + ringkasan pemasukan |
| 40 | `/pemilik/laporan-pemasukan/pdf` | GET | `ReportController` | `incomePdf` | `from`, `to`, `method`, `print` | - | Pemilik | Unduhan PDF |
| 41 | `/pemilik/laporan-pemasukan/excel` | GET | `ReportController` | `incomeExcel` | `from`, `to`, `method` | - | Pemilik | Unduhan XLSX |
| 42 | `/pemilik/pembayaran/{paymentId}/bukti` | GET | `PaymentProofController` | `show` | `paymentId` | metode QRIS dan berkas ada | Pemilik | Berkas gambar inline (read-only) |
| 43 | `/api/bookings` | POST | `Api\ApiBookingController` | `store` | `nama`, `telepon`, `jenis_perangkat`, `merek_model`, `service`, `keluhan`, `schedule_id`, `payment_method` | sama dengan booking web, dengan `service` bernilai `laptop`/`pc` | Publik (JSON) | HTTP 201 JSON berisi `service_id_code` |

Route di luar tabel di atas tetapi muncul pada `php artisan route:list` adalah route bawaan framework dan alat pengembangan: `/up` (health check), `storage/{path}` (penyajian berkas storage publik), `sanctum/csrf-cookie`, dan `_boost/browser-logs`.

## 4. Endpoint JSON yang Benar-Benar Ada

### 4.1 `POST /api/bookings`

**Tujuan:** membuat booking baru dan mengembalikan Service ID dalam format JSON.

**Autentikasi:** tidak ada (endpoint publik, sama seperti booking web).

**Header wajib:** `Content-Type: application/json`, `Accept: application/json`.

**Body:**

| Parameter | Tipe | Wajib | Aturan | Keterangan |
| --- | --- | --- | --- | --- |
| `nama` | string | ya | maks 255, regex `^[a-zA-Z\s\.\,\-]+$` | Nama pelanggan |
| `telepon` | string | ya | 10-15 karakter, regex `^[0-9+\-\s()]+$` | Nomor telepon |
| `jenis_perangkat` | string | ya | `Laptop` atau `PC Desktop` | Jenis perangkat |
| `merek_model` | string | ya | maks 255 | Merek dan model |
| `service` | string | ya | `laptop` atau `pc` | Kategori layanan; dipetakan ke nama layanan `Servis Laptop` / `Servis PC Desktop` |
| `keluhan` | string | ya | maks 1000 | Keluhan kerusakan |
| `schedule_id` | integer | ya | harus ada pada tabel `schedules` | Slot jadwal |
| `payment_method` | string | ya | `CASH` atau `QRIS` | Metode pembayaran |

**Contoh request:**

```json
{
  "nama": "Budi Santoso",
  "telepon": "081234567890",
  "jenis_perangkat": "Laptop",
  "merek_model": "Asus Vivobook A14",
  "service": "laptop",
  "keluhan": "Laptop mati total setelah terkena air",
  "schedule_id": 12,
  "payment_method": "QRIS"
}
```

**Respons 201 Created:**

```json
{
  "success": true,
  "message": "Booking berhasil dibuat. Silakan simpan Service ID Anda.",
  "data": {
    "service_id_code": "FP-20261115-A7K2Q",
    "status": "PENDING",
    "created_at": "2026-11-15T09:12:44.000000Z"
  }
}
```

**Respons 422 Unprocessable Entity (validasi gagal):**

```json
{
  "success": false,
  "message": "Validasi data gagal. Silakan periksa kembali input Anda.",
  "errors": {
    "telepon": ["Format nomor telepon tidak valid."],
    "payment_method": ["Metode pembayaran wajib dipilih."]
  }
}
```

**Respons 422 (jadwal penuh atau layanan tidak ditemukan):**

```json
{
  "success": false,
  "message": "Maaf, jadwal yang Anda pilih sudah penuh atau tidak tersedia.",
  "errors": {
    "schedule_id": ["Slot jadwal sudah habis."]
  }
}
```

**Respons 500 (kesalahan server):**

```json
{
  "success": false,
  "message": "Terjadi kesalahan pada server saat memproses transaksi.",
  "error": "SQLSTATE[...] (detail hanya muncul karena blok catch menampilkan pesan exception)"
}
```

> Catatan keamanan: pada blok `catch` di `ApiBookingController@store`, pesan exception dikembalikan apa adanya. Untuk production, detail tersebut sebaiknya tidak dikirim ke klien. Hal ini belum diubah pada Sprint 4 karena berada di luar perbaikan bug yang ditemukan pada pengujian dan memerlukan keputusan pemilik produk; tercatat sebagai rekomendasi, bukan sebagai bug yang menghalangi rilis.

## 5. Controller Action Reference

| Controller | Method | Ringkasan perilaku | View / Response |
| --- | --- | --- | --- |
| `AuthController` | `showLogin` | Menampilkan form login | `login.login` |
| `AuthController` | `login` | Validasi, `Auth::attempt`, regenerate session, cek role, redirect sesuai role | Redirect |
| `AuthController` | `logout` | Logout + invalidate + regenerate token | Redirect `/login` |
| `BookingController` | `store` | Transaksi booking lengkap (pelanggan, perangkat, booking, pembayaran, kuota jadwal) | Redirect + flash |
| `BookingController` | `verifyBooking` | Terima booking, buat `service_records`, status `CONFIRMED` | Redirect + flash |
| `BookingController` | `updateBookingStatus` | Diagnosis, estimasi, status pengerjaan, biaya akhir, sinkronisasi `payments.amount` | Redirect + flash/`$errors` |
| `BookingController` | `markPaid` | Konfirmasi pembayaran versi Eloquent (route legacy tidak dipakai antarmuka) | Redirect + flash |
| `AdminPanelController` | `dashboard` | Statistik dan ringkasan dashboard Admin | `admin.dashboard-admin` |
| `AdminPanelController` | `schedules`/`storeSchedule`/`updateScheduleStatus` | Pengelolaan jadwal dan deteksi tabrakan waktu | `admin.jadwal-servis` |
| `AdminPanelController` | `serviceProcess` | Daftar booking dengan filter status dan pencarian | `admin.kelola-proses-servis` |
| `AdminPanelController` | `customers`/`devices`/`services` | Data master dengan subquery hitungan | `admin.data-*` |
| `AdminPanelController` | `storeService`/`updateService`/`toggleService` | CRUD layanan tanpa penghapusan data historis | Redirect + flash |
| `AdminPanelController` | `payments` | Daftar pembayaran dengan filter dan pencarian | `admin.pembayaran` |
| `AdminPanelController` | `paymentReceipt` | Struk cetak, hanya untuk pembayaran lunas | `admin.struk-pembayaran` |
| `AdminPanelController` | `cancelBooking` | Pembatalan + pengembalian slot dalam transaksi | Redirect + flash |
| `AdminPanelController` | `markPaid` | Konfirmasi pembayaran (QRIS/CASH) dengan aturan bukti dan uang diterima | Redirect + flash |
| `StatusController` | `index`/`show` | Halaman cek status dan pencarian Service ID | `status.cek-status` |
| `StatusController` | `cancel` | Pembatalan oleh pelanggan + pengembalian slot | Redirect + flash |
| `StatusController` | `uploadProof` | Validasi dan penyimpanan bukti QRIS | Redirect + flash |
| `StatusController` | `upload` | Alias kompatibilitas, memanggil `uploadProof` | Redirect + flash |
| `PaymentProofController` | `show`/`showForCustomer`/`streamProof` | Penyajian berkas bukti dengan pemeriksaan MIME dan hak akses | Berkas inline |
| `PaymentProofController` | `requestReupload` | Penolakan bukti dan permintaan unggah ulang | Redirect + flash |
| `ReportController` | `serviceHistory`/`serviceHistoryPdf`/`serviceHistoryExcel` | Riwayat servis dengan filter tanggal dan ekspor | View / PDF / XLSX |
| `ReportController` | `income`/`incomePdf`/`incomeExcel` | Laporan pemasukan dengan filter metode dan ekspor | View / PDF / XLSX |
| `Api\ApiBookingController` | `store` | Booking melalui JSON API | JSON 201/422/500 |

## 6. Data Dictionary (Field Formulir)

| Field | Muncul pada | Tipe | Wajib | Aturan validasi | Pesan kesalahan (ringkas) |
| --- | --- | --- | --- | --- | --- |
| `nama` | Booking web dan API | string | Ya | maks 255, regex huruf/spasi/titik/koma/strip | Nama hanya boleh berisi huruf, spasi, tanda titik, koma, atau strip |
| `telepon` | Booking web dan API | string | Ya | 10-15 karakter, regex angka dan simbol telepon | Nomor telepon minimal 10 digit |
| `jenis_perangkat` | Booking web dan API | string | Ya | `Laptop` atau `PC Desktop` | Jenis perangkat harus dipilih |
| `merek_model` | Booking web dan API | string | Ya untuk laptop | maks 255; wajib kecuali `PC Desktop` | Merek / model wajib diisi untuk perangkat laptop |
| `service_id` | Booking web | integer | Ya | harus ada dan `is_active = true` | Layanan yang dipilih tidak tersedia |
| `service` | Booking API | string | Ya | `laptop` atau `pc` | Kategori layanan wajib dipilih |
| `keluhan` | Booking web dan API | string | Ya | maks 1000 | Keluhan maksimal 1000 karakter |
| `schedule_id` | Booking web dan API | integer | Ya | harus ada pada `schedules` | Jadwal yang dipilih tidak valid |
| `payment_method` | Booking web dan API | string | Ya | `CASH` atau `QRIS` | Metode pembayaran wajib dipilih |
| `email` | Login | string | Ya | format email | Email atau password salah |
| `password` | Login | string | Ya | - | Email atau password salah |
| `role` | Login | string | Ya | `admin` atau `pemilik` | Akun ini tidak memiliki akses sebagai ... |
| `schedule_date` | Jadwal | date | Ya | tidak boleh sebelum hari ini | - |
| `start_time`, `end_time` | Jadwal | time | Ya | format `H:i`, `end_time` setelah `start_time` | Jadwal bertabrakan dengan jadwal yang sudah ada |
| `max_booking` | Jadwal | integer | Ya | minimal 1 | - |
| `service_id_code` | Proses servis, unggah bukti | string | Ya | harus ada pada `bookings` | Service ID tidak ditemukan |
| `service_status` | Proses servis | string | Ya | `DIAGNOSIS`, `PROCESS`, `COMPLETED` | Status pengerjaan tidak dapat dikembalikan ke tahap sebelumnya |
| `diagnosis` | Proses servis | text | Tidak | maks 5000 | - |
| `estimated_duration` | Proses servis | string | Tidak | maks 100 | - |
| `estimated_cost`, `final_cost` | Proses servis | numeric | Tidak (wajib saat `COMPLETED` untuk `final_cost`) | minimal 0, maksimal 9.999.999.999,99 | Biaya akhir harus lebih dari Rp0 sebelum servis diselesaikan |
| `service_notes` | Proses servis | text | Tidak | maks 3000 | - |
| `cancellation_reason` | Pembatalan | text | Ya | maks 500 | Alasan pembatalan wajib diisi |
| `payment_proof` | Unggah bukti | file | Ya | gambar JPG/JPEG/PNG/WEBP, maks 2 MB | Ukuran bukti pembayaran maksimal 2 MB |
| `cash_received` | Konfirmasi cash | numeric | Ya untuk CASH | tidak kurang dari tagihan | Uang tunai yang diterima kurang dari total tagihan |
| `note` | Minta unggah ulang | text | Tidak | maks 200 | Catatan penolakan maksimal 200 karakter |
| `name`, `description`, `estimated_price`, `is_active` | Data layanan | string/text/numeric/bool | `name` wajib | maks 255 / maks 2000 / min 0 | - |
| `from`, `to` | Laporan | date | Tidak | format tanggal; rentang tertukar dibalik otomatis | - |
| `method` | Laporan pemasukan | string | Tidak | `CASH` atau `QRIS` | - |
| `q` | Pencarian admin | string | Tidak | - | - |

## 7. Penanganan Error dan Kode Status

| Kode | Kapan terjadi | Respons |
| --- | --- | --- |
| 200 | Halaman atau berkas berhasil ditampilkan | HTML, PDF, atau XLSX |
| 201 | `POST /api/bookings` berhasil | JSON `success: true` |
| 302 | Validasi gagal pada form, redirect setelah aksi, atau tamu membuka halaman terproteksi | Redirect dengan `$errors` atau flash message |
| 403 | Role tidak sesuai middleware `role` | Halaman `errors/403` dengan teks "Akses Ditolak" |
| 404 | Data tidak ditemukan, berkas bukti tidak ada, atau booking belum selesai saat membuka bukti | Halaman `errors/404` |
| 419 | Token CSRF kedaluwarsa | Halaman `errors/419` |
| 422 | Validasi gagal pada endpoint JSON | JSON `success: false` dengan `errors` |
| 500 | Kesalahan server atau `QueryException` | Halaman `errors/500`, atau JSON `{"message": "..."}` bila klien meminta JSON, tanpa detail query ketika `APP_DEBUG=false` |
| 503 | Mode maintenance | Halaman `errors/503` |

## 8. Hak Akses yang Diuji

Pengujian otomatis yang membuktikan pembatasan hak akses (berkas `tests/Feature/Sprint3AcceptanceTest.php`):

| Pengujian | Yang dibuktikan |
| --- | --- |
| `test_tamu_tidak_bisa_membuka_dashboard_admin` | Tamu diarahkan ke login |
| `test_tamu_tidak_bisa_membuka_laporan_internal` | Laporan Admin dan Pemilik tidak terbuka tanpa login |
| `test_pemilik_tidak_bisa_membuka_halaman_admin` | Pemilik menerima 403 pada halaman Admin |
| `test_admin_tidak_bisa_membuka_dashboard_pemilik` | Admin menerima 403 pada panel Pemilik |
| `test_laporan_bisa_dibuka_sesuai_hak_akses` | Riwayat servis dapat dibuka Admin dan Pemilik; laporan pemasukan hanya Pemilik |
| `test_pemilik_bisa_mengunduh_laporan_tetapi_tidak_bisa_membuka_pembayaran_admin` | Ekspor laporan Pemilik berhasil, halaman pembayaran Admin ditolak |

## 9. Postman Collection

Berkas `docs/04-api/fixpoint_service_api_collection.json` disertakan dengan format Postman Collection v2.1. Koleksi ini **hanya** memuat `POST /api/bookings` beserta contoh respons 201, 422, dan 500, karena hanya endpoint itulah yang benar-benar berfungsi. Variabel koleksi: `base_url` (contoh `http://127.0.0.1:8000`), `schedule_id`, dan `service_id_code`.

Tidak ada endpoint rekaan pada koleksi ini. Seluruh interaksi lain aplikasi berjalan melalui form HTML, sehingga pengujiannya dilakukan lewat browser, bukan Postman.
