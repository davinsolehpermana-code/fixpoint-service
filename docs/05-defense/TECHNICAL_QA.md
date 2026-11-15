# TECHNICAL Q&A - PERTANGGUNGJAWABAN INDIVIDU FIXPOINT SERVICE

**Proyek:** FixPoint Service - Sistem Informasi Booking dan Manajemen Jasa Servis Laptop dan PC
**Tahap:** S4-08 Kesiapan Pertanggungjawaban Individu
**Tanggal:** 15 November 2026
**Untuk:** Davin Soleh Permana (Backend) dan Lutvi (Frontend)

---

## Cara memakai dokumen ini

1. Baca Bagian 1 agar dapat menjelaskan sistem dari sisi bisnis tanpa membuka kode.
2. Latih Bagian 2 (sepuluh pertanyaan wajib) sampai dapat menjawab tanpa membaca.
3. Gunakan Bagian 3 atau 4 sesuai peran, sambil membuka file yang disebutkan.
4. Kerjakan Bagian 5 di laptop masing-masing sebelum hari pengujian.
5. Hafalkan Bagian 6 (kartu ringkas) pada hari-H.

Setiap jawaban pada dokumen ini mengacu pada kode yang benar-benar ada di repository. Bila penguji menanyakan hal yang tidak ada, jawab jujur bahwa itu di luar lingkup.

---

## Bagian 1 - Pemahaman Sistem

### 1.1 Apa itu FixPoint Service?

Aplikasi web untuk UMKM jasa servis laptop dan PC. Pelanggan memesan jadwal servis tanpa membuat akun, mendapat Service ID, lalu memakai kode itu untuk memeriksa progres. Admin mengelola servis dan pembayaran; Pemilik Usaha membaca laporan.

### 1.2 Masalah apa yang diselesaikan?

Pencatatan manual membuat status servis sering ditanyakan ulang, nomor servis tertukar, bukti QRIS sulit dicocokkan, dan laporan pemasukan dihitung ulang. Aplikasi menyimpan semuanya pada satu database sehingga pelanggan, admin, dan pemilik melihat data yang sama.

### 1.3 Siapa aktornya?

Tiga aktor: Pelanggan (tanpa login), Admin (login, role `ADMIN`), Pemilik Usaha (login, role `PEMILIK_USAHA`). Teknisi bukan aktor pengguna sistem.

### 1.4 Bagaimana alur bisnisnya dari awal sampai akhir?

1. Pelanggan melihat layanan dan jadwal di beranda serta halaman ketersediaan jadwal.
2. Pelanggan mengisi formulir booking.
3. Sistem membuat pelanggan (bila baru), perangkat, booking `PENDING`, dan pembayaran `UNPAID`, lalu menampilkan Service ID.
4. Admin menerima booking: status `CONFIRMED` dan catatan servis dibuat.
5. Admin mengisi diagnosis dan estimasi, status `PROCESS`, lalu biaya akhir dan status `COMPLETED`.
6. Pelanggan membayar (CASH di toko atau QRIS lalu unggah bukti).
7. Admin mengonfirmasi pembayaran: status `PAID`, struk dapat dicetak.
8. Pemilik Usaha membaca laporan pemasukan dan riwayat servis.

### 1.5 Bagaimana arsitektur MVC-nya?

Model di `app/Models` memetakan tabel. Controller di `app/Http/Controllers` menerima request, validasi, query, lalu memilih view. View Blade di `resources/views` dirender menjadi HTML. Route di `routes/web.php` menghubungkan URL, middleware, dan controller. Konfigurasi aplikasi ada di `bootstrap/app.php`.

### 1.6 Bagaimana alur request dari browser sampai tampil?

`public/index.php` -> `bootstrap/app.php` (routing, middleware `web`/`auth`/`role`) -> controller -> model/Query Builder ke MySQL -> view Blade -> HTML kembali ke browser. Bila terjadi `QueryException` dan `APP_DEBUG=false`, pengguna menerima halaman 500 tanpa detail query.

### 1.7 Tabel apa saja yang ada?

`users`, `customers`, `devices`, `services`, `schedules`, `bookings`, `service_records`, `payments`, dan `personal_access_tokens` (bawaan Sanctum). Relasi utama: satu pelanggan banyak perangkat dan booking; satu booking satu layanan, satu jadwal, paling banyak satu catatan servis dan satu pembayaran.

---

## Bagian 2 - Sepuluh Pertanyaan Teknis Wajib

### Q1. Bagaimana sistem menangani booking agar slot jadwal tidak terpakai tidak semestinya?

**Jawaban lisan.** Kuota dijaga di dalam transaksi database. Baris jadwal dikunci, sisa kuota diperiksa ulang tepat sebelum disimpan, lalu kuota ditambah. Halaman form hanya menampilkan jadwal yang masih tersedia, tetapi keputusan akhir tetap di server.

**Teknis.** `BookingController@store` memakai `DB::transaction()` + `Schedule::...->lockForUpdate()`. Bila `status !== 'AVAILABLE'` atau `current_booking >= max_booking`, proses dibatalkan dan pengguna menerima pesan. Setelah booking tersimpan, `current_booking` dinaikkan; bila penuh, status jadwal menjadi `FULL`.

**Lokasi.** `app/Http/Controllers/BookingController.php` (method `store`).

```php
$schedule = Schedule::query()
    ->where('schedule_id', $validated['schedule_id'])
    ->whereDate('schedule_date', '>=', now()->toDateString())
    ->lockForUpdate()
    ->first();
```

### Q2. Mengapa Database Transaction diperlukan saat satu proses melibatkan beberapa tabel?

**Jawaban lisan.** Karena satu booking menyentuh empat tabel sekaligus. Bila satu langkah gagal, data setengah jadi membuat laporan salah dan kuota jadwal berkurang tanpa booking. Transaction menjamin semua berhasil atau semua dibatalkan.

**Teknis.** Yang dibungkus transaksi di `BookingController@store`: `customers`, `devices`, `bookings`, `payments`, dan `schedules`. Exception di tengah proses menyebabkan rollback otomatis. Pola sama dipakai `StatusController@cancel` (booking + slot), `StatusController@uploadProof` (berkas + status), `AdminPanelController@cancelBooking`, dan `BookingController@updateBookingStatus`.

**Lokasi.** `BookingController`, `StatusController`, `AdminPanelController`, `PaymentProofController`.

### Q3. Bagaimana aplikasi menyimpan password Admin dan Pemilik Usaha?

**Jawaban lisan.** Password di-hash dengan bcrypt sebelum disimpan, jadi isi tabel tidak bisa dibaca sebagai password. Saat login, sistem membandingkan input dengan hash tersebut.

**Teknis.** `Hash::make()` saat membuat akun (`DatabaseSeeder`, `UserController@store`), `Auth::attempt()` saat login (di dalamnya `Hash::check()`). `User::$hidden` menyembunyikan `password`. `BCRYPT_ROUNDS=12`.

**Lokasi.** `app/Models/User.php`, `app/Http/Controllers/AuthController.php`, `database/seeders/DatabaseSeeder.php`.

### Q4. Bagaimana aplikasi menghindari SQL Injection?

**Jawaban lisan.** Semua nilai dari pengguna dikirim sebagai parameter terikat, bukan ditempel ke teks perintah SQL.

**Teknis.** Eloquent dan Query Builder memakai prepared statement. Pencarian memakai `'%' . $search . '%'` sebagai nilai, bukan interpolasi string SQL. Tidak ada `DB::raw()` yang menerima input pengguna.

**Lokasi.** `AdminPanelController@serviceProcess`, `@customers`, `@devices`, `@payments`; `ReportController`.

```php
$query->where(function ($subQuery) use ($search) {
    $subQuery->where('b.service_id_code', 'like', '%' . $search . '%')
        ->orWhere('c.name', 'like', '%' . $search . '%');
});
```

### Q5. Apa perbedaan validasi browser dan validasi Laravel di server?

**Jawaban lisan.** Validasi browser hanya kenyamanan; bisa dilewati. Validasi server adalah yang menentukan data boleh masuk database.

**Teknis.** HTML `required`/`maxlength` ada di `resources/views/booking/booking.blade.php`. Aturan sebenarnya di `$request->validate()` (`BookingController@store`) dan `Validator::make()` (`ApiBookingController@store`). Kegagalan menghasilkan redirect dengan `$errors` untuk web, atau HTTP 422 dengan `errors` untuk JSON.

**Lokasi.** `BookingController@store`, `ApiBookingController@store`.

### Q6. Bagaimana sistem melindungi proses upload bukti pembayaran QRIS?

**Jawaban lisan.** Berkas harus benar-benar gambar dengan format dan ukuran tertentu, disimpan dengan nama acak di luar folder publik, dan hanya dapat dibuka lewat route yang memeriksa hak akses.

**Teknis.** Validasi `image`, `mimes:jpeg,png,jpg,webp`, `mimetypes:image/jpeg,image/png,image/webp`, `max:2048`. Disimpan dengan `store('payment_proofs','public')` di `storage/app/public`. Penyajian melalui `PaymentProofController` yang memeriksa MIME, booking `COMPLETED`, metode QRIS, dan mengirim header `nosniff`, `no-store`, `inline`. Berkas baru dihapus bila transaksi gagal.

**Lokasi.** `StatusController@uploadProof`, `PaymentProofController@streamProof`.

### Q7. Jelaskan relasi tabel dan penerapan normalisasi.

**Jawaban lisan.** Setiap entitas punya tabelnya sendiri dan dihubungkan dengan id, sehingga tidak ada data yang diulang. Nama layanan hanya ada di tabel layanan; booking hanya menyimpan `service_id`.

**Teknis.** 1NF: nilai atomik per kolom. 2NF: primary key satu kolom (`booking_id`, `payment_id`), sehingga tidak ada ketergantungan parsial. 3NF: atribut non-kunci bergantung pada primary key tabelnya, mis. `final_cost` hanya di `service_records`. Denormalisasi terkontrol: `payments.amount` menyalin biaya akhir karena nominal transaksi harus tetap sebagai nilai historis.

**Lokasi.** `database/migrations/*`.

### Q8. Apa yang dilakukan bila internet terputus saat aplikasi dipresentasikan?

**Jawaban lisan.** Jalankan `run_local_offline.bat` di laptop, lalu perangkat penguji membuka alamat IP LAN yang muncul. Semua data ada di MySQL lokal.

**Teknis.** Skrip memeriksa PHP, menyalakan MySQL, memeriksa `.env` dan database, `php artisan optimize:clear`, menyiapkan storage, membaca IPv4 aktif, lalu `php artisan serve --host 0.0.0.0 --port 8000`. Bila perangkat lain tidak bisa terhubung: periksa `netstat` (harus `0.0.0.0:8000`), izinkan port 8000 di firewall, pastikan profil jaringan Private, dan SSID sama.

**Lokasi.** `run_local_offline.bat`, `docs/02-deployment/OFFLINE_DEPLOYMENT.md`.

### Q9. Bagaimana pembagian tanggung jawab Davin dan Lutvi?

**Jawaban lisan.** Davin menangani database, model, controller, routing, middleware, validasi, transaksi, pembayaran, dan laporan. Lutvi menangani seluruh Blade, form, CSS, JavaScript, dashboard, dan responsivitas. Titik temu keduanya adalah nama route, nama field form, dan nama variabel yang dikirim controller.

**Teknis.** Davin: `database/`, `app/Models`, `app/Http/Controllers`, `routes/`, `app/Http/Middleware`, `app/Support`, `tests/Feature`. Lutvi: `resources/views/layouts`, `resources/views/**/*.blade.php`, CSS di dalam Blade, JavaScript interaksi, `admin/partials`, `pemilik/partials`.

### Q10. Apa kendala implementasi saat dipakai UMKM dan solusinya?

**Jawaban lisan.** Kendala terbesar adalah kebiasaan manual, kelengkapan data pelanggan, dan kualitas foto bukti bayar.

**Teknis dan solusi yang sudah ada.**

| Kendala | Penanganan |
| --- | --- |
| Pelanggan lupa Service ID | Ditampilkan setelah booking, dapat dicari di `/cek-status`, format mudah dibaca |
| Admin salah menandai selesai | Status tidak dapat mundur; `COMPLETED` wajib biaya akhir > 0 |
| Bukti transfer buram/terlalu besar | Maksimal 2 MB, hanya JPG/PNG/WEBP, pesan kesalahan jelas |
| Cash tanpa uang diterima | `cash_received` wajib dan tidak boleh kurang dari tagihan |
| Internet toko tidak stabil | Mode offline LAN |
| Data sensitif di hosting | `.env` di luar akses browser, bukti bayar dialirkan lewat controller |
| Pelanggan menanyakan status di luar jam kerja | Halaman cek status dapat diakses kapan saja tanpa login |

---

## Bagian 3 - Pertanggungjawaban Davin (Backend)

### 3.1 Mengapa primary key bukan `id`?

Karena mengikuti nama kolom ERD. Model menetapkan `protected $primaryKey`, dan foreign key menyebut kolom tujuan: `->constrained('customers','customer_id')`. Bila memakai `id`, seluruh query dan relasi harus diubah.

**File:** `database/migrations/0001_01_01_000000_create_users_table.php`, `app/Models/*.php`.

### 3.2 Tunjukkan satu relasi dan cara memakainya.

`Booking::payment()` (`hasOne`). Dipakai dengan eager loading di `StatusController@show`: `Booking::with(['customer','device','schedule','service','payment'])`. Eager loading mencegah N+1 query.

**File:** `app/Models/Booking.php`, `app/Http/Controllers/StatusController.php`.

### 3.3 Mengapa `POST /admin/booking/update` memakai Service ID, bukan ID database?

Karena Admin bekerja dari Service ID yang tertulis pada catatan/struk. Controller memvalidasi `exists:bookings,service_id_code` lalu `lockForUpdate()`. Risikonya tetap tertutup karena kode bersifat unik.

**File:** `routes/web.php`, `BookingController@updateBookingStatus`.

### 3.4 Bagaimana Anda mencegah Pemilik membuka halaman Admin?

Route grup Admin memakai `middleware(['auth','role:ADMIN'])`. `RoleMiddleware` membandingkan `strtoupper($user->role)` dengan daftar role dan memanggil `abort(403)`. Terbukti lulus pada `test_pemilik_tidak_bisa_membuka_halaman_admin`.

**File:** `app/Http/Middleware/RoleMiddleware.php`, `tests/Feature/Sprint3AcceptanceTest.php`.

### 3.5 Mengapa ada dua method `markPaid`?

`AdminPanelController@markPaid` adalah yang dipakai route aktif (`PUT /admin/booking/{id}/mark-paid`) dan memuat aturan lengkap QRIS/CASH. `BookingController@markPaid` adalah versi Eloquent yang lebih lama dan tidak lagi dirujuk route mana pun. Keduanya tidak saling mengganggu, tetapi versi lama dapat dibersihkan pada iterasi berikutnya (dicatat di `BUG_FIXING_LOG.md`).

### 3.6 Bagaimana Service ID dijamin unik?

Dua lapis: perulangan `do { ... } while (Booking::where('service_id_code', $serviceIdCode)->exists())` dan constraint `unique` pada kolom. Bila terjadi tabrakan pada saat bersamaan, database tetap menolak dan transaksi rollback.

### 3.7 Bagaimana alur status servis dijaga?

`updateBookingStatus` memakai peta `PENDING=0, DIAGNOSIS=1, PROCESS=2, COMPLETED=3`. Status baru yang lebih rendah ditolak dengan `ValidationException`. `COMPLETED` wajib memiliki `final_cost` lebih dari nol. Status pembayaran yang sudah `PAID` mengunci form ini.

### 3.8 Bagaimana laporan pemasukan menghindari data ganda?

Query hanya mengambil `payments` dengan `payment_status = PAID`, di-join ke `bookings`, dan dijumlahkan dalam satu agregasi `SUM`. Transaksi `UNPAID` dan `WAITING_VERIFICATION` tidak masuk hitungan. Diuji pada `test_laporan_pemasukan_hanya_menghitung_pembayaran_lunas`.

**File:** `app/Http/Controllers/ReportController.php` (`incomeQuery`, `incomeSummary`).

### 3.9 Bagaimana PDF dan Excel dibuat?

PDF melalui `Pdf::loadView('admin.exports...')->setPaper('a4','landscape')` dari dompdf. Excel melalui `App\Support\SimpleXlsx::download()` yang menulis zip + XML sendiri, sehingga tidak memerlukan PhpSpreadsheet.

### 3.10 Bagaimana penanganan error di level aplikasi?

`bootstrap/app.php` mendaftarkan render untuk `QueryException`: bila debug mati, exception dilaporkan ke log dan pengguna menerima halaman `errors/500` atau JSON ringkas. Halaman `403`, `404`, `419`, `503` juga tersedia di `resources/views/errors`.

---

## Bagian 4 - Pertanggungjawaban Lutvi (Frontend)

### 4.1 Mengapa memakai dua layout?

`layouts/app.blade.php` untuk halaman publik dan login; `layouts/admin.blade.php` untuk panel karena membutuhkan sidebar, kartu statistik, tabel, dan form. Halaman pelanggan tetap ringan.

### 4.2 Apa saja partial yang dibuat?

`admin/partials/sidebar.blade.php`, `admin/partials/cancel-booking-modal.blade.php`, `admin/partials/qris-payment-proofs.blade.php`, `pemilik/partials/bukti-pembayaran.blade.php`. Tujuannya menghindari duplikasi markup.

### 4.3 Bagaimana form dihubungkan ke controller?

`action="{{ route('nama.route') }}"`, `method="POST"`, `@csrf`, dan `@method('PUT'/'PATCH')` untuk update. Unggah berkas memakai `enctype="multipart/form-data"`. Contoh: konfirmasi pembayaran CASH mengirim `PUT` ke `admin.booking.markPaid` dengan field `cash_received`.

### 4.4 Bagaimana menampilkan pesan sukses dan gagal?

Layout admin memeriksa `session('success')` dan `session('error')` untuk alert hijau/merah, lalu `$errors->any()` untuk daftar kesalahan validasi. Halaman login menampilkan `$errors->first()` pada kotak merah di atas tombol masuk.

### 4.5 Bagaimana logika warna status dibuat?

Dengan kondisi Blade dan kelas badge: `in_array($booking->booking_status, ['PENDING','WAITING_VERIFICATION'], true)` menjadi `badge-yellow`; `['REJECTED','CANCELLED']` menjadi `badge-red`; `COMPLETED` menjadi `badge-green`; default `badge-gray`.

### 4.6 Mengapa CSS tidak memakai file terpisah atau Tailwind?

Karena aplikasi tidak memakai build Vite: tidak ada `@vite` di view. Semua gaya ditulis di dalam Blade sehingga deployment tidak memerlukan Node.js. Konsekuensinya, gaya bersama pada panel ditempatkan di layout admin dan hanya gaya khusus halaman ditulis di halamannya.

### 4.7 Bagaimana responsivitas diatur?

Media query pada layout admin: 1100 px (grid statistik menjadi dua kolom), 800 px (sidebar menjadi tombol menu lipat dan `.admin-nav` disembunyikan), 520 px (tabel/form satu kolom, tombol melebar). Halaman booking juga menyesuaikan pada layar kecil.

### 4.8 Bagaimana tampilan struk saat dicetak?

`admin/struk-pembayaran.blade.php` memakai lebar menyerupai printer 80 mm dan aturan `@media print` untuk menyembunyikan tombol, sehingga hasil cetak bersih.

### 4.9 Bagaimana data dari controller dipakai di view?

Controller mengirim variabel melalui `compact()` atau array kedua `view()`. Contoh `AdminPanelController@dashboard` mengirim `stats`, `recentBookings`, `upcomingSchedules`, `recentQrisProofs`; view memakai `$stats['need_action']` dan `foreach ($recentBookings as $booking)`. Bila nama variabel diubah di controller, view harus disesuaikan.

### 4.10 Bagaimana Anda memastikan form tidak mengirim data ganda?

Tombol submit tidak dinonaktifkan otomatis, sehingga pengaman data ganda berada di sisi server: kunci transaksi, pemeriksaan status, dan `unique` pada kolom kunci. Untuk pembayaran, status yang sudah `PAID` ditolak.

---

## Bagian 5 - Latihan Live Coding

Semua latihan dikerjakan pada salinan lokal, bukan untuk di-commit. Setiap latihan menyebut file, perubahan, dan hasil yang diharapkan.

### Latihan 1 - Menambah validasi

- **File:** `app/Http/Controllers/BookingController.php`
- **Perubahan:** pada aturan `nama` tambahkan `'min:3'` dan pesan `'nama.min' => 'Nama minimal 3 karakter.'`
- **Uji:** kirim booking dengan nama `Al`.
- **Hasil:** kembali ke form dengan pesan pada field nama; tabel `bookings` tidak bertambah.

### Latihan 2 - Mengubah pengurutan

- **File:** `app/Http/Controllers/AdminPanelController.php` (method `payments`)
- **Perubahan:** `->orderByDesc('p.payment_id')` menjadi `->orderByDesc('p.payment_date')`
- **Hasil:** transaksi terbaru berdasarkan tanggal bayar tampil paling atas.

### Latihan 3 - Mengubah kondisi tampilan

- **File:** `resources/views/admin/kelola-proses-servis.blade.php`
- **Perubahan:** tambahkan kondisi `COMPLETED` menghasilkan `badge-gray` sebelum kondisi `PENDING`.
- **Hasil:** baris selesai berwarna abu-abu.

### Latihan 4 - Mengubah label status

- **File:** `resources/views/status/cek-status.blade.php`, `resources/views/admin/pembayaran.blade.php`
- **Perubahan:** label tampilan `WAITING_VERIFICATION` menjadi `Menunggu Verifikasi Bukti` tanpa mengubah nilai data.
- **Hasil:** label berubah, data database tetap sama.

### Latihan 5 - Memperbaiki route bermasalah

- **Masalah:** sebelum Sprint 4, `routes/api.php` memuat route yang menunjuk method tidak ada sehingga HTTP 500.
- **Perbaikan yang dipilih:** menghapus route yang belum berfungsi dan mempertahankan `POST /api/bookings`.
- **Verifikasi:** `php artisan route:list` tidak lagi menampilkan route tersebut; `php artisan test` tetap lulus.
- **Catatan:** perbaikan ini sudah diterapkan pada repository, sehingga latihan ini berupa penjelasan, bukan perubahan baru.

### Latihan 6 - Menampilkan data tambahan di dashboard

- **File:** `app/Http/Controllers/AdminPanelController.php` (method `dashboard`)
- **Perubahan:** tambahkan `'today' => DB::table('bookings')->whereDate('created_at', now()->toDateString())->count(),` ke `$stats`.
- **File view:** `resources/views/admin/dashboard-admin.blade.php`, tampilkan `$stats['today']` pada kartu statistik.
- **Hasil:** kartu baru menampilkan jumlah booking hari ini.

### Latihan 7 - Mengubah query laporan

- **File:** `app/Http/Controllers/ReportController.php` (method `paymentMethod`)
- **Perubahan:** kembalikan `'QRIS'` ketika parameter `method` kosong.
- **Hasil:** laporan pemasukan default hanya QRIS; filter CASH tetap bekerja.

---

## Bagian 6 - Kartu Hafalan

### Davin (Backend)

| Ingat | Isi |
| --- | --- |
| Entry point | `public/index.php`, konfigurasi `bootstrap/app.php` |
| Middleware | `web` (session+CSRF), `auth`, `role` -> `RoleMiddleware` |
| Booking | `BookingController@store`: transaksi + `lockForUpdate` + Service ID unik |
| Terima booking | `verifyBooking` -> buat `service_records`, status `CONFIRMED` |
| Proses | `updateBookingStatus`: diagnosis, estimasi, `PROCESS`, `COMPLETED` + biaya akhir |
| Bayar | `AdminPanelController@markPaid`: QRIS butuh bukti, CASH butuh `cash_received` |
| Bukti | `StatusController@uploadProof`, `PaymentProofController@streamProof` |
| Batal | `StatusController@cancel`, `AdminPanelController@cancelBooking` |
| Laporan | `ReportController`: `serviceHistory`, `income`, PDF (dompdf), XLSX (`SimpleXlsx`) |
| Uji | `php artisan test` -> 30 lulus |

### Lutvi (Frontend)

| Ingat | Isi |
| --- | --- |
| Layout | `layouts/app` (publik), `layouts/admin` (panel) |
| Section | `content` untuk publik, `admin-content` untuk panel |
| Form | `@csrf`, `@method`, `route()`, `enctype` untuk unggah |
| Flash | `session('success')`, `session('error')`, `$errors` |
| Partial | `sidebar`, `cancel-booking-modal`, `qris-payment-proofs`, `bukti-pembayaran` |
| Badge | biru, hijau, kuning, merah, abu-abu |
| Responsif | 1100 px, 800 px, 520 px |
| JS | toggle password, toggle sidebar, modal pembatalan |
| Cetak | `struk-pembayaran.blade.php` dengan `@media print` |
| Tanpa build | Semua CSS di Blade, tidak ada `@vite` |

---

## Bagian 7 - Daftar Periksa Hari-H

| No | Butir | Davin | Lutvi |
| --- | --- | --- | --- |
| 1 | Dapat menjalankan `php artisan serve` dan membuka aplikasi | Ya | Ya |
| 2 | Dapat membuat booking dan menyebut Service ID | Ya | Ya |
| 3 | Dapat menjelaskan tabel dan relasi tanpa membuka file | Ya | Cukup |
| 4 | Dapat menjelaskan alur request dan middleware | Ya | Cukup |
| 5 | Dapat menunjukkan file untuk setiap fitur | Ya | Ya |
| 6 | Dapat menjelaskan keputusan CSS di dalam Blade | - | Ya |
| 7 | Dapat menjelaskan mode offline | Ya | Ya |
| 8 | Dapat menjawab pertanyaan yang tidak ada di dokumen dengan jujur | Ya | Ya |

## Bagian 8 - Hal yang Harus Dijawab Jujur

| Pertanyaan penguji | Jawaban yang benar |
| --- | --- |
| Apakah aplikasi sudah online di hosting? | Belum. Paket production dan panduan sudah siap, tetapi unggah memerlukan akun hosting. |
| Apakah ada notifikasi WhatsApp/email? | Belum ada, di luar ruang lingkup. |
| Apakah ada integrasi payment gateway? | Belum. QRIS diproses manual dengan gambar QRIS toko dan unggah bukti. |
| Apakah semua endpoint API berfungsi? | Tidak. Hanya `POST /api/bookings`; endpoint lain dihapus pada Sprint 4 karena belum diimplementasikan. |
| Apakah ada teknisi sebagai aktor? | Tidak. Teknisi bukan pengguna sistem; pekerjaan teknis dilakukan Admin. |
| Apakah ada pengujian browser otomatis? | Belum. Pengujian saat ini Feature Test PHPUnit (30 pengujian). |
| Apakah `migrate:fresh` boleh dipakai? | Tidak pada database berisi data. Gunakan database terpisah untuk pengujian destruktif. |
