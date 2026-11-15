---
title: Checklist Pertanggungjawaban Individu
subtitle: Materi Tanya Jawab Teknis, Pembagian Tanggung Jawab, dan Latihan Live Coding
doc_type: DOKUMEN PERTAHANAN INDIVIDU (S4-08)
project: UKK RPL - Sprint 4 Finalization and Release Candidate
identity: FixPoint Service
version: 1.0.0-rc
date: 15 November 2026
---

# BAGIAN I - MATERI PEMAHAMAN SISTEM

Bagian ini adalah bekal bersama bagi Davin (Backend) dan Lutvi (Frontend). Semua jawaban mengacu pada kode yang benar-benar ada di repository.

## 1.1 Tujuan FixPoint Service

FixPoint Service adalah sistem informasi booking dan manajemen jasa servis laptop dan PC untuk UMKM. Sistem mencatat booking pelanggan, membuat Service ID, mengelola diagnosis dan biaya servis, mencatat pembayaran tunai maupun QRIS, menampilkan status pengerjaan kepada pelanggan, serta menyediakan laporan bagi pemilik usaha.

## 1.2 Masalah yang Diselesaikan

| Masalah manual | Solusi di aplikasi |
| --- | --- |
| Booking lewat chat mudah terlewat | Formulir booking tersimpan di database dengan status yang jelas |
| Pelanggan berulang kali menanyakan progres | Halaman cek status berdasarkan Service ID |
| Nomor urut servis tertukar | Service ID unik `FP-YYYYMMDD-XXXXX` dengan constraint unique |
| Bukti transfer QRIS hilang di galeri ponsel | Unggah bukti ke sistem, disimpan di luar document root |
| Laporan pemasukan dihitung manual | Laporan pemasukan otomatis dengan ekspor PDF dan Excel |

## 1.3 Fitur Aplikasi

Fitur publik: beranda, ketersediaan jadwal, formulir booking, cek status, unggah bukti QRIS, pembatalan booking.

Fitur Admin: dashboard, kelola jadwal, terima booking, diagnosis dan estimasi, biaya akhir dan status pengerjaan, konfirmasi pembayaran, pembatalan, struk, data pelanggan, data perangkat, data layanan, riwayat servis, ekspor PDF dan Excel.

Fitur Pemilik Usaha: dashboard monitoring, riwayat servis, laporan pemasukan, pratinjau bukti pembayaran QRIS (read-only).

## 1.4 Aktor dan Hak Akses

| Aktor | Login | Data yang disentuh |
| --- | --- | --- |
| Pelanggan | Tidak | Booking miliknya sendiri melalui Service ID |
| Admin | Ya (role `ADMIN`) | Seluruh transaksi dan data master, kecuali laporan pemasukan |
| Pemilik Usaha | Ya (role `PEMILIK_USAHA`) | Baca laporan, dashboard, dan bukti pembayaran |

Pembatasan dijalankan `app/Http/Middleware/RoleMiddleware.php` melalui alias `role` yang dipasang di grup route `routes/web.php`.

## 1.5 Alur Bisnis Ringkas

1. Pelanggan membuka beranda, melihat layanan dan jadwal.
2. Pelanggan mengisi formulir booking tanpa login.
3. Sistem membuat pelanggan (bila baru), perangkat, booking berstatus `PENDING`, dan baris pembayaran `UNPAID`, lalu menampilkan Service ID.
4. Admin menerima booking sehingga status menjadi `CONFIRMED` dan catatan servis dibuat.
5. Admin mengisi diagnosis, estimasi, status `PROCESS`, lalu biaya akhir dan status `COMPLETED`.
6. Pelanggan membayar tunai di toko atau QRIS lalu mengunggah bukti.
7. Admin mengonfirmasi pembayaran sehingga status menjadi `PAID` dan struk dapat dicetak.
8. Pemilik Usaha membaca laporan pemasukan hasil transaksi tersebut.

## 1.6 Database dan ERD

Tujuh tabel inti: `users`, `customers`, `devices`, `services`, `schedules`, `bookings`, `service_records`, dan `payments` (delapan bila `users` dihitung terpisah dari tabel lain). Relasi utama:

- `customers` 1 ke banyak `devices`, `bookings`, `payments`.
- `bookings` menunjuk satu `services` dan satu `schedules`, serta memiliki paling banyak satu `service_records` dan satu `payments`.
- `users` menjadi penanggung jawab pada `bookings.handled_by` dan `service_records.handled_by`.

## 1.7 Arsitektur MVC dan Alur Backend-Frontend

Request masuk melalui `public/index.php`, melewati middleware session dan CSRF, dicocokkan ke route, dijalankan controller (validasi, query, transaksi), lalu dirender menjadi Blade. Blade mengirim HTML ke browser; tidak ada proses build JavaScript yang wajib.

# BAGIAN II - SEPULUH PERTANYAAN TEKNIS WAJIB

## Q1 - Bagaimana sistem menangani booking agar slot jadwal tidak terpakai ganda?

**Jawaban sederhana.** Sistem mengunci baris jadwal selama proses booking, memeriksa sisa kuota, lalu menambah pemakaian kuota. Selama satu permintaan masih memproses, permintaan lain menunggu sehingga slot terakhir tidak bisa diambil dua orang sekaligus.

**Penjelasan teknis.** `BookingController@store` membungkus seluruh proses dalam `DB::transaction()`. Baris `schedules` dibaca dengan `lockForUpdate()` sehingga database mengunci baris tersebut sampai transaksi selesai. Setelah itu kondisi `status === 'AVAILABLE'` dan `current_booking < max_booking` diperiksa ulang di dalam transaksi (bukan hanya di halaman form). Bila lolos, `current_booking` dinaikkan dan status diubah menjadi `FULL` ketika kuota habis. Tambahan pengaman: Service ID diperiksa unik dengan perulangan dan kolom `service_id_code` memiliki constraint `unique`.

File: `app/Http/Controllers/BookingController.php`. Method: `store()`.

```php
$schedule = Schedule::query()
    ->where('schedule_id', $validated['schedule_id'])
    ->whereDate('schedule_date', '>=', now()->toDateString())
    ->lockForUpdate()
    ->first();
```

## Q2 - Mengapa perlu database transaction saat satu proses menyentuh beberapa tabel?

**Jawaban sederhana.** Booking membuat data pelanggan, perangkat, booking, dan pembayaran sekaligus. Bila langkah terakhir gagal, data setengah jadi akan merusak laporan. Transaction memastikan semuanya berhasil atau semuanya dibatalkan.

**Penjelasan teknis.** `DB::transaction(function () { ... })` di `BookingController@store` mencakup pembuatan `customers`, `devices`, `bookings`, `payments`, serta pembaruan `schedules`. Bila terjadi exception di tengah proses, Laravel menjalankan rollback sehingga tidak ada booking tanpa pembayaran atau slot yang berkurang tanpa booking. Pola yang sama dipakai `StatusController@cancel` (booking + pengembalian slot), `StatusController@uploadProof` (path bukti + status pembayaran), dan `AdminPanelController@cancelBooking`.

File: `app/Http/Controllers/BookingController.php`, `app/Http/Controllers/StatusController.php`, `app/Http/Controllers/AdminPanelController.php`.

## Q3 - Bagaimana aplikasi menyimpan password Admin dan Pemilik Usaha?

**Jawaban sederhana.** Password tidak disimpan sebagai teks. Sistem mengubahnya menjadi hash bcrypt, sehingga isi database tidak dapat dibaca langsung sebagai password.

**Penjelasan teknis.** Pembuatan akun memakai `Hash::make($password)` (lihat `database/seeders/DatabaseSeeder.php` dan `UserController@store`). Pemeriksaan saat login dilakukan `Auth::attempt()` yang di dalamnya memanggil `Hash::check()`. Model `User` menetapkan `protected $hidden = ['password'];` sehingga atribut tersebut tidak ikut terserialisasi. `BCRYPT_ROUNDS=12` pada `.env.example` menentukan biaya hashing.

File: `app/Models/User.php`, `app/Http/Controllers/AuthController.php`, `database/seeders/DatabaseSeeder.php`.

## Q4 - Bagaimana aplikasi menghindari SQL Injection?

**Jawaban sederhana.** Semua nilai dari pengguna dikirim ke database sebagai parameter terikat, bukan ditempel ke dalam teks perintah SQL.

**Penjelasan teknis.** Query memakai Eloquent dan Query Builder (`where`, `whereIn`, `whereDate`, `like`) yang secara otomatis memakai prepared statement. Pencarian memakai `'%' . $search . '%'` sebagai nilai parameter, bukan interpolasi ke string SQL. Tidak ada pemakaian `DB::raw()` dengan input pengguna.

File: `app/Http/Controllers/AdminPanelController.php` (method `serviceProcess`, `customers`, `devices`, `payments`), `app/Http/Controllers/ReportController.php`.

```php
$query->where(function ($subQuery) use ($search) {
    $subQuery->where('b.service_id_code', 'like', '%' . $search . '%')
        ->orWhere('c.name', 'like', '%' . $search . '%');
});
```

## Q5 - Apa beda validasi di browser dan validasi Laravel di server?

**Jawaban sederhana.** Atribut `required` dan `type` di HTML hanya membantu pengguna; nilainya dapat dilewati. Validasi Laravel di server adalah yang menentukan data boleh masuk database.

**Penjelasan teknis.** Form booking memakai atribut HTML seperti `required` dan `maxlength` untuk kenyamanan, tetapi aturan sebenarnya ada pada `$request->validate([...])` di `BookingController@store` dan `Validator::make()` di `ApiBookingController@store`. Validasi server menolak data yang tidak sesuai meskipun permintaan dikirim langsung tanpa browser (misalnya lewat alat uji HTTP), dan menghasilkan HTTP 302 dengan `$errors` (web) atau HTTP 422 dengan JSON `errors` (API).

File: `app/Http/Controllers/BookingController.php`, `app/Http/Controllers/Api/ApiBookingController.php`, `resources/views/booking/booking.blade.php`.

## Q6 - Bagaimana sistem melindungi proses unggah bukti pembayaran QRIS?

**Jawaban sederhana.** Berkas diperiksa harus benar-benar gambar dengan format dan ukuran tertentu, disimpan dengan nama acak di luar folder publik, dan hanya bisa dibuka lewat route yang memeriksa hak akses.

**Penjelasan teknis.** `StatusController@uploadProof` memvalidasi `required|file|image|mimes:jpeg,png,jpg,webp|mimetypes:image/jpeg,image/png,image/webp|max:2048`. Berkas disimpan dengan `store('payment_proofs', 'public')` sehingga berada di `storage/app/public`. Saat menampilkan, `PaymentProofController` memeriksa MIME, mengalirkan berkas dengan header `X-Content-Type-Options: nosniff` dan `Cache-Control: private, no-store`, serta memastikan pembayaran berstatus QRIS dan booking berstatus `COMPLETED`. Bila transaksi gagal, berkas yang sudah terunggah dihapus.

File: `app/Http/Controllers/StatusController.php` (`uploadProof`), `app/Http/Controllers/PaymentProofController.php` (`show`, `showForCustomer`, `streamProof`).

## Q7 - Jelaskan relasi tabel dan normalisasi database FixPoint Service.

**Jawaban sederhana.** Setiap data disimpan di tempatnya masing-masing: pelanggan di tabel pelanggan, perangkat di tabel perangkat, layanan di tabel layanan. Booking hanya menyimpan nomor relasi, sehingga tidak ada data yang diulang-ulang.

**Penjelasan teknis.** Struktur memenuhi 1NF karena setiap kolom bernilai atomik, 2NF karena semua tabel memakai primary key satu kolom sehingga tidak ada ketergantungan parsial, dan 3NF karena atribut non-kunci bergantung hanya pada primary key tabelnya. Contoh: nama layanan dan estimasi harga hanya ada di `services`, sedangkan `bookings` menyimpan `service_id`. Satu denormalisasi disengaja: `payments.amount` menyalin `service_records.final_cost` karena nominal transaksi harus tetap sebagai nilai historis.

File: `database/migrations/2026_10_06_*` sampai `2026_10_07_073714_create_payments_table.php`.

## Q8 - Apa yang dilakukan bila internet terputus saat aplikasi dipresentasikan?

**Jawaban sederhana.** Aplikasi dijalankan di laptop dengan MySQL lokal, lalu diakses dari perangkat lain melalui alamat IP pada jaringan WiFi atau hotspot yang sama.

**Penjelasan teknis.** `run_local_offline.bat` memeriksa PHP, menyalakan MySQL bila belum berjalan, memeriksa `.env` dan `APP_KEY`, memastikan database dapat dihubungi, membersihkan cache (`php artisan optimize:clear`), menyiapkan folder `storage/app/public/payment_proofs`, membaca alamat IPv4 aktif dengan `ipconfig`, lalu menjalankan `php artisan serve --host 0.0.0.0 --port 8000`. Perangkat lain membuka `http://<IP-LAN>:8000`. Rincian dan batasan dijelaskan pada `docs/02-deployment/OFFLINE_DEPLOYMENT.md`.

File: `run_local_offline.bat`, `docs/02-deployment/OFFLINE_DEPLOYMENT.md`.

## Q9 - Bagaimana pembagian pekerjaan Davin dan Lutvi?

**Jawaban sederhana.** Davin mengerjakan bagian data dan logika di balik layar; Lutvi mengerjakan tampilan, formulir, dan pengalaman pengguna. Keduanya bertemu pada route dan nama field form.

**Penjelasan teknis.** Davin: `database/migrations`, `app/Models`, `app/Http/Controllers`, `routes/web.php`, `routes/api.php`, `app/Http/Middleware/RoleMiddleware.php`, `app/Support/SimpleXlsx.php`, laporan PDF/Excel, serta pengujian di `tests/Feature`. Lutvi: `resources/views/layouts`, `resources/views/**/*.blade.php`, CSS di dalam Blade, JavaScript interaksi (toggle password, sidebar, modal), form beserta `@csrf` dan `@method`, tabel dan kartu dashboard, serta penyesuaian responsif.

Titik temu yang wajib dipahami keduanya: nama route (`route('booking.store')`), nama field (`nama`, `telepon`, `jenis_perangkat`, `merek_model`, `service_id`, `keluhan`, `schedule_id`, `payment_method`), dan nama variabel yang dikirim controller ke view.

## Q10 - Apa kendala nyata saat aplikasi dipakai UMKM dan bagaimana solusinya?

**Jawaban sederhana.** Kendala utamanya bukan teknologi, melainkan kebiasaan dan perangkat: pelanggan lupa Service ID, admin terbiasa mencatat di buku, dan ponsel pelanggan beresolusi rendah.

**Penjelasan teknis dan solusi yang sudah ada di kode.**

| Kendala | Penanganan di aplikasi |
| --- | --- |
| Pelanggan lupa Service ID | Service ID ditampilkan setelah booking dan dapat dicari kembali di `/cek-status`; formatnya mudah dibaca (`FP-20261115-A7K2Q`) |
| Bukti QRIS berupa tangkapan layar besar | Validasi maksimal 2 MB dan format JPG/PNG/WEBP; pesan kesalahan berbahasa Indonesia |
| Admin salah menandai servis selesai | Status tidak bisa mundur, dan `COMPLETED` wajib memiliki biaya akhir lebih dari nol |
| Konfirmasi cash tanpa uang diterima | `cash_received` wajib diisi dan tidak boleh kurang dari tagihan |
| Koneksi internet toko tidak stabil | Mode offline LAN melalui `run_local_offline.bat` |
| Data sensitif terbuka di hosting | `.env` di luar akses browser, bukti pembayaran dialirkan lewat controller ber-otorisasi |

# BAGIAN III - PERTANGGUNGJAWABAN DAVIN (BACKEND)

## 3.1 Database dan Migration

**Pertanyaan penguji.** Mengapa primary key memakai `user_id` dan bukan `id`?

**Jawaban.** Karena skema proyek mengikuti nama kolom pada ERD, yaitu `user_id`, `booking_id`, dan seterusnya. Model menyesuaikan dengan `protected $primaryKey = 'user_id';` dan setiap foreign key menyebut kolom tujuan secara eksplisit, misalnya `->constrained('customers', 'customer_id')`.

**Lokasi.** `database/migrations/0001_01_01_000000_create_users_table.php`, `app/Models/User.php`.

## 3.2 Model dan Relasi Eloquent

**Pertanyaan penguji.** Tunjukkan satu relasi dan jelaskan cara memakainya.

**Jawaban.** `Booking::payment()` adalah relasi `hasOne` ke `Payment` melalui `booking_id`. Dipakai pada `StatusController@show` dengan eager loading: `Booking::with(['customer', 'device', 'schedule', 'service', 'payment'])`. Dengan eager loading, controller hanya menjalankan beberapa query, bukan satu query per baris.

**Lokasi.** `app/Models/Booking.php`, `app/Http/Controllers/StatusController.php`.

## 3.3 Controller dan Routing

**Pertanyaan penguji.** Mengapa `POST /admin/booking/update` tidak memakai parameter ID di URL?

**Jawaban.** Karena form proses servis dikirim berdasarkan `service_id_code` yang sudah unik dan dikenal Admin di lapangan. Controller memvalidasi `service_id_code` dengan `exists:bookings,service_id_code`, lalu mencari booking dengan `lockForUpdate()`. Ini juga mencegah Admin mengubah booking yang salah ketika bekerja dari kertas Service ID.

**Lokasi.** `routes/web.php`, `BookingController@updateBookingStatus`.

## 3.4 Middleware dan Otorisasi

**Pertanyaan penguji.** Bagaimana Anda memastikan Pemilik Usaha tidak bisa membuka halaman Admin?

**Jawaban.** Grup route Admin memakai `middleware(['auth', 'role:ADMIN'])`. `RoleMiddleware` mengambil `strtoupper($user->role)` dan membandingkannya dengan daftar role yang diminta; bila tidak cocok, `abort(403)` sehingga tampil halaman 403 kustom. Diuji otomatis pada `test_pemilik_tidak_bisa_membuka_halaman_admin`.

**Lokasi.** `app/Http/Middleware/RoleMiddleware.php`, `routes/web.php`, `tests/Feature/Sprint3AcceptanceTest.php`.

## 3.5 Validasi Server-side

**Pertanyaan penguji.** Mengapa nomor telepon memakai regex?

**Jawaban.** Agar data pelanggan dapat dicocokkan ulang saat booking berikutnya (pencarian memakai `phone_number`) dan agar nomor yang tidak mungkin dihubungi tidak masuk. Aturannya `min:10`, `max:15`, dan `regex:/^[0-9+\-\s()]+$/` dengan pesan kesalahan berbahasa Indonesia.

**Lokasi.** `BookingController@store`.

## 3.6 Query dan Transaksi

**Pertanyaan penguji.** Di mana `lockForUpdate()` dipakai dan mengapa?

**Jawaban.** Dipakai pada `schedules` (kuota jadwal), `bookings` dan `payments` (pembayaran), serta `service_records` (status pengerjaan). Tujuannya mencegah dua permintaan mengubah baris yang sama secara bersamaan sehingga kuota dan status pembayaran tetap konsisten.

**Lokasi.** `BookingController`, `StatusController`, `AdminPanelController`, `PaymentProofController`.

## 3.7 Proses Booking dan Pembuatan Service ID

**Pertanyaan penguji.** Bagaimana Service ID dijamin unik?

**Jawaban.** Dua lapis: perulangan `do { ... } while (Booking::where('service_id_code', $serviceIdCode)->exists());` dan constraint `unique` pada kolom `service_id_code`. Bila ada tabrakan, database tetap menolak.

**Lokasi.** `BookingController@store`, `database/migrations/2026_10_07_073455_create_bookings_table.php`.

## 3.8 Pembayaran dan Upload Bukti

**Pertanyaan penguji.** Mengapa konfirmasi QRIS mensyaratkan status `WAITING_VERIFICATION`?

**Jawaban.** Agar Admin tidak menandai lunas sebelum pelanggan mengunggah bukti. `AdminPanelController@markPaid` menolak QRIS yang belum memiliki bukti (`filled($payment->payment_proof)`), sedangkan CASH mewajibkan `cash_received` yang cukup.

**Lokasi.** `AdminPanelController@markPaid`, `StatusController@uploadProof`, `PaymentProofController@requestReupload`.

## 3.9 Perubahan Status dan Penanganan Error

**Pertanyaan penguji.** Apa yang terjadi bila Admin mencoba mengembalikan status servis ke tahap sebelumnya?

**Jawaban.** Ditolak. `updateBookingStatus` memakai peta urutan `PENDING => 0, DIAGNOSIS => 1, PROCESS => 2, COMPLETED => 3` dan melempar `ValidationException` bila status baru lebih rendah dari status saat ini.

Untuk error tak terduga, `bootstrap/app.php` menangkap `QueryException`, mencatatnya ke log melalui `report($exception)`, lalu menampilkan halaman `errors/500` atau JSON tanpa detail query ketika `APP_DEBUG=false`.

**Lokasi.** `BookingController@updateBookingStatus`, `bootstrap/app.php`.

## 3.10 Laporan PDF dan Excel

**Pertanyaan penguji.** Bagaimana Excel dibuat tanpa library tambahan?

**Jawaban.** `App\Support\SimpleXlsx` menulis struktur XLSX (zip berisi XML) secara langsung memakai `ZipArchive`, sehingga tidak perlu menambah dependensi besar pada hosting. PDF dibuat dengan dompdf melalui `Pdf::loadView(...)`.

**Lokasi.** `app/Support/SimpleXlsx.php`, `ReportController@serviceHistoryExcel`, `ReportController@incomePdf`.

# BAGIAN IV - PERTANGGUNGJAWABAN LUTVI (FRONTEND)

## 4.1 Struktur Blade dan Layout

**Pertanyaan penguji.** Mengapa ada dua layout?

**Jawaban.** `layouts/app.blade.php` dipakai halaman publik dan login (header-footer sederhana), sedangkan `layouts/admin.blade.php` menyediakan kerangka panel dengan sidebar, kartu statistik, tabel, dan form. Pemisahan ini menjaga halaman pelanggan tetap ringan.

**Lokasi.** `resources/views/layouts/app.blade.php`, `resources/views/layouts/admin.blade.php`.

## 4.2 Komponen dan Partial

**Pertanyaan penguji.** Bagian mana yang dibuat sebagai partial dan mengapa?

**Jawaban.** Sidebar (`admin/partials/sidebar.blade.php`), modal pembatalan (`admin/partials/cancel-booking-modal.blade.php`), dan pratinjau bukti QRIS (`admin/partials/qris-payment-proofs.blade.php`, `pemilik/partials/bukti-pembayaran.blade.php`). Tujuannya agar tidak ada salinan kode yang harus diperbarui di banyak halaman.

**Lokasi.** `resources/views/admin/partials`, `resources/views/pemilik/partials`.

## 4.3 Form, CSRF, dan Integrasi ke Route

**Pertanyaan penguji.** Bagaimana form dihubungkan ke controller?

**Jawaban.** Form memakai `action="{{ route('...') }}"`, `method="POST"`, dan `@csrf`. Untuk ubah data yang bukan POST, dipakai `@method('PUT')` atau `@method('PATCH')`. Contoh: konfirmasi pembayaran mengirim `PUT` ke `admin.booking.markPaid`, dan unggah bukti memakai `enctype="multipart/form-data"` ke `cek-status.upload-proof`.

**Lokasi.** `resources/views/admin/pembayaran.blade.php`, `resources/views/status/cek-status.blade.php`, `resources/views/booking/booking.blade.php`.

## 4.4 Data Dinamis dan Kondisi Tampilan

**Pertanyaan penguji.** Bagaimana Anda menampilkan status dengan warna berbeda?

**Jawaban.** Status dipetakan ke kelas badge memakai kondisi Blade, contoh pada dashboard admin: `in_array($booking->booking_status, ['PENDING','WAITING_VERIFICATION'], true)` menentukan kelas `badge-yellow`, `REJECTED`/`CANCELLED` menjadi `badge-red`, dan seterusnya. Semua logika tampilan memakai data yang sudah dikirim controller.

**Lokasi.** `resources/views/admin/dashboard-admin.blade.php`, `resources/views/admin/kelola-proses-servis.blade.php`.

## 4.5 CSS dan Responsivitas

**Pertanyaan penguji.** Mengapa CSS ditulis di dalam Blade, bukan file terpisah?

**Jawaban.** Agar aplikasi tidak bergantung pada proses build Vite. Tidak ada direktif `@vite` di view, sehingga deployment hanya membutuhkan PHP dan MySQL. Konsekuensinya CSS per halaman perlu dijaga konsistensinya; untuk skala proyek ini masih terkendali karena layout admin memuat sebagian besar gaya bersama.

Media query yang dipakai pada layout admin: 1100 px (kolom statistik menjadi dua), 800 px (sidebar menjadi menu lipat), dan 520 px (tabel dan form satu kolom).

**Lokasi.** `resources/views/layouts/admin.blade.php`, `resources/views/booking/booking.blade.php`.

## 4.6 JavaScript Interaksi

**Pertanyaan penguji.** JavaScript apa yang Anda tulis dan apa fungsinya?

**Jawaban.** Tiga hal kecil tanpa library: tombol tampilkan/sembunyikan password pada halaman login, tombol buka-tutup menu sidebar pada layar kecil, dan dialog konfirmasi pembatalan booking beserta kolom alasan wajib. Tidak ada permintaan data melalui `fetch` karena semua proses memakai submit form biasa.

**Lokasi.** `resources/views/login/login.blade.php`, `resources/views/admin/partials/sidebar.blade.php`, `resources/views/admin/partials/cancel-booking-modal.blade.php`.

## 4.7 Dashboard Admin dan Pemilik

**Pertanyaan penguji.** Apa perbedaan dashboard Admin dan Pemilik dari sisi tampilan?

**Jawaban.** Dashboard Admin berisi kartu tindakan (perlu tindakan, diagnosis, dikerjakan, belum dibayar), tabel booking terbaru, jadwal terdekat, dan pratinjau bukti QRIS dengan tombol aksi. Dashboard Pemilik menampilkan statistik monitoring, tabel booking, riwayat servis dengan filter tanggal, dan bukti pembayaran tanpa tombol yang mengubah data, karena role-nya read-only.

**Lokasi.** `resources/views/admin/dashboard-admin.blade.php`, `resources/views/pemilik/dashboard-pemilik.blade.php`, `routes/web.php` (closure `pemilik.dashboard`).

## 4.8 Tampilan Mobile dan Cetak

**Pertanyaan penguji.** Bagaimana tampilan struk saat dicetak?

**Jawaban.** `resources/views/admin/struk-pembayaran.blade.php` memakai gaya khusus cetak dengan lebar menyerupai printer thermal 80 mm dan aturan `@media print` untuk menyembunyikan tombol. Halaman laporan PDF memakai template terpisah di `resources/views/admin/exports`.

**Lokasi.** `resources/views/admin/struk-pembayaran.blade.php`, `resources/views/admin/exports/*.blade.php`.

# BAGIAN V - LATIHAN LIVE CODING

Latihan berikut hanya untuk dokumen. Jangan mengubah source code proyek untuk menyiapkan contoh; kerjakan langsung saat pengujian bila diminta.

## 5.1 Menambah Aturan Validasi

**Tugas.** Tolak nama pelanggan yang hanya berisi satu kata.

**Langkah.** Buka `app/Http/Controllers/BookingController.php`, pada aturan `nama` tambahkan `'min:3'` dan pesan `'nama.min' => 'Nama minimal 3 karakter.'`. Uji dengan mengirim nama `Al`.

**Hasil yang diharapkan.** Form kembali dengan pesan kesalahan pada field nama dan tidak ada baris baru pada tabel `bookings`.

## 5.2 Mengubah Urutan Data

**Tugas.** Tampilkan pembayaran terbaru berdasarkan tanggal bayar, bukan ID.

**Langkah.** Pada `AdminPanelController@payments`, ubah `->orderByDesc('p.payment_id')` menjadi `->orderByDesc('p.payment_date')`.

**Hasil yang diharapkan.** Baris dengan `payment_date` terbaru muncul paling atas; baris tanpa tanggal bayar berada di bawah.

## 5.3 Mengubah Kondisi Tampilan

**Tugas.** Tampilkan badge abu-abu untuk status `COMPLETED`.

**Langkah.** Pada `resources/views/admin/kelola-proses-servis.blade.php`, tambahkan kondisi `$booking->booking_status === 'COMPLETED'` yang menghasilkan kelas `badge-gray` sebelum kondisi lain.

**Hasil yang diharapkan.** Baris booking selesai menampilkan badge abu-abu dengan label Selesai.

## 5.4 Mengubah Label Status

**Tugas.** Ubah label `WAITING_VERIFICATION` menjadi `Menunggu Verifikasi Bukti`.

**Langkah.** Cari kemunculan label tersebut pada `resources/views/status/cek-status.blade.php` dan `resources/views/admin/pembayaran.blade.php`, lalu ganti teks tampilannya tanpa mengubah nilai data.

**Hasil yang diharapkan.** Label berubah di kedua halaman, sedangkan nilai pada database tetap `WAITING_VERIFICATION`.

## 5.5 Memperbaiki Error Sederhana pada Controller

**Tugas.** Perbaiki controller yang memanggil method yang tidak ada.

**Langkah.** Jalankan `php artisan route:list` lalu periksa route `PUT api/bookings/{code}/status` yang menunjuk `ApiBookingController@updateStatus`. Method tersebut belum ada. Perbaikan minimal: hapus route tersebut sampai fiturnya diimplementasikan, atau tambahkan method `updateStatus` yang memvalidasi `service_status` dan memanggil logika yang sama dengan `BookingController@updateBookingStatus`.

**Hasil yang diharapkan.** Tidak ada lagi route yang menunjuk method yang tidak ada; permintaan ke route tersebut tidak menghasilkan kesalahan 500.

## 5.6 Menampilkan Data dari Database

**Tugas.** Tampilkan jumlah booking hari ini pada dashboard Admin.

**Langkah.** Pada `AdminPanelController@dashboard`, tambahkan `'today' => DB::table('bookings')->whereDate('created_at', now()->toDateString())->count(),` ke array `$stats`, lalu tampilkan pada kartu statistik di `resources/views/admin/dashboard-admin.blade.php` dengan `$stats['today']`.

**Hasil yang diharapkan.** Kartu baru menampilkan jumlah booking yang dibuat hari ini.

## 5.7 Mengubah Query Laporan

**Tugas.** Batasi laporan pemasukan hanya untuk metode QRIS secara default.

**Langkah.** Pada `ReportController@paymentMethod`, ubah nilai default sehingga mengembalikan `'QRIS'` ketika parameter `method` kosong.

**Hasil yang diharapkan.** Halaman laporan pemasukan hanya menampilkan transaksi QRIS, dan filter `method=CASH` tetap berfungsi bila dipilih.

# BAGIAN VI - KARTU HAFALAN

## 6.1 Kartu Hafalan Davin (Backend)

| Topik | Yang harus diingat |
| --- | --- |
| Entry point | `public/index.php`, konfigurasi di `bootstrap/app.php` |
| Route | `routes/web.php` (42 route) dan `routes/api.php` (1 endpoint); total 48 route termasuk 5 route bawaan framework |
| Middleware | `auth` dan alias `role` menuju `RoleMiddleware` |
| Booking | `BookingController@store`: transaksi + `lockForUpdate()` + Service ID unik |
| Service ID | `FP-` + `Ymd` + 5 karakter acak, kolom unique |
| Terima booking | `BookingController@verifyBooking` membuat `service_records` |
| Proses servis | `BookingController@updateBookingStatus`, status tidak bisa mundur |
| Konfirmasi bayar | `AdminPanelController@markPaid`; QRIS butuh bukti, CASH butuh `cash_received` |
| Upload bukti | `StatusController@uploadProof`: gambar, maks 2 MB, disk `public` |
| Batal | `StatusController@cancel` (pelanggan), `AdminPanelController@cancelBooking` (admin) |
| Laporan | `ReportController`: `serviceHistory`, `income`, varian PDF dan Excel |
| Password | `Hash::make`, bcrypt rounds 12, `$hidden` pada model |

## 6.2 Kartu Hafalan Lutvi (Frontend)

| Topik | Yang harus diingat |
| --- | --- |
| Layout | `layouts/app.blade.php` (publik), `layouts/admin.blade.php` (panel) |
| Section | `@section('content')` untuk publik, `@section('admin-content')` untuk panel |
| Partial | `admin/partials/sidebar`, `cancel-booking-modal`, `qris-payment-proofs` |
| Form | `@csrf`, `@method`, `route()`, `enctype` untuk unggah berkas |
| Flash | `session('success')`, `session('error')`, `$errors->any()` |
| Data | Dikirim controller lewat `compact()`; contoh `$stats`, `$bookings`, `$paymentProofs` |
| Badge status | `badge-blue`, `badge-green`, `badge-yellow`, `badge-red`, `badge-gray` |
| Responsif | Media query 1100 px, 800 px, 520 px |
| JavaScript | Toggle password, toggle sidebar, modal pembatalan |
| Cetak | `struk-pembayaran.blade.php` dengan `@media print` |

# BAGIAN VII - DAFTAR PERIKSA KESIAPAN

## 7.1 Persiapan Teknis

| No | Butir | Status |
| --- | --- | --- |
| 1 | Dapat menjalankan aplikasi lokal (`php artisan serve`) | Perlu dilatih |
| 2 | Dapat membuat booking dan menyebutkan Service ID yang terbentuk | Perlu dilatih |
| 3 | Dapat menjelaskan setiap tabel dan relasinya tanpa membuka file | Perlu dilatih |
| 4 | Dapat menunjukkan `RoleMiddleware` dan menjelaskan cara kerjanya | Perlu dilatih |
| 5 | Dapat melakukan alur lengkap: terima booking, diagnosis, selesai, bayar | Perlu dilatih |
| 6 | Dapat menunjukkan lokasi file untuk setiap fitur yang ditanyakan | Perlu dilatih |
| 7 | Dapat menjalankan `php artisan test` dan menjelaskan hasilnya | Perlu dilatih |
| 8 | Dapat menjelaskan tindakan bila internet terputus | Perlu dilatih |

## 7.2 Berkas Bukti yang Wajib Dibawa

| No | Berkas | Lokasi |
| --- | --- | --- |
| 1 | Dokumentasi teknis DOCX dan PDF | `docs/03-manual/` |
| 2 | Dokumentasi API | `docs/04-api/API_DOCUMENTATION.md` |
| 3 | Log perbaikan bug | `docs/01-release/BUG_FIXING_LOG.md` |
| 4 | Panduan deployment dan offline | `docs/02-deployment/` |
| 5 | README repository | `README.md` |
| 6 | Skrip mode offline | `run_local_offline.bat` |

## 7.3 Catatan Kejujuran

Bila penguji menanyakan fitur yang tidak ada, jawablah bahwa fitur tersebut di luar ruang lingkup, bukan mengarang. Contoh yang harus dijawab jujur: belum ada notifikasi otomatis, belum ada integrasi payment gateway, `GET /api/schedules` dan dua endpoint API lain belum diimplementasikan, dan deployment publik belum dapat diverifikasi dari repository ini.
