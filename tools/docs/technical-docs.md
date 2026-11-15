---
title: Dokumentasi Teknis FixPoint Service
subtitle: Sistem Informasi Booking dan Manajemen Jasa Servis Laptop dan PC
doc_type: DOKUMEN TEKNIS (S4-03)
project: UKK RPL - Sprint 4 Finalization and Release Candidate
identity: FixPoint Service
version: 1.0.0-rc
date: 15 November 2026
---

# BAB I - INFORMASI PROYEK

## 1.1 Identitas Aplikasi

Dokumen ini menjelaskan aplikasi **FixPoint Service** sebagaimana yang benar-benar ada di dalam repository `fixpoint-service`. Seluruh isi dokumen disusun dari migration, model, controller, route, view, dan konfigurasi yang ada pada repository, bukan dari perkiraan.

| Item | Keterangan |
| --- | --- |
| Nama aplikasi | FixPoint Service |
| Judul proyek | Sistem Informasi Booking dan Manajemen Jasa Servis Laptop dan PC |
| Jenis aplikasi | Aplikasi web monolitik berbasis Laravel (Blade Template Engine) |
| Pemilik proses bisnis | UMKM jasa servis laptop dan PC |
| Basis data | MySQL / MariaDB, nama database pengembangan `fixpoint_service` |
| Tim pengembang | Davin Soleh Permana (Backend), Lutvi (Frontend) |
| Repository | `github.com/davinsolehpermana-code/fixpoint-service` |
| Versi dokumen | 1.0.0-rc |
| Tanggal penyusunan | 15 November 2026 |

## 1.2 Tujuan Aplikasi

FixPoint Service dibuat untuk membantu pemilik usaha jasa servis komputer mengelola proses bisnis harian yang sebelumnya dicatat secara manual. Tujuan yang benar-benar terwujud di dalam kode adalah sebagai berikut.

1. Menyediakan kanal booking daring bagi pelanggan tanpa mewajibkan registrasi akun.
2. Memberi identitas unik (Service ID) pada setiap booking sehingga pelanggan dapat memantau status servis perangkatnya kapan saja.
3. Menyimpan data pelanggan, data perangkat, dan keluhan kerusakan secara terpusat agar riwayat servis tidak hilang.
4. Mencatat proses diagnosis, estimasi biaya, biaya akhir, serta status pengerjaan servis.
5. Mencatat pembayaran dengan dua metode yang dipakai UMKM, yaitu tunai (CASH) dan QRIS, termasuk penyimpanan bukti transfer QRIS.
6. Menyediakan laporan riwayat servis dan laporan pemasukan yang dapat diekspor ke PDF dan Excel untuk keperluan pembukuan.

## 1.3 Latar Belakang

Praktik umum pada UMKM jasa servis komputer adalah pencatatan booking melalui pesan WhatsApp dan buku catatan. Pola ini menimbulkan beberapa masalah nyata: pelanggan harus berulang kali menanyakan status perangkat, nomor urut servis mudah tertukar, bukti pembayaran QRIS tersebar di galeri ponsel, dan laporan pemasukan bulanan harus dihitung ulang secara manual.

FixPoint Service menjawab masalah tersebut dengan satu alur terpadu: pelanggan mengisi formulir booking, sistem membuat Service ID, admin memproses servis dan menetapkan biaya, pelanggan mengunggah bukti pembayaran QRIS bila memilih metode tersebut, dan pemilik usaha membaca laporan pemasukan dari data yang sama. Karena tidak ada aplikasi bergerak maupun layanan pihak ketiga yang dipakai, sistem dapat dijalankan pada satu hosting PHP dan MySQL biasa.

## 1.4 Ruang Lingkup Sistem

Ruang lingkup yang sudah diimplementasikan:

- Halaman beranda yang menampilkan layanan dan jadwal servis yang masih tersedia.
- Halaman ketersediaan jadwal yang menampilkan status AVAILABLE, FULL, dan CLOSED.
- Formulir booking publik tanpa login.
- Pembuatan Service ID otomatis dengan pola `FP-YYYYMMDD-XXXXX`.
- Halaman cek status berdasarkan Service ID, termasuk riwayat status servis dan rincian pembayaran.
- Pembatalan booking oleh pelanggan selama status masih PENDING atau WAITING_VERIFICATION.
- Login Admin dan Pemilik Usaha dengan pemilihan role.
- Panel Admin: dashboard, penambahan dan penutupan jadwal, penerimaan booking, pengelolaan diagnosis, estimasi dan biaya akhir, konfirmasi pembayaran, pembatalan booking, data pelanggan, data perangkat, data layanan, struk pembayaran, serta laporan.
- Panel Pemilik Usaha: dashboard monitoring (read-only), riwayat servis, laporan pemasukan, dan akses baca bukti pembayaran QRIS.
- Ekspor laporan ke PDF (dompdf) dan Excel (XLSX yang dibangkitkan sendiri melalui `App\Support\SimpleXlsx`).
- Sekumpulan endpoint JSON terbatas di bawah prefix `api/` untuk pembuatan booking dan pengecekan status.

Di luar ruang lingkup (tidak diklaim ada):

- Aplikasi mobile atau PWA.
- Notifikasi WhatsApp, email, atau SMS otomatis.
- Integrasi payment gateway. QRIS diproses secara manual: pelanggan memindai gambar QRIS statis milik toko, lalu mengunggah bukti.
- Manajemen stok suku cadang dan manajemen teknisi sebagai aktor sistem.
- Multi-cabang atau multi-tenancy.

## 1.5 Aktor Aplikasi

Aplikasi hanya mengenal tiga aktor. Teknisi tidak dimodelkan sebagai aktor pengguna sistem; pekerjaan teknis dilakukan oleh Admin melalui panel administrasi.

| Aktor | Autentikasi | Hak akses utama |
| --- | --- | --- |
| Pelanggan | Tanpa login (identifikasi memakai Service ID) | Melihat layanan dan jadwal, membuat booking, mengecek status, mengunggah bukti pembayaran QRIS, membatalkan booking yang belum dikerjakan |
| Admin | Login (email, password, role `admin`) | Seluruh operasional: jadwal, booking, diagnosis, estimasi, biaya akhir, pembayaran, struk, data master, laporan |
| Pemilik Usaha | Login (email, password, role `pemilik`) | Monitoring dan laporan read-only: dashboard, riwayat servis, laporan pemasukan, pratinjau bukti pembayaran |

Pembatasan hak akses dijalankan oleh `App\Http\Middleware\RoleMiddleware` melalui alias middleware `role` yang didaftarkan di `bootstrap/app.php`.

## 1.6 Teknologi yang Digunakan

| Komponen | Versi / keterangan | Sumber |
| --- | --- | --- |
| PHP | `^8.3` pada `composer.json`; lingkungan pengembangan memakai PHP 8.4.26 | `composer.json`, `php -v` |
| Laravel Framework | constraint `^13.17`, terpasang **13.36.0** (`php artisan --version`) | `composer.json`, `composer.lock` |
| MySQL / MariaDB | Database `fixpoint_service` pada MySQL lokal (XAMPP) | `.env`, `php artisan migrate:status` |
| Blade | Template engine bawaan Laravel untuk seluruh tampilan | `resources/views` |
| Tailwind/Vite | Terdaftar di `package.json`, tetapi **tidak dipakai** oleh view: tidak ada direktif `@vite` di `resources/views`, seluruh CSS ditulis langsung di dalam file Blade | hasil pencarian `@vite` pada `resources/views` |
| barryvdh/laravel-dompdf | `^3.1`, dipakai untuk ekspor PDF laporan | `composer.json`, `ReportController` |
| laravel/sanctum | `^4.0`, terpasang melalui skeleton; belum dipakai untuk autentikasi API | `composer.json` |
| laravel/tinker | `^3.0`, alat bantu pengembangan | `composer.json` |
| PHPUnit | `^12.5.12`, pengujian otomatis | `composer.json`, `tests/` |
| Git dan GitHub | Version control dan remote `origin` | `.git`, `git remote -v` |

# BAB II - ARSITEKTUR SISTEM

## 2.1 Pola Laravel MVC

FixPoint Service mengikuti pola Model-View-Controller bawaan Laravel. Aplikasi berjalan sebagai satu proses PHP di sisi server; tidak ada frontend framework terpisah dan tidak ada proses build JavaScript yang wajib dijalankan sebelum aplikasi dapat dipakai.

- **Model** (`app/Models`) memetakan tabel MySQL dan menyimpan relasi Eloquent.
- **View** (`resources/views`) berisi berkas Blade yang dirender di server menjadi HTML.
- **Controller** (`app/Http/Controllers`) menerima request, memvalidasi input, menjalankan query, lalu mengembalikan view atau redirect.
- **Route** (`routes/web.php`, `routes/api.php`) menjadi penghubung antara URL dan controller.

Sebagian controller menggunakan Query Builder (`DB::table(...)`) alih-alih Eloquent untuk laporan dan dashboard, karena laporan memerlukan join beberapa tabel sekaligus dan hanya membaca data. Pendekatan ini konsisten di `AdminPanelController`, `ReportController`, dan closure dashboard Pemilik Usaha.

## 2.2 Hubungan Model, View, Controller

Alur hubungan antarbagian digambarkan sebagai berikut.

```
Browser (Blade + HTML/CSS/JS)
        |
        | HTTP request (GET/POST/PUT/PATCH)
        v
routes/web.php  --->  Middleware (auth, role, web: CSRF + session)
        |
        v
Controller (App\Http\Controllers)
        |
        +--> Model / Query Builder  --->  MySQL
        |         (Booking, Customer, Device, Payment, ServiceRecord, ...)
        |
        v
View Blade (resources/views)  --->  HTML response ke browser
```

Contoh nyata: `GET /cek-status/result?code=FP-20261115-ABCDE` diarahkan ke `StatusController@show`. Controller memvalidasi `code`, memuat `Booking` beserta relasi `customer`, `device`, `schedule`, `service`, dan `payment`, mengambil satu baris `service_records` melalui Query Builder, lalu mengembalikan `view('status.cek-status')`.

## 2.3 Alur Request

1. Request masuk ke `public/index.php` (satu-satunya pintu masuk aplikasi).
2. `bootstrap/app.php` membangun aplikasi: mendaftarkan `routes/web.php`, `routes/api.php`, `routes/console.php`, endpoint kesehatan `/up`, alias middleware `role`, dan penanganan exception.
3. Grup middleware `web` menjalankan pengelolaan session dan verifikasi token CSRF untuk request non-GET.
4. Route dicocokkan. Bila route berada dalam grup `auth`, pengguna yang belum login diarahkan ke halaman login. Bila berada dalam grup `role:ADMIN` atau `role:PEMILIK_USAHA`, `RoleMiddleware` memeriksa role pengguna dan mengembalikan HTTP 403 bila tidak sesuai.
5. Controller memvalidasi input memakai `$request->validate()` atau `Validator::make()`.
6. Controller menjalankan query; operasi multi-tabel dibungkus `DB::transaction()` dengan `lockForUpdate()` bila melibatkan kuota jadwal atau status pembayaran.
7. Controller mengembalikan `view(...)`, `redirect()->route(...)->with('success'|'error', ...)`, atau `response()->json(...)` untuk endpoint API.
8. `bootstrap/app.php` menangani exception: `QueryException` dilaporkan ke log dan ditampilkan sebagai halaman 500 tanpa detail query ketika `APP_DEBUG=false`.

## 2.4 Routing

Seluruh route web berada di `routes/web.php` (satu berkas) dan route JSON berada di `routes/api.php`. `php artisan route:list` menampilkan 48 route: 42 route web, 1 route JSON, dan 5 route bawaan framework (`/up`, `storage/{path}`, `sanctum/csrf-cookie`, `_boost/browser-logs`).

| Kelompok | Prefix | Middleware | Contoh route name |
| --- | --- | --- | --- |
| Publik | `/`, `/ketersediaan-jadwal`, `/booking`, `/cek-status` | `web` | `home`, `booking`, `cek-status` |
| Autentikasi | `/login`, `/logout` | `web` | `login`, `login.process`, `logout` |
| Admin | `/dashboard-admin`, `/admin/*` | `auth`, `role:ADMIN` | `admin.dashboard`, `admin.booking.update` |
| Pemilik Usaha | `/dashboard-pemilik`, `/pemilik/*` | `auth`, `role:PEMILIK_USAHA` | `pemilik.dashboard`, `pemilik.income` |
| API | `/api/*` | `api` (+ `auth`,`role:ADMIN` pada satu route) | `api/bookings` |

Tiga route utama menggunakan closure di dalam `routes/web.php`, yaitu beranda, ketersediaan jadwal, halaman booking, dan dashboard Pemilik Usaha. Closure dashboard pemilik memuat query statistik, daftar booking, riwayat servis, dan bukti pembayaran QRIS.

## 2.5 Middleware

| Middleware | Berkas | Fungsi |
| --- | --- | --- |
| `web` (bawaan) | `bootstrap/app.php` | Session, cookie, dan verifikasi CSRF |
| `auth` (bawaan) | `bootstrap/app.php` | Memastikan pengguna sudah login; mengarahkan ke `login` bila belum |
| `role` | `app/Http/Middleware/RoleMiddleware.php` | Membandingkan `strtoupper($user->role)` dengan daftar role yang diizinkan; `abort(403)` bila tidak cocok |
| `api` (bawaan) | `bootstrap/app.php` | Grup route JSON; pada proyek ini tetap memakai session untuk route yang dilindungi |

`RoleMiddleware` menerima daftar role sebagai parameter variadic sehingga satu middleware dapat dipakai untuk `role:ADMIN` maupun `role:PEMILIK_USAHA`.

## 2.6 Autentikasi

Autentikasi hanya berlaku untuk Admin dan Pemilik Usaha.

- Halaman login: `GET /login` ditangani `AuthController@showLogin`.
- Proses login: `POST /login` ditangani `AuthController@login`. Validasi: `email` wajib dan berformat email, `password` wajib, `role` wajib bernilai `admin` atau `pemilik`.
- Pemeriksaan kredensial memakai `Auth::attempt(['email' => ..., 'password' => ...])`.
- Setelah kredensial benar, `$request->session()->regenerate()` dipanggil untuk mencegah session fixation.
- Role pada tombol login dipetakan ke role database (`admin` menjadi `ADMIN`, `pemilik` menjadi `PEMILIK_USAHA`). Bila akun tidak memiliki role yang dipilih, pengguna langsung di-logout, session di-invalidate, dan pesan kesalahan ditampilkan pada form login.
- Redirect setelah login: Admin ke `admin.dashboard`, Pemilik Usaha ke `pemilik.dashboard`.
- Logout: `POST /logout` menangani `Auth::logout()`, `invalidate()`, dan `regenerateToken()`.

Pelanggan tidak memiliki akun. Identitas pelanggan diwakili oleh Service ID yang dibuat sistem, dan seluruh halaman pelanggan bersifat publik.

## 2.7 Otorisasi

Otorisasi dilakukan pada dua lapis.

1. **Lapis route**: grup route `role:ADMIN` dan `role:PEMILIK_USAHA` di `routes/web.php`. Contoh: `GET /admin/pembayaran` hanya untuk ADMIN; `GET /dashboard-pemilik` hanya untuk PEMILIK_USAHA.
2. **Lapis data**: query pada `ReportController` memakai `routePrefix()` yang membaca `auth()->user()->role` sehingga tautan laporan selalu mengarah ke route yang boleh dibuka oleh role tersebut. Tombol aksi (terima booking, ubah status, konfirmasi pembayaran) hanya dirender di view Admin.

Pemisahan hak akses diuji otomatis pada `tests/Feature/Sprint3AcceptanceTest.php`, misalnya `test_pemilik_tidak_bisa_membuka_halaman_admin` dan `test_admin_tidak_bisa_membuka_dashboard_pemilik`.

## 2.8 Integrasi Frontend dengan Backend

- **Blade sebagai satu-satunya lapisan tampilan.** Layout utama `resources/views/layouts/app.blade.php` dipakai halaman publik dan login, sedangkan `resources/views/layouts/admin.blade.php` dipakai panel Admin dan Pemilik Usaha.
- **CSS ditulis di dalam berkas Blade** (blok `<style>` per halaman atau per layout). Karena tidak ada `@vite` di view, aplikasi tidak membutuhkan `npm run build` saat dideploy. Berkas `resources/css/app.css` dan `resources/js/app.js` tetap ada sebagai sisa scaffolding Vite dan tidak dimuat oleh view mana pun.
- **Data dinamis** dikirim controller melalui `compact()` atau array kedua pada `view()`. Contoh: `AdminPanelController@dashboard` mengirim `stats`, `recentBookings`, `upcomingSchedules`, dan `recentQrisProofs`.
- **Form** selalu memakai `@csrf` dan method HTTP yang sesuai (`POST`, `PUT`, `PATCH`) melalui `@method`. Contoh form pembayaran di `resources/views/admin/pembayaran.blade.php` mengirim `PUT /admin/booking/{id}/mark-paid`.
- **Umpan balik** memakai session flash `success` dan `error`, serta `$errors` untuk kegagalan validasi; layout admin menampilkan ketiganya di bagian atas halaman.
- **JavaScript** hanya dipakai untuk interaksi kecil tanpa framework: tombol tampilkan password (`resources/views/login/login.blade.php`), pengalih menu sidebar pada layar kecil (`admin/partials/sidebar.blade.php`), dan dialog konfirmasi pembatalan booking.

# BAB III - PERANCANGAN DATABASE

## 3.1 Gambaran Umum

Basis data yang dipakai adalah MySQL. Proyek memiliki 10 migration: 9 migration pembentuk tabel aplikasi dan 1 migration penambahan indeks pelaporan. Selain tabel aplikasi, Laravel membuat tabel `personal_access_tokens` untuk Sanctum.

Semua tabel memakai primary key berupa kolom `*_id` bertipe `id()` (unsigned big integer auto increment), bukan kolom `id` bawaan Laravel, dan model terkait mendefinisikan `protected $primaryKey` sesuai nama kolom tersebut.

## 3.2 Entity Relationship Diagram

Relasi antartabel sesuai foreign key pada migration:

```
users (user_id)
  |  1
  |  *
bookings (booking_id) ----------< service_records (service_record_id)
  |  \                                    |
  |   \                                   | 1
  |    \                                  | 1
  |     *                                 *
  |   payments (payment_id) >-------------+ (service_record_id)
  |
  +--> customers (customer_id)
  |        |
  |        +--> devices (device_id)  <---- bookings.device_id
  |
  +--> services (service_id)
  |
  +--> schedules (schedule_id)
```

Keterangan: satu pelanggan (`customers`) memiliki banyak perangkat (`devices`) dan banyak booking (`bookings`). Satu booking menunjuk tepat satu layanan (`services`) dan satu jadwal (`schedules`), serta memiliki paling banyak satu catatan servis (`service_records`) dan paling banyak satu pembayaran (`payments`). Tabel `payments` juga menyimpan `customer_id` dan `service_record_id` agar tagihan dapat ditelusuri langsung.

## 3.3 Daftar Tabel dan Fungsi

| No | Tabel | Fungsi | Migration |
| --- | --- | --- | --- |
| 1 | `users` | Akun Admin dan Pemilik Usaha beserta role | `0001_01_01_000000_create_users_table.php` |
| 2 | `customers` | Identitas pelanggan (nama, nomor telepon) | `2026_10_06_163113_create_customers_table.php` |
| 3 | `services` | Daftar layanan servis beserta estimasi harga dan status aktif | `2026_10_06_164715_create_services_table.php` |
| 4 | `schedules` | Jadwal dan kuota slot servis | `2026_10_06_164817_create_schedules_table.php` |
| 5 | `devices` | Data perangkat pelanggan dan keluhan kerusakan | `2026_10_06_165000_create_devices_table.php` |
| 6 | `bookings` | Transaksi booking dan Service ID | `2026_10_07_073455_create_bookings_table.php` |
| 7 | `service_records` | Diagnosis, estimasi biaya, biaya akhir, status pengerjaan | `2026_10_07_073616_create_service_records_table.php` |
| 8 | `payments` | Tagihan, metode, status pembayaran, bukti QRIS | `2026_10_07_073714_create_payments_table.php` |
| 9 | `personal_access_tokens` | Tabel bawaan Laravel Sanctum | `2026_10_13_155659_create_personal_access_tokens_table.php` |
| 10 | Indeks pelaporan | Indeks tambahan pada kolom filter laporan | `2026_11_06_000000_add_reporting_indexes.php` |

## 3.4 Struktur Kolom Tabel Utama

### 3.4.1 Tabel `users`

| Kolom | Tipe | Constraint | Keterangan |
| --- | --- | --- | --- |
| `user_id` | bigint unsigned | PK, auto increment | Identitas akun |
| `email` | varchar(255) | unique, not null | Dipakai untuk login |
| `password` | varchar(255) | not null | Hash bcrypt hasil `Hash::make()` |
| `role` | enum | `ADMIN`, `PEMILIK_USAHA` | Menentukan hak akses |
| `created_at`, `updated_at` | timestamp | nullable | Waktu dibuat dan diubah |

### 3.4.2 Tabel `customers`

| Kolom | Tipe | Constraint | Keterangan |
| --- | --- | --- | --- |
| `customer_id` | bigint unsigned | PK | Identitas pelanggan |
| `name` | varchar(255) | not null | Nama pelanggan dari form booking |
| `phone_number` | varchar(255) | not null | Kunci pencocokan pelanggan berulang |
| `email` | varchar(255) | nullable | Tersedia pada struktur, belum diisi form booking |
| `address` | text | nullable | Tersedia pada struktur, belum diisi form booking |
| `created_at`, `updated_at` | timestamp | nullable | Waktu dibuat dan diubah |

### 3.4.3 Tabel `services`

| Kolom | Tipe | Constraint | Keterangan |
| --- | --- | --- | --- |
| `service_id` | bigint unsigned | PK | Identitas layanan |
| `name` | varchar(255) | not null | Nama layanan, mis. Servis Laptop |
| `description` | text | nullable | Penjelasan layanan |
| `estimated_price` | decimal(12,2) | nullable | Estimasi harga awal |
| `is_active` | boolean | default true | Layanan nonaktif tidak muncul di form booking |
| `created_at`, `updated_at` | timestamp | nullable | Waktu dibuat dan diubah |

### 3.4.4 Tabel `schedules`

| Kolom | Tipe | Constraint | Keterangan |
| --- | --- | --- | --- |
| `schedule_id` | bigint unsigned | PK | Identitas jadwal |
| `schedule_date` | date | not null | Tanggal slot servis |
| `start_time` | time | not null | Jam mulai |
| `end_time` | time | not null | Jam selesai |
| `max_booking` | integer | default 1 | Kuota maksimum slot |
| `current_booking` | integer | default 0 | Jumlah booking terpakai |
| `status` | enum | `AVAILABLE`, `FULL`, `CLOSED` | Ketersediaan slot |
| `created_at`, `updated_at` | timestamp | nullable | Waktu dibuat dan diubah |

### 3.4.5 Tabel `devices`

| Kolom | Tipe | Constraint | Keterangan |
| --- | --- | --- | --- |
| `device_id` | bigint unsigned | PK | Identitas perangkat |
| `customer_id` | bigint unsigned | FK ke `customers.customer_id`, cascade delete | Pemilik perangkat |
| `device_type` | varchar(255) | not null | `Laptop` atau `PC Desktop` |
| `brand` | varchar(255) | not null | Merek; diisi string kosong untuk PC desktop |
| `model` | varchar(255) | not null | Model; diisi string kosong untuk PC desktop |
| `complaint` | text | not null | Keluhan kerusakan dari pelanggan |
| `created_at`, `updated_at` | timestamp | nullable | Waktu dibuat dan diubah |

### 3.4.6 Tabel `bookings`

| Kolom | Tipe | Constraint | Keterangan |
| --- | --- | --- | --- |
| `booking_id` | bigint unsigned | PK | Identitas booking |
| `service_id_code` | varchar(255) | unique, not null | Service ID yang diberikan ke pelanggan |
| `customer_id` | bigint unsigned | FK `customers`, cascade delete | Pelanggan pemilik booking |
| `device_id` | bigint unsigned | FK `devices`, cascade delete | Perangkat yang diservis |
| `service_id` | bigint unsigned | FK `services`, restrict delete | Layanan yang dipilih |
| `schedule_id` | bigint unsigned | FK `schedules`, restrict delete | Slot jadwal yang dipesan |
| `handled_by` | bigint unsigned | FK `users.user_id`, nullable, null on delete | Admin yang menangani |
| `booking_date` | datetime | not null | Tanggal booking dibuat |
| `status` | enum | `PENDING`, `WAITING_VERIFICATION`, `CONFIRMED`, `REJECTED`, `COMPLETED`, `CANCELLED` | Status booking |
| `notes` | text | nullable | Catatan booking |
| `cancellation_reason` | text | nullable | Alasan pembatalan |
| `created_at`, `updated_at` | timestamp | nullable | Waktu dibuat dan diubah |

### 3.4.7 Tabel `service_records`

| Kolom | Tipe | Constraint | Keterangan |
| --- | --- | --- | --- |
| `service_record_id` | bigint unsigned | PK | Identitas catatan servis |
| `booking_id` | bigint unsigned | FK `bookings`, cascade delete | Booking yang ditangani |
| `handled_by` | bigint unsigned | FK `users.user_id`, restrict delete | Admin penanggung jawab |
| `diagnosis` | text | nullable | Hasil diagnosis kerusakan |
| `estimated_cost` | decimal(12,2) | nullable | Estimasi biaya |
| `final_cost` | decimal(12,2) | nullable | Biaya akhir yang ditagihkan |
| `service_status` | enum | `PENDING`, `DIAGNOSIS`, `PROCESS`, `COMPLETED` | Tahap pengerjaan |
| `service_notes` | text | nullable | Catatan pengerjaan dan estimasi waktu |
| `started_at` | datetime | nullable | Waktu mulai dikerjakan |
| `completed_at` | datetime | nullable | Waktu selesai |
| `created_at`, `updated_at` | timestamp | nullable | Waktu dibuat dan diubah |

### 3.4.8 Tabel `payments`

| Kolom | Tipe | Constraint | Keterangan |
| --- | --- | --- | --- |
| `payment_id` | bigint unsigned | PK | Identitas pembayaran |
| `customer_id` | bigint unsigned | FK `customers`, cascade delete | Pelanggan yang membayar |
| `booking_id` | bigint unsigned | FK `bookings`, nullable, unique, cascade delete | Satu booking satu pembayaran |
| `service_record_id` | bigint unsigned | FK `service_records`, nullable, cascade delete | Tagihan dihubungkan ke catatan servis |
| `processed_by` | bigint unsigned | FK `users.user_id`, nullable, restrict delete | Admin yang mengonfirmasi |
| `amount` | decimal(12,2) | not null | Nominal tagihan |
| `cash_received` | decimal(12,2) | nullable | Uang tunai yang diterima untuk metode CASH |
| `payment_method` | enum | `QRIS`, `CASH` | Metode pembayaran pilihan pelanggan |
| `payment_status` | enum | `UNPAID`, `WAITING_VERIFICATION`, `PAID` | Status pembayaran |
| `payment_proof` | varchar(255) | nullable | Path berkas bukti QRIS pada disk `public` |
| `payment_date` | datetime | nullable | Waktu unggah bukti atau konfirmasi |
| `notes` | text | nullable | Catatan Admin saat meminta unggah ulang |
| `created_at`, `updated_at` | timestamp | nullable | Waktu dibuat dan diubah |

## 3.5 Relasi dan Foreign Key

| Relasi | Tipe | Aturan hapus |
| --- | --- | --- |
| `customers` ke `devices` | one to many | cascade |
| `customers` ke `bookings` | one to many | cascade |
| `customers` ke `payments` | one to many | cascade |
| `devices` ke `bookings` | one to many | cascade |
| `services` ke `bookings` | one to many | restrict |
| `schedules` ke `bookings` | one to many | restrict |
| `users` ke `bookings.handled_by` | one to many | set null |
| `bookings` ke `service_records` | one to many | cascade |
| `bookings` ke `payments` | one to one (unique `booking_id`) | cascade |
| `users` ke `service_records.handled_by` | one to many | restrict |
| `service_records` ke `payments.service_record_id` | one to many | cascade |

Relasi Eloquent yang didefinisikan di model: `Booking::customer()`, `Booking::device()`, `Booking::service()`, `Booking::schedule()`, `Booking::payment()`, `Booking::serviceRecord()`, serta `Payment::customer()`, `Payment::booking()`, `Payment::serviceRecord()`, `Payment::processedBy()`, `ServiceRecord::booking()`, `ServiceRecord::handledBy()`, `ServiceRecord::payment()`.

## 3.6 Indeks Tambahan

Migration `2026_11_06_000000_add_reporting_indexes.php` menambahkan indeks untuk kolom yang paling sering dipakai filter dan pengurutan laporan. Migration ini memeriksa indeks yang sudah ada sehingga aman dijalankan pada database yang sudah berisi data.

| Tabel | Nama indeks | Kolom |
| --- | --- | --- |
| `payments` | `payments_status_date_index` | `payment_status`, `payment_date` |
| `payments` | `payments_method_index` | `payment_method` |
| `service_records` | `service_records_status_completed_index` | `service_status`, `completed_at` |
| `bookings` | `bookings_status_index` | `status` |
| `bookings` | `bookings_date_index` | `booking_date` |
| `schedules` | `schedules_date_status_index` | `schedule_date`, `status` |
| `customers` | `customers_phone_index` | `phone_number` |

## 3.7 Normalisasi Database

Struktur tabel memenuhi bentuk normal ketiga:

- **1NF (tanpa grup berulang).** Setiap kolom menyimpan satu nilai atomik. Keluhan tidak disimpan sebagai daftar di satu kolom, dan bukti pembayaran berupa satu path berkas per baris `payments`.
- **2NF (tidak ada ketergantungan parsial).** Seluruh tabel memakai primary key satu kolom (`*_id`) sehingga tidak ada atribut yang hanya bergantung pada sebagian kunci. Data perangkat dipisahkan dari `bookings` ke tabel `devices`, data pelanggan dipisahkan ke `customers` sehingga nama dan nomor telepon tidak diulang pada setiap booking.
- **3NF (tidak ada ketergantungan transitif).** Nama layanan dan estimasi harga hanya tersimpan di `services`; `bookings` menyimpan `service_id`. Status pengerjaan dan biaya hanya tersimpan di `service_records`, sedangkan `payments` menyimpan nominal tagihan yang disalin saat servis selesai agar nilai historis tagihan tidak berubah ketika estimasi layanan diperbarui.

Denormalisasi terkontrol yang disengaja: `payments` menyimpan `amount` hasil salinan `service_records.final_cost`. Nilai ini sengaja disalin karena nominal yang ditagihkan harus tetap tercatat walaupun biaya akhir diubah kemudian; salinan tersebut berfungsi sebagai nilai transaksi, bukan duplikasi data referensi.

## 3.8 Catatan Desain Database

- Proyek tidak membuat tabel `sessions`, `cache`, atau `jobs`. Karena itu `.env` memakai `SESSION_DRIVER=file`, `CACHE_STORE=file`, dan `QUEUE_CONNECTION=file/sync`. Penggunaan driver `database` untuk session akan gagal pada instalasi baru.
- Bukti pembayaran disimpan sebagai path relatif pada disk `public` (`storage/app/public/payment_proofs`), bukan sebagai BLOB di database.
- Alasan pembatalan disimpan pada `bookings.cancellation_reason` sehingga tidak perlu tabel pembatalan terpisah.
- Estimasi waktu pengerjaan dan catatan admin disimpan pada `service_records.service_notes` dengan format teks, karena migration tidak menyediakan kolom khusus untuk estimasi durasi.

# BAB IV - STRUKTUR SOURCE CODE

## 4.1 Direktori Utama

| Direktori | Isi dan fungsi |
| --- | --- |
| `app/Http/Controllers` | Seluruh controller web dan satu controller API |
| `app/Http/Middleware` | `RoleMiddleware` untuk pembatasan role |
| `app/Models` | Model Eloquent yang memetakan tabel |
| `app/Support` | Kelas bantu `SimpleXlsx` untuk membuat berkas Excel |
| `bootstrap` | `app.php` sebagai konfigurasi aplikasi (routing, middleware, exception) |
| `config` | Konfigurasi bawaan Laravel (app, database, session, filesystem, dll.) |
| `database/migrations` | Definisi skema database |
| `database/seeders` | `DatabaseSeeder` dan `UserSeeder` untuk data awal |
| `public` | Document root: `index.php`, `.htaccess`, gambar, QRIS, favicon |
| `resources/views` | Seluruh tampilan Blade |
| `routes` | `web.php`, `api.php`, `console.php` |
| `storage` | Log, cache framework, dan berkas unggahan (`app/public`) |
| `tests` | Pengujian PHPUnit (`tests/Feature`, `tests/Unit`) |
| `docs` | Dokumentasi Sprint 3 dan Sprint 4 |
| `deploy` | Berkas bantu deployment (konfigurasi hosting dan contoh `.env` production) |

## 4.2 Daftar Model

| Model | Tabel | Primary key | Fungsi |
| --- | --- | --- | --- |
| `User` | `users` | `user_id` | Akun Admin dan Pemilik Usaha; menyembunyikan atribut `password` |
| `Customer` | `customers` | `customer_id` | Data pelanggan |
| `Device` | `devices` | `device_id` | Data perangkat dan keluhan |
| `Service` | `services` | `service_id` | Daftar layanan; cast `is_active` menjadi boolean |
| `Schedule` | `schedules` | `schedule_id` | Slot jadwal dan kuota |
| `Booking` | `bookings` | `booking_id` | Booking dan Service ID; relasi ke customer, device, service, schedule, payment, serviceRecord |
| `ServiceRecord` | `service_records` | `service_record_id` | Diagnosis, biaya, status pengerjaan; cast biaya ke decimal |
| `Payment` | `payments` | `payment_id` | Pembayaran, metode, status, bukti QRIS |

## 4.3 Daftar Controller dan Fungsinya

| Controller | Method | Fungsi |
| --- | --- | --- |
| `AuthController` | `showLogin` | Menampilkan form login |
| `AuthController` | `login` | Validasi kredensial, cek role, regenerate session, redirect sesuai role |
| `AuthController` | `logout` | Logout dan invalidasi session |
| `BookingController` | `store` | Membuat booking, pelanggan, perangkat, dan pembayaran awal dalam satu transaksi |
| `BookingController` | `verifyBooking` | Menerima booking dan membuat `service_records` |
| `BookingController` | `updateBookingStatus` | Menyimpan diagnosis, estimasi, status pengerjaan, dan biaya akhir |
| `BookingController` | `markPaid` | Konfirmasi pembayaran versi Eloquent (dipakai route legacy) |
| `AdminPanelController` | `dashboard` | Statistik dan ringkasan untuk dashboard Admin |
| `AdminPanelController` | `schedules`, `storeSchedule`, `updateScheduleStatus` | Pengelolaan jadwal servis |
| `AdminPanelController` | `serviceProcess` | Daftar booking dan form proses servis dengan filter status dan pencarian |
| `AdminPanelController` | `customers`, `devices`, `services` | Data master pelanggan, perangkat, dan layanan |
| `AdminPanelController` | `storeService`, `updateService`, `toggleService` | CRUD layanan tanpa menghapus data historis |
| `AdminPanelController` | `payments` | Daftar pembayaran dengan filter status dan pencarian |
| `AdminPanelController` | `paymentReceipt` | Struk pembayaran yang hanya tersedia setelah lunas |
| `AdminPanelController` | `cancelBooking` | Pembatalan booking oleh Admin beserta pengembalian slot |
| `AdminPanelController` | `markPaid` | Konfirmasi pembayaran; QRIS wajib punya bukti, CASH wajib mencatat uang diterima |
| `StatusController` | `index`, `show` | Halaman cek status dan pencarian berdasarkan Service ID |
| `StatusController` | `cancel` | Pembatalan booking oleh pelanggan |
| `StatusController` | `uploadProof` | Unggah bukti pembayaran QRIS dengan validasi berkas |
| `StatusController` | `upload` | Alias kompatibilitas untuk route upload lama |
| `PaymentProofController` | `show` | Menampilkan bukti pembayaran untuk Admin dan Pemilik Usaha |
| `PaymentProofController` | `showForCustomer` | Menampilkan bukti pembayaran milik Service ID yang sedang dicek |
| `PaymentProofController` | `requestReupload` | Meminta pelanggan mengunggah ulang bukti dengan catatan admin |
| `ReportController` | `serviceHistory`, `serviceHistoryPdf`, `serviceHistoryExcel` | Riwayat servis beserta ekspor PDF dan Excel |
| `ReportController` | `income`, `incomePdf`, `incomeExcel` | Laporan pemasukan beserta ekspor PDF dan Excel |
| `Api\ApiBookingController` | `store` | Membuat booking melalui JSON API (HTTP 201) |
| `PageController` | `index` | Sisa pengembangan; tidak terhubung ke route mana pun |
| `CustomerController`, `ServiceController`, `UserController` | CRUD | Sisa pengembangan; tidak terhubung ke route mana pun dan view `crud.*` tidak tersedia |

## 4.4 Daftar Middleware

| Middleware | Alias | Didaftarkan di | Fungsi |
| --- | --- | --- | --- |
| `RoleMiddleware` | `role` | `bootstrap/app.php` | Membatasi route berdasarkan role pengguna |
| Middleware bawaan `web`, `auth`, `api` | - | `bootstrap/app.php` | Session, CSRF, autentikasi |

## 4.5 Penjelasan Routing

- `routes/web.php` memuat seluruh route aplikasi. Route publik didefinisikan lebih dahulu, kemudian grup `auth` + `role:ADMIN`, lalu grup `auth` + `role:PEMILIK_USAHA`.
- Nama route memakai awalan `admin.` dan `pemilik.` sehingga view dapat memanggil `route('admin.payments.proof')` atau `route('pemilik.income')` tanpa menulis URL.
- Route yang mengubah data memakai method `POST`, `PUT`, atau `PATCH` sehingga otomatis dilindungi CSRF.
- `routes/api.php` memuat satu endpoint JSON yang benar-benar berfungsi, yaitu `POST /api/bookings`. Tiga route lama (`GET /api/schedules`, `GET /api/bookings/{code}`, `PUT /api/bookings/{code}/status`) dihapus pada Sprint 4 karena menunjuk method controller yang belum ada sehingga menghasilkan HTTP 500 (lihat `docs/01-release/BUG_FIXING_LOG.md` tiket S4-01-02).

## 4.6 Penjelasan View

| View | Fungsi |
| --- | --- |
| `layouts/app.blade.php` | Layout publik: header, navigasi, footer, penampilan flash message |
| `layouts/admin.blade.php` | Layout panel: sidebar, header halaman, kartu statistik, tabel, form, alert |
| `beranda/welcome.blade.php` | Halaman beranda dengan layanan dan jadwal terdekat |
| `jadwal/ketersediaan-jadwal.blade.php` | Daftar seluruh jadwal beserta statusnya |
| `booking/booking.blade.php` | Formulir booking publik |
| `status/cek-status.blade.php` | Pencarian Service ID, status servis, rincian pembayaran, unggah bukti, dan pembatalan |
| `login/login.blade.php` | Form login dengan pilihan role |
| `admin/dashboard-admin.blade.php` | Dashboard Admin |
| `admin/jadwal-servis.blade.php` | Tambah, tutup, dan buka jadwal |
| `admin/kelola-proses-servis.blade.php` | Penerimaan booking dan form diagnosis, estimasi, biaya akhir, status |
| `admin/data-customer.blade.php`, `data-perangkat.blade.php`, `data-service.blade.php` | Data master |
| `admin/pembayaran.blade.php` | Daftar pembayaran dan konfirmasi lunas |
| `admin/struk-pembayaran.blade.php` | Struk siap cetak (termasuk printer 80 mm) |
| `admin/riwayat-servis.blade.php`, `laporan-pemasukan.blade.php` | Halaman laporan |
| `admin/exports/*.blade.php` | Template PDF laporan (dompdf) |
| `admin/partials/sidebar.blade.php` | Navigasi panel; menu operasional hanya untuk Admin |
| `admin/partials/cancel-booking-modal.blade.php` | Dialog pembatalan dengan alasan wajib |
| `admin/partials/qris-payment-proofs.blade.php` | Pratinjau bukti QRIS |
| `pemilik/dashboard-pemilik.blade.php` | Dashboard Pemilik Usaha (monitoring) |
| `pemilik/partials/bukti-pembayaran.blade.php` | Pratinjau bukti pembayaran untuk Pemilik |
| `errors/403`, `404`, `419`, `500`, `503`, `layout` | Halaman kesalahan ramah tanpa stack trace |

## 4.7 Struktur Frontend

- Setiap halaman mendeklarasikan `@extends('layouts.app')` atau `@extends('layouts.admin')`, lalu mengisi `@section('content')` atau `@section('admin-content')`.
- CSS ditulis pada blok `<style>` di bagian atas masing-masing view atau layout dengan penamaan kelas berawalan (`admin-*`, `login-*`). Tidak ada berkas CSS eksternal yang dimuat.
- Tata letak responsif memakai media query pada layout admin: 1100 px, 800 px, dan 520 px. Sidebar berubah menjadi menu yang dapat dibuka-tutup pada layar kecil.
- Gambar statis tersedia di `public/images/logo.fixpoint.jpeg` (logo) dan `public/qris/qris.toko.png` (QRIS toko).

## 4.8 Kelas Bantu `SimpleXlsx`

`app/Support/SimpleXlsx.php` membuat berkas `.xlsx` langsung dengan `ZipArchive` dan XML SpreadsheetML minimal (sheet, shared string sederhana, dan format kolom). Kelas ini dipakai `ReportController` agar ekspor Excel tidak memerlukan dependensi tambahan seperti PhpSpreadsheet pada lingkungan shared hosting.

# BAB V - PROSES BISNIS

## 5.1 Booking oleh Pelanggan

1. Pelanggan membuka `GET /booking`. Closure route memuat `Schedule` dengan `status = AVAILABLE`, `current_booking < max_booking`, dan `schedule_date >= hari ini`, serta `Service` dengan `is_active = true`.
2. Pelanggan mengisi nama, nomor telepon, jenis perangkat, merek/model, layanan, keluhan, jadwal, dan metode pembayaran.
3. `POST /booking` ditangani `BookingController@store`. Validasi memakai `Rule::exists` untuk memastikan layanan aktif dan jadwal valid, serta `regex` untuk membatasi nama dan nomor telepon.
4. Seluruh proses dibungkus `DB::transaction()`. Jadwal dibaca dengan `lockForUpdate()` agar dua permintaan bersamaan tidak mengambil slot terakhir sekaligus.
5. Bila jadwal penuh atau ditutup, controller mengembalikan `back()->withInput()->with('error', ...)` tanpa membuat data apa pun.
6. Pelanggan dicari berdasarkan `phone_number`; bila belum ada, dibuat baru. Perangkat selalu dibuat baru untuk setiap booking agar keluhan tiap servis tercatat.
7. Booking disimpan dengan status `PENDING`, lalu baris `payments` dibuat dengan `amount = 0`, `payment_method` sesuai pilihan pelanggan, dan `payment_status = UNPAID`.
8. `current_booking` dinaikkan dan status jadwal diubah menjadi `FULL` bila kuota habis.
9. Controller mengembalikan redirect ke halaman booking dengan flash `success` dan `service_id_code` untuk ditampilkan kepada pelanggan.

## 5.2 Pembuatan Service ID

Service ID dibuat dengan pola `FP-` + tanggal (`Ymd`) + lima karakter acak huruf/angka, contoh `FP-20261115-A7K2Q`. Pembuatan memakai perulangan `do ... while` yang memeriksa `bookings.service_id_code` sehingga tidak ada kode ganda, dan kolom tersebut juga memiliki constraint `unique` di database. Kode ini menjadi identitas yang dipakai pelanggan pada halaman cek status.

## 5.3 Pengelolaan Data Pelanggan

Data pelanggan dibuat otomatis saat booking. Halaman `admin/data-customer` menampilkan nama, nomor telepon, jumlah perangkat, dan jumlah booking melalui `AdminPanelController@customers`, dengan subquery `selectSub` untuk menghitung relasi. Pencarian memakai parameter `q` pada nama atau nomor telepon. Data pelanggan tidak dihapus dari panel karena pembatalan booking tetap menyimpan riwayat pelanggan.

## 5.4 Pengelolaan Data Perangkat

`AdminPanelController@devices` menampilkan jenis perangkat, merek, model, keluhan, dan pemiliknya. Untuk PC desktop, `brand` dan `model` diisi string kosong karena kolom tersebut wajib (NOT NULL) pada skema, sedangkan formulir hanya meminta merek/model untuk laptop.

## 5.5 Pengelolaan Layanan

Admin dapat menambah layanan (`storeService`), mengubah (`updateService`), dan mengaktifkan atau menonaktifkan layanan (`toggleService`). Layanan tidak dihapus agar booking historis tetap memiliki referensi. Layanan nonaktif tidak muncul di form booking, dan validasi `store` menolak layanan yang tidak aktif.

## 5.6 Pengelolaan Jadwal

`storeSchedule` memvalidasi tanggal (tidak boleh lampau), format jam, `end_time` setelah `start_time`, dan kuota minimal 1. Sistem menolak jadwal yang bertabrakan dengan jadwal lain pada tanggal yang sama melalui pemeriksaan rentang waktu. `updateScheduleStatus` menutup jadwal (status `CLOSED`) atau membukanya kembali; saat dibuka, status menjadi `FULL` bila kuota sudah penuh, atau `AVAILABLE` bila masih tersedia.

## 5.7 Diagnosis dan Estimasi Biaya

1. Admin menerima booking melalui `PUT /admin/booking/{id}/verify` (`BookingController@verifyBooking`). Controller membuat `service_records` dengan status `PENDING` dan `handled_by` diisi Admin yang login, lalu mengubah status booking menjadi `CONFIRMED`.
2. Admin mengisi diagnosis, estimasi biaya, estimasi waktu, dan status pengerjaan melalui `POST /admin/booking/update` (`BookingController@updateBookingStatus`).
3. Controller menolak perubahan status bila booking belum `CONFIRMED`, menolak pengembalian status ke tahap sebelumnya, dan menolak penandaan `COMPLETED` tanpa biaya akhir lebih dari nol.
4. Saat status menjadi `PROCESS`, `started_at` diisi bila masih kosong. Saat `COMPLETED`, `final_cost` dan `completed_at` diisi, lalu booking berubah menjadi `COMPLETED` dan `payments.amount` diperbarui menjadi biaya akhir dengan status tetap `UNPAID` sampai pembayaran dikonfirmasi.
5. Estimasi waktu dan catatan disimpan pada `service_notes` dengan awalan teks `Estimasi pengerjaan:` dan `Catatan:`.

## 5.8 Estimasi dan Penetapan Biaya Akhir

Estimasi biaya (`estimated_cost`) bersifat informatif dan tampil pada halaman cek status pelanggan. Biaya akhir (`final_cost`) menjadi dasar tagihan: nilainya disalin ke `payments.amount` pada saat servis ditandai selesai. Validasi membatasi kedua nilai pada rentang 0 sampai 9.999.999.999,99.

## 5.9 Pembayaran Tunai (CASH)

Konfirmasi dilakukan Admin melalui `PUT /admin/booking/{id}/mark-paid` (`AdminPanelController@markPaid`). Sistem mensyaratkan:

- Booking berstatus `COMPLETED`.
- Baris pembayaran ada, metode `CASH`, dan nominal tagihan lebih dari nol.
- Input `cash_received` wajib berupa angka dan tidak kurang dari total tagihan.

Setelah valid, `payment_status` menjadi `PAID`, `processed_by` diisi Admin, `payment_date` diperbarui, dan `cash_received` disimpan untuk keperluan struk.

## 5.10 Pembayaran QRIS

Pelanggan memilih metode QRIS saat booking. Setelah servis selesai dan tagihan tersedia, pelanggan memindai gambar QRIS toko (`public/qris/qris.toko.png`), membayar, lalu mengunggah bukti. Admin hanya dapat mengonfirmasi QRIS bila `payment_status = WAITING_VERIFICATION` dan `payment_proof` terisi. Admin juga dapat menolak bukti melalui `POST /admin/pembayaran/{paymentId}/minta-upload-ulang` yang mengubah status kembali ke `UNPAID` dan menyimpan catatan penolakan pada `payments.notes`.

## 5.11 Unggah Bukti Pembayaran

`POST /cek-status/upload-proof` ditangani `StatusController@uploadProof`:

1. Validasi: `service_id_code` harus ada pada tabel `bookings`; `payment_proof` wajib berupa berkas gambar dengan `mimes:jpeg,png,jpg,webp`, `mimetypes:image/jpeg,image/png,image/webp`, dan ukuran maksimal 2 MB (2048 KB).
2. Berkas disimpan pada disk `public` di folder `payment_proofs` dengan nama acak yang dihasilkan Laravel.
3. Dalam transaksi: booking harus `COMPLETED`, pembayaran harus ada, metode harus `QRIS`, nominal tagihan lebih dari nol, status bukan `PAID`, dan status bukan `WAITING_VERIFICATION`.
4. Bila proses gagal, berkas baru dihapus; bila berhasil, path lama dihapus agar tidak menumpuk.
5. Status pembayaran berubah menjadi `WAITING_VERIFICATION` dan catatan penolakan sebelumnya dibersihkan.

## 5.12 Pembatalan Booking

- **Oleh pelanggan**: `POST /cek-status/{code}/cancel` (`StatusController@cancel`). Alasan pembatalan wajib diisi (maksimal 500 karakter). Hanya booking berstatus `PENDING` atau `WAITING_VERIFICATION` yang dapat dibatalkan. Slot jadwal dikembalikan (`current_booking` dikurangi) dan status jadwal kembali `AVAILABLE` bila sebelumnya `FULL`.
- **Oleh Admin**: `POST /admin/booking/{id}/cancel` (`AdminPanelController@cancelBooking`). Alasan wajib diisi. Booking yang catatan servisnya sudah `PROCESS` atau `COMPLETED` tidak dapat dibatalkan karena pekerjaan sudah berjalan.
- Pada kedua jalur, alasan disimpan di `bookings.cancellation_reason` dan status menjadi `CANCELLED`.

## 5.13 Perubahan Status

| Dari | Ke | Dipicu oleh | Efek |
| --- | --- | --- | --- |
| `PENDING` | `CONFIRMED` | Admin menerima booking | `service_records` dibuat, `handled_by` terisi |
| `PENDING`/`WAITING_VERIFICATION` | `CANCELLED` | Pelanggan atau Admin | Slot jadwal dikembalikan, alasan tersimpan |
| `CONFIRMED` | `CONFIRMED` | Admin mengubah `service_status` menjadi `DIAGNOSIS` atau `PROCESS` | `started_at` terisi saat masuk `PROCESS` |
| `CONFIRMED` | `COMPLETED` | Admin menandai servis selesai dengan biaya akhir | `final_cost`, `completed_at`, dan `payments.amount` terisi |
| `COMPLETED` | `COMPLETED` (pembayaran) | Admin mengonfirmasi pembayaran | `payment_status` menjadi `PAID` |

Status `WAITING_VERIFICATION` dan `REJECTED` tersedia pada enum `bookings.status` untuk kebutuhan alur verifikasi, namun alur yang berjalan saat ini memakai `PENDING` sampai booking diterima Admin.

## 5.14 Riwayat Servis

Riwayat servis tersedia bagi Admin (`/admin/riwayat-servis`) dan Pemilik Usaha (`/pemilik/riwayat-servis`) melalui `ReportController@serviceHistory`. Query menggabungkan `service_records`, `bookings`, `customers`, `devices`, `services`, dan `payments` dengan syarat `service_status = COMPLETED`. Filter `from` dan `to` memakai `whereDate` pada `sr.completed_at`; bila rentang tertukar, controller menukarnya otomatis. Ringkasan menampilkan jumlah servis selesai dan total biaya. Ekspor tersedia dalam PDF (dompdf) dan XLSX.

# BAB VI - KEAMANAN SISTEM

## 6.1 Autentikasi

Login memakai `Auth::attempt()` sehingga password diperiksa dengan `Hash::check()` terhadap hash bcrypt di database. Setelah login berhasil, `session()->regenerate()` dijalankan untuk mencegah session fixation, dan role akun diverifikasi terhadap pilihan role pada form. Bila role tidak sesuai, pengguna langsung di-logout dan session di-invalidate.

## 6.2 Otorisasi

Hak akses dibatasi pada level route melalui `RoleMiddleware` (`abort(403)` bila role tidak cocok), dan pada level tampilan melalui `admin/partials/sidebar.blade.php` yang hanya menampilkan menu operasional untuk Admin. Route laporan untuk Pemilik Usaha mengarah ke prefix `pemilik` sehingga tidak membuka akses ke route `admin`.

## 6.3 Penyimpanan Password

Password disimpan sebagai hash bcrypt melalui `Hash::make()`, dengan `BCRYPT_ROUNDS=12` pada `.env.example`. Model `User` menyembunyikan atribut `password` melalui `protected $hidden`. Password asli tidak pernah disimpan, dicatat pada log, atau dikirim ke view. Seeder pengembangan membuat akun awal dengan password yang wajib diganti sebelum dipakai di production.

## 6.4 Validasi Input

Seluruh input yang mengubah data divalidasi di server:

- Booking: `nama` (regex huruf, spasi, titik, koma, strip), `telepon` (10-15 karakter, hanya angka dan simbol telepon), `jenis_perangkat` (`in:Laptop,PC Desktop`), `service_id` (exists pada layanan aktif), `keluhan` (maks. 1000), `schedule_id` (exists), `payment_method` (`in:CASH,QRIS`).
- Login: `email` (email), `password`, `role` (`in:admin,pemilik`).
- Jadwal: `schedule_date` (`after_or_equal:today`), `start_time` dan `end_time` (`date_format:H:i`), `end_time` (`after:start_time`), `max_booking` (integer, min 1).
- Diagnosis dan biaya: `service_status` (`in:DIAGNOSIS,PROCESS,COMPLETED`), `estimated_cost` dan `final_cost` (numeric, min 0, maks 9.999.999.999,99).
- Pembayaran: `cash_received` diperiksa numerik dan tidak kurang dari tagihan.
- Pembatalan: `cancellation_reason` wajib, maksimal 500 karakter.
- Layanan: `name` wajib, `estimated_price` numeric.
- Unggah bukti: tipe berkas dan ukuran seperti pada bagian 5.11.

Pesan kesalahan ditulis dalam bahasa Indonesia agar dapat dipahami pengguna.

## 6.5 Perlindungan CSRF

Laravel mengaktifkan middleware `VerifyCsrfToken` pada grup `web`. Semua form Blade memuat `@csrf`. Route yang mengubah data tidak dapat dipanggil melalui `GET`. Bila token kedaluwarsa, pengguna menerima halaman `errors/419`.

## 6.6 Perlindungan SQL Injection

Seluruh query memakai Query Builder atau Eloquent dengan parameter terikat (prepared statement), termasuk `where`, `whereIn`, `whereDate`, `orderBy`, dan `select`. Tidak ada input pengguna yang digabungkan langsung ke string SQL. Pencarian memakai `LIKE` dengan nilai yang diikat, contoh pada `AdminPanelController@serviceProcess`:

```php
$query->where(function ($subQuery) use ($search) {
    $subQuery->where('b.service_id_code', 'like', '%' . $search . '%')
        ->orWhere('c.name', 'like', '%' . $search . '%');
});
```

Nilai `$search` dikirim sebagai parameter terikat sehingga tidak dapat mengubah struktur query.

## 6.7 Validasi Berkas Unggah

Bukti pembayaran divalidasi tiga lapis: aturan `image` (memastikan berkas benar-benar gambar), `mimes` dan `mimetypes` (JPG, JPEG, PNG, WEBP), serta `max:2048` (2 MB). Berkas disimpan dengan nama acak yang dibuat Laravel sehingga nama asli pengguna tidak dipakai sebagai path.

## 6.8 Pengamanan Berkas

- Berkas unggahan berada di `storage/app/public/payment_proofs`, di luar document root aplikasi.
- Bukti pembayaran tidak pernah dirender sebagai URL storage langsung. `PaymentProofController` mengalirkan berkas melalui route yang dilindungi (`/admin/pembayaran/{paymentId}/bukti`, `/pemilik/pembayaran/{paymentId}/bukti`) atau route pelanggan yang memeriksa status booking (`/cek-status/{code}/bukti-pembayaran`).
- Respons berkas memakai header `Content-Type` sesuai MIME yang diperiksa, `X-Content-Type-Options: nosniff`, `Cache-Control: private, no-store`, dan `Content-Disposition: inline`.
- Pada deployment shared hosting, `.htaccess` di document root mengarahkan seluruh permintaan ke folder `public`, sehingga berkas `.env` dan `storage/logs` tidak dapat dibuka dari browser.

## 6.9 Pengamanan Konfigurasi Environment

- `.env` tidak di-commit (tercantum pada `.gitignore`).
- Untuk production: `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true` bila HTTPS aktif.
- `bootstrap/app.php` menangani `QueryException`: pesan rinci hanya dikirim ke log, sedangkan pengguna menerima halaman 500 ramah atau JSON `{"message": "..."}` tanpa detail query.
- Halaman kesalahan kustom `403`, `404`, `419`, `500`, dan `503` tidak menampilkan stack trace.

## 6.10 Catatan Temuan Keamanan

- Endpoint `PUT /api/bookings/{code}/status`, `GET /api/schedules`, dan `GET /api/bookings/{code}` sebelumnya terdaftar tanpa implementasi. Ketiganya sudah dihapus dari `routes/api.php` pada Sprint 4 sehingga tidak ada lagi route publik yang menghasilkan HTTP 500. Pembuatan endpoint baru berada di luar Code Freeze dan dicatat sebagai pengembangan lanjutan pada `docs/01-release/BUG_FIXING_LOG.md`.

# BAB VII - INSTALASI DAN KONFIGURASI

## 7.1 Prasyarat Sistem

| Kebutuhan | Versi minimal | Keterangan |
| --- | --- | --- |
| PHP | 8.3 | Sesuai `composer.json` (`^8.3`); pengembangan memakai 8.4.26 |
| Ekstensi PHP | `openssl`, `pdo`, `pdo_mysql`, `mbstring`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `zip`, `dom`, `libxml` | `zip` dan `dom` wajib untuk ekspor XLSX dan PDF |
| Composer | 2.x | Pengembangan memakai Composer 2.10.3 |
| MySQL / MariaDB | 5.7+ / 10.3+ | Database `fixpoint_service` |
| Git | 2.x | Untuk mengambil repository |

PHP tidak memerlukan `npm` atau Node.js karena view tidak memakai `@vite`.

## 7.2 Instalasi Dependensi

```
git clone https://github.com/davinsolehpermana-code/fixpoint-service.git
cd fixpoint-service
composer install
cp .env.example .env
php artisan key:generate
```

## 7.3 Konfigurasi Environment

Isi `.env` minimal sebagai berikut.

```
APP_NAME="FixPoint Service"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=fixpoint_service
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=file
FILESYSTEM_DISK=local
```

`SESSION_DRIVER`, `CACHE_STORE`, dan `QUEUE_CONNECTION` harus memakai `file` atau `sync` karena migration proyek tidak membuat tabel `sessions`, `cache`, maupun `jobs`.

## 7.4 Konfigurasi Database

1. Buat database kosong: `CREATE DATABASE fixpoint_service CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`
2. Sesuaikan `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD` pada `.env`.
3. Uji koneksi dengan `php artisan migrate:status`; bila konfigurasi benar, perintah menampilkan daftar migration beserta statusnya.

## 7.5 Menjalankan Migration

```
php artisan migrate
```

Perintah ini menjalankan 10 migration sesuai urutan tanggal. Jangan memakai `migrate:fresh` pada database yang sudah berisi data karena akan menghapus seluruh tabel.

## 7.6 Menjalankan Seeder

```
php artisan db:seed
```

`DatabaseSeeder` bersifat idempoten (`firstOrCreate`) dan membuat dua layanan awal serta dua akun:

| Email | Role | Password awal |
| --- | --- | --- |
| `admin@fixpoint.test` | ADMIN | `Admin12345` |
| `pemilik@fixpoint.test` | PEMILIK_USAHA | `Pemilik12345` |

Akun ini hanya untuk pengembangan. Sebelum dipakai di production, password wajib diubah dan `UserSeeder` (yang memakai `create()` sehingga gagal bila data sudah ada) tidak perlu dijalankan.

## 7.7 Build Asset

Tidak diperlukan. Seluruh CSS dan JavaScript yang dipakai aplikasi ditulis di dalam file Blade, dan tidak ada direktif `@vite` pada `resources/views`. Perintah `npm install` dan `npm run build` hanya relevan bila kelak Tailwind dipakai melalui Vite.

## 7.8 Storage

```
php artisan storage:link
```

Perintah ini membuat symlink `public/storage` ke `storage/app/public` sehingga berkas bukti pembayaran dapat dilayani. Pada hosting yang tidak mendukung symlink, gunakan aturan `RewriteRule` seperti pada `deploy/infinityfree/htdocs.htaccess`. Folder `storage/app/public/payment_proofs` harus ada dan dapat ditulis oleh proses PHP; `run_local_offline.bat` membuat folder tersebut secara otomatis.

## 7.9 Menjalankan Aplikasi

```
php artisan serve
```

Aplikasi dapat dibuka pada `http://127.0.0.1:8000`. Untuk keperluan demonstrasi tanpa internet, gunakan `run_local_offline.bat` yang menjalankan `php artisan serve --host 0.0.0.0 --port 8000` dan menampilkan alamat IP LAN yang sebenarnya.

## 7.10 Menjalankan Pengujian

```
php artisan test
```

Pengujian memakai SQLite in-memory sesuai `phpunit.xml` sehingga tidak mengubah database pengembangan. Hasil terakhir: 30 pengujian lulus dengan 131 assertion.

# BAB VIII - DEPENDENSI DAN TEKNOLOGI

## 8.1 PHP dan Ekstensi

Aplikasi memerlukan PHP 8.3 atau lebih baru dengan ekstensi `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `zip`, dan `dom`. Ekstensi `zip` dipakai `SimpleXlsx` dan `build.php`; `dom` dipakai dompdf.

## 8.2 Dependensi Composer (Production)

| Paket | Versi | Kegunaan dalam proyek |
| --- | --- | --- |
| `laravel/framework` | `^13.17` | Kerangka aplikasi: routing, Eloquent, validasi, session, Blade |
| `barryvdh/laravel-dompdf` | `^3.1` | Membuat PDF laporan riwayat servis dan laporan pemasukan |
| `laravel/sanctum` | `^4.0` | Terpasang dari skeleton; belum dipakai pada alur aplikasi |
| `laravel/tinker` | `^3.0` | REPL untuk pemeriksaan data saat pengembangan |

## 8.3 Dependensi Pengembangan

| Paket | Versi | Kegunaan |
| --- | --- | --- |
| `phpunit/phpunit` | `^12.5.12` | Menjalankan pengujian otomatis |
| `mockery/mockery` | `^1.6` | Mock objek pada pengujian |
| `fakerphp/faker` | `^1.23` | Data palsu untuk factory |
| `nunomaduro/collision` | `^8.6` | Tampilan kesalahan yang mudah dibaca pada CLI |
| `laravel/pint` | `^1.27` | Perapian gaya kode PHP |
| `laravel/pail` | `^1.2.5` | Pembacaan log secara langsung |
| `laravel/boost` | `^2.10` | Alat bantu pengembangan Laravel |
| `laravel/pao` | `^1.0.6` | Alat bantu pengembangan Laravel |

## 8.4 JavaScript dan CSS

| Berkas | Status |
| --- | --- |
| `resources/css/app.css` | Ada sebagai sisa scaffolding Vite, tidak dimuat view |
| `resources/js/app.js` | Ada sebagai sisa scaffolding Vite, tidak dimuat view |
| `package.json` (`vite`, `tailwindcss`, `laravel-vite-plugin`) | Terdaftar, tidak dibutuhkan untuk menjalankan aplikasi |
| JavaScript di dalam Blade | Dipakai untuk toggle password, toggle sidebar, dan dialog konfirmasi |

## 8.5 Alat Pengembangan dan Operasional

| Alat | Kegunaan |
| --- | --- |
| Git dan GitHub | Version control, branch `main`, `feature/backend`, `feature/frontend` |
| Composer | Manajemen dependensi PHP |
| PHPUnit | Pengujian fitur dan unit |
| `php artisan route:list` | Memeriksa seluruh route yang terdaftar |
| `php artisan migrate:status` | Memeriksa status migration |
| phpMyAdmin / mysqldump | Pemeriksaan dan ekspor skema database |

## 8.6 Ringkasan Ketergantungan Eksternal

Aplikasi tidak bergantung pada layanan pihak ketiga saat berjalan: tidak ada payment gateway, tidak ada penyedia email, tidak ada penyimpanan objek awan, dan tidak ada API eksternal. Semua data berada pada database MySQL dan filesystem host. Ketergantungan eksternal hanya ada pada proses pengembangan (GitHub) dan pada gambar QRIS statis milik toko.

# LAMPIRAN A - DAFTAR EVIDENCE TANGKAPAN LAYAR

Dokumen ini belum memuat tangkapan layar aplikasi. Sesuai ketentuan penyusunan dokumen, gambar tidak dibuat atau direkayasa. Tangkapan layar berikut perlu diambil dari aplikasi yang berjalan agar dokumen dapat dilengkapi tanpa mengubah isi teknisnya.

| No | Evidence yang perlu diambil | Halaman / sumber |
| --- | --- | --- |
| 1 | Halaman beranda dengan daftar layanan dan jadwal | `/` |
| 2 | Halaman ketersediaan jadwal (AVAILABLE, FULL, CLOSED) | `/ketersediaan-jadwal` |
| 3 | Formulir booking yang terisi | `/booking` |
| 4 | Konfirmasi booking beserta Service ID | `/booking` setelah submit |
| 5 | Halaman cek status servis | `/cek-status/result?code=<Service ID>` |
| 6 | Form unggah bukti pembayaran QRIS | `/cek-status/result?code=<Service ID>` |
| 7 | Halaman login dengan pilihan role | `/login` |
| 8 | Dashboard Admin beserta kartu statistik | `/dashboard-admin` |
| 9 | Form diagnosis, estimasi, dan biaya akhir | `/admin/kelola-proses-servis` |
| 10 | Daftar pembayaran dan konfirmasi lunas | `/admin/pembayaran` |
| 11 | Struk pembayaran siap cetak | `/admin/pembayaran/{paymentId}/struk` |
| 12 | Riwayat servis beserta filter tanggal | `/admin/riwayat-servis` |
| 13 | Laporan pemasukan Pemilik Usaha | `/pemilik/laporan-pemasukan` |
| 14 | Dashboard Pemilik Usaha | `/dashboard-pemilik` |
| 15 | Halaman 403 dan 404 kustom | `/dashboard-admin` sebagai Pemilik, URL tidak ada |
| 16 | Tampilan mobile (lebar 390 px) pada halaman booking dan dashboard | `/booking`, `/dashboard-admin` |

# LAMPIRAN B - CATATAN KETERLACAKAN DOKUMEN

| Bagian dokumen | Sumber utama di repository |
| --- | --- |
| Bab I | `composer.json`, `.env.example`, `routes/web.php`, `php -v` |
| Bab II | `bootstrap/app.php`, `routes/web.php`, `app/Http/Middleware/RoleMiddleware.php`, `app/Http/Controllers/AuthController.php` |
| Bab III | `database/migrations/*`, `app/Models/*` |
| Bab IV | `app/Http/Controllers/*`, `resources/views/*`, `app/Support/SimpleXlsx.php` |
| Bab V | `BookingController`, `StatusController`, `AdminPanelController`, `PaymentProofController`, `ReportController` |
| Bab VI | Validasi dan query pada controller, `bootstrap/app.php`, `resources/views/errors/*` |
| Bab VII | `composer.json`, `phpunit.xml`, `database/seeders/*` |
| Bab VIII | `composer.json`, `package.json`, `vite.config.js` |
