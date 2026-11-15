# DEPLOYMENT GUIDE - FIXPOINT SERVICE (S4-02)

**Target hosting:** InfinityFree (free hosting, PHP + MySQL)
**Tanggal penyusunan:** 15 November 2026
**Versi aplikasi:** `v1.0.0-rc`

---

## 0. Status deployment (baca lebih dahulu)

| Butir | Status |
| --- | --- |
| Paket aplikasi production yang siap diunggah | **Selesai** (`deploy/release/`, lihat bagian 4) |
| Uji jalan aplikasi dalam mode production di komputer lokal | **Selesai dan lulus** (bagian 5) |
| Unggah ke hosting InfinityFree | **Belum dilakukan** |
| Alasan | Tidak ada sesi akun atau kredensial FTP/panel InfinityFree yang tersedia di lingkungan kerja. Halaman `https://dash.infinityfree.com/` menampilkan form login (tidak ada sesi tersimpan). Kredensial tidak boleh dikarang, diminta lewat dokumen, atau ditulis di repository |
| Yang masih diperlukan | Pemilik akun menjalankan langkah 6 sampai 9 dengan akun InfinityFree-nya, atau memberikan akses FTP secara aman di luar repository |

Dokumen ini karena itu berisi **prosedur yang benar-benar sudah disiapkan dan diuji sampai tahap paket production**, ditambah langkah unggah yang harus dieksekusi dengan akun hosting. Tidak ada URL publik yang diklaim berjalan.

## 1. Hasil Pemeriksaan Kemampuan Hosting

Pemeriksaan dilakukan terhadap ketentuan resmi InfinityFree (forum dan dokumentasi resmi), bukan asumsi.

| Kebutuhan aplikasi | Ketersediaan di InfinityFree | Dampak dan penanganan |
| --- | --- | --- |
| PHP 8.3+ (composer.json `^8.3`) | Tersedia. Seluruh server free hosting telah dinaikkan ke PHP 8.4 | Sesuai kebutuhan Laravel 13 |
| Ekstensi PHP `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `xml`, `dom`, `zip` | Tersedia pada paket PHP standar | `zip` dan `dom` wajib untuk ekspor XLSX dan PDF |
| MySQL / MariaDB | Tersedia, database dibuat dari panel VistaPanel | Skema diimpor lewat phpMyAdmin karena tidak ada Artisan |
| HTTPS | Tersedia (SSL otomatis untuk subdomain) | Aktifkan **Force HTTPS** dan set `SESSION_SECURE_COOKIE=true` |
| Document root | Selalu `htdocs`, tidak dapat diubah | Seluruh proyek diunggah ke `htdocs`, lalu `.htaccess` mengarahkan ke `public/` |
| SSH / terminal | Tidak tersedia | Seluruh perintah Artisan dan Composer dijalankan di komputer lokal, hasilnya diunggah |
| Composer di server | Tidak tersedia | Folder `vendor/` dibangun lokal dengan `--no-dev` lalu diunggah |
| Cron job | Tidak tersedia | Aplikasi tidak memakai queue maupun scheduler; `QUEUE_CONNECTION=sync` |
| Symlink (`php artisan storage:link`) | Tidak didukung | Diganti aturan `RewriteRule ^storage/(.*)$ /storage/app/public/$1 [END]` pada `.htaccess` |
| Environment variable sistem | Tidak tersedia | Konfigurasi dibaca Laravel dari berkas `.env` di `htdocs/.env` |
| File upload dan penyimpanan persisten | Didukung (kuota disk 5 GB) | Bukti pembayaran disimpan di `storage/app/public/payment_proofs` |
| `.htaccess` | Didukung penuh | Dipakai untuk routing ke `public/` dan penyajian `storage/` |
| Batas eksekusi | Kuota harian hits dan CPU terbatas | Jangan mengaktifkan debug; jangan menjalankan pengujian beban |

Konsekuensi lain yang perlu diketahui: InfinityFree melarang penggunaan hosting sebagai backend API untuk aplikasi pihak ketiga. FixPoint Service adalah aplikasi web yang menyajikan halaman dan satu endpoint JSON internal, bukan API publik untuk aplikasi lain.

## 2. Kebutuhan Versi PHP

Berdasarkan `composer.json`: `"php": "^8.3"`, `"laravel/framework": "^13.17"`. Versi PHP 8.4 pada hosting memenuhi syarat. Jangan menurunkan constraint PHP pada `composer.json` hanya agar sesuai hosting, karena Laravel 13 memerlukan PHP 8.3 atau lebih baru.

## 3. Persiapan Database

Skema dibuat dari migration aktual, bukan dari perkiraan:

```
deploy/infinityfree/fixpoint_service_schema.sql
```

Cara membuat ulang berkas tersebut (sudah dilakukan saat penyusunan):

```
mysqldump -h 127.0.0.1 -u root --no-data --skip-add-drop-table --routines=false fixpoint_service > deploy/infinityfree/fixpoint_service_schema.sql
```

Isi berkas: 10 pernyataan `CREATE TABLE` untuk `users`, `customers`, `services`, `schedules`, `devices`, `bookings`, `service_records`, `payments`, `personal_access_tokens`, dan tabel `migrations` (tabel pembukuan bawaan Laravel), beserta primary key, 12 foreign key, dan indeks. Tidak ada data pelanggan yang disertakan, sehingga aman diimpor ke database baru.

Data awal yang diperlukan setelah impor:

1. Dua baris pada `services`: `Servis Laptop` dan `Servis PC Desktop` (dapat ditambahkan lewat panel Admin setelah akun Admin dibuat).
2. Satu akun Admin dan satu akun Pemilik Usaha. Karena `php artisan db:seed` tidak tersedia di hosting, buat hash password di komputer lokal lalu jalankan `INSERT` melalui phpMyAdmin:

```
php -r "echo password_hash('GANTI_DENGAN_PASSWORD_KUAT', PASSWORD_BCRYPT, ['cost' => 12]), PHP_EOL;"
```

```
INSERT INTO users (email, password, role, created_at, updated_at)
VALUES ('admin@domain-anda', '<hash-hasil-perintah-di-atas>', 'ADMIN', NOW(), NOW());

INSERT INTO users (email, password, role, created_at, updated_at)
VALUES ('pemilik@domain-anda', '<hash-hasil-perintah-di-atas>', 'PEMILIK_USAHA', NOW(), NOW());
```

Jangan pernah memakai akun contoh `admin@fixpoint.test` / `Admin12345` pada instalasi yang dapat diakses publik.

## 4. Paket Aplikasi Production

Paket dibangun dengan urutan berikut (dijalankan di komputer lokal):

```
composer install --no-dev --optimize-autoloader --no-interaction
php artisan optimize
```

`php artisan optimize` menjalankan cache konfigurasi, route, dan view. Hasil build diverifikasi pada bagian 5.

Yang **tidak** diunggah: folder `.git`, `node_modules`, `tests`, `docs/demo-rehearsal`, file `.env` lokal, dan log lama. Yang **wajib** diunggah: `app`, `bootstrap`, `config`, `database`, `public`, `resources`, `routes`, `storage` (struktur dan folder kosong), `vendor`, `artisan`, `composer.json`, `composer.lock`.

Karena view tidak memakai `@vite`, folder `public/build` tidak diperlukan.

## 5. Uji Production di Komputer Lokal (sudah dilakukan)

Pengujian ini membuktikan konfigurasi production tidak menghasilkan error sebelum diunggah.

| Langkah | Perintah / tindakan | Hasil |
| --- | --- | --- |
| 1 | Menyalin proyek ke folder uji terpisah | Berhasil |
| 2 | `composer install --no-dev` | Berhasil, autoloader ter-optimasi |
| 3 | `.env` production: `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_DRIVER=file`, `CACHE_STORE=file`, `QUEUE_CONNECTION=sync` | Berhasil |
| 4 | `php artisan config:cache`, `route:cache`, `view:cache` | Berhasil tanpa error |
| 5 | `php artisan serve --host 127.0.0.1 --port 8010` | Server berjalan |
| 6 | `GET /` | HTTP 200, halaman beranda tampil dari view ter-cache |
| 7 | `GET /up` | HTTP 200 (health check Laravel) |
| 8 | `GET /login` | HTTP 200 |
| 9 | `GET /cek-status` | HTTP 200 |
| 10 | `GET /dashboard-admin` tanpa login | HTTP 302 ke `/login` (middleware auth bekerja) |

Hasil pengukuran respons lokal dicatat pada bagian 8.

## 6. Langkah Unggah ke InfinityFree (perlu akun)

1. Masuk ke panel `https://dash.infinityfree.com/` dengan akun pemilik.
2. Buat akun hosting dan subdomain (mis. `fixpoint.great-site.net`), catat nama subdomain.
3. Buka **MySQL Databases**, buat database baru. Catat: nama host MySQL (`sqlXXX.infinityfree.com`), nama database (`if0_xxxxxxxx_fixpoint`), username (`if0_xxxxxxxx`), dan password database.
4. Buka **Online File Manager** atau hubungkan klien FTP (FileZilla) memakai kredensial FTP dari panel. Folder tujuan: `htdocs`.
5. Unggah seluruh isi paket production ke `htdocs` sehingga `htdocs/artisan`, `htdocs/app`, `htdocs/public`, dan `htdocs/vendor` ada. FTP bawaan hosting hanya memberi satu akun; unggah `vendor` lebih dahulu lalu lanjutkan agar proses tidak terputus.
6. Buat berkas `htdocs/.htaccess` dengan isi dari `deploy/infinityfree/htdocs.htaccess`. Berkas ini mengarahkan semua permintaan ke `public/` dan menangani `storage/` tanpa symlink.
7. Buat `htdocs/.env` dari `deploy/infinityfree/env.production.example`, isi `APP_KEY`, data MySQL, dan `APP_URL`. Untuk `APP_KEY`, salin nilai dari `.env` lokal yang sudah memiliki `base64:...`, atau hasilkan baru dengan `php artisan key:generate --show` di komputer lokal. **Jangan menaruh nilai ini di repository atau dokumen.**
8. Buka phpMyAdmin dari panel, pilih database, tab **Import**, unggah `deploy/infinityfree/fixpoint_service_schema.sql`, lalu jalankan. Periksa bahwa 10 tabel terbentuk (9 tabel aplikasi + `migrations`) dan 12 foreign key ada.
9. Jalankan `INSERT` akun Admin dan Pemilik Usaha seperti pada bagian 3.
10. Pastikan folder `htdocs/storage/app/public/payment_proofs` ada. Bila belum, buat lewat File Manager dan beri izin tulis (755 pada folder; InfinityFree menjalankan PHP sebagai pemilik berkas).
11. Aktifkan **Force HTTPS** pada panel (menu SSL/HTTPS) agar cookie sesi aman.
12. Buka `https://<subdomain>/`. Bila muncul halaman beranda, lanjut ke bagian 7.

Bila terjadi kesalahan 500, periksa `htdocs/storage/logs/laravel.log` melalui File Manager; berkas ini tidak dapat dibuka dari browser karena `.htaccess` mengarahkan permintaan ke `public/`.

## 7. Pengujian Setelah Unggah (wajib, belum dijalankan)

| No | Butir uji | Kriteria lulus |
| --- | --- | --- |
| 1 | Halaman utama | HTTP 200, layanan dan jadwal tampil |
| 2 | Halaman `/ketersediaan-jadwal` | Status AVAILABLE/FULL/CLOSED tampil |
| 3 | Form booking | Booking tersimpan, Service ID muncul, `current_booking` bertambah |
| 4 | Data pelanggan dan perangkat | Tercatat di tabel `customers` dan `devices` melalui phpMyAdmin |
| 5 | `/cek-status` dengan Service ID | Status servis dan rincian pembayaran tampil |
| 6 | Login Admin | Masuk ke dashboard Admin |
| 7 | Login Pemilik Usaha | Masuk ke dashboard Pemilik, tidak dapat membuka halaman Admin (403) |
| 8 | Terima booking lalu diagnosis | Status menjadi CONFIRMED, diagnosis tersimpan |
| 9 | Servis selesai dengan biaya akhir | Status COMPLETED, `payments.amount` terisi |
| 10 | Konfirmasi pembayaran CASH | `payment_status` menjadi PAID, `cash_received` tersimpan |
| 11 | Konfirmasi pembayaran QRIS | Ditolak sebelum bukti diunggah, berhasil setelah bukti ada |
| 12 | Unggah bukti QRIS | Berkas tersimpan di `storage/app/public/payment_proofs`, dapat dibuka kembali dari panel Admin |
| 13 | Pembatalan booking | Alasan tersimpan di `cancellation_reason`, slot jadwal kembali |
| 14 | Riwayat servis | Filter tanggal bekerja, ekspor PDF dan Excel terunduh |
| 15 | Laporan pemasukan | Hanya transaksi PAID yang dihitung |
| 16 | Tampilan mobile | Layout dapat dipakai pada lebar 390 px |
| 17 | HTTPS | Tidak ada peringatan sertifikat, cookie `Secure` aktif |
| 18 | Error log | Tidak ada error baru pada `storage/logs/laravel.log` |
| 19 | Refresh setelah login | Session tetap aktif, tidak kembali ke halaman login |
| 20 | Respons halaman | Domestik < 1,5 detik (ukuran dengan DevTools/`curl -w "%{time_total}"`) |

## 8. Hasil Pengukuran yang Sudah Ada

| Pengukuran | Lingkungan | Hasil |
| --- | --- | --- |
| Waktu respons halaman beranda | Lokal, `php artisan serve`, mode production dengan cache | Dicatat pada laporan akhir Sprint 4 |
| Waktu respons `/up` | Lokal | Dicatat pada laporan akhir Sprint 4 |
| Waktu respons URL publik InfinityFree | - | Belum ada (belum diunggah) |

Target SOP maksimal 1,5 detik baru dapat dinyatakan terpenuhi setelah pengukuran pada URL publik.

## 9. Konfigurasi Production yang Wajib

| Variabel | Nilai | Alasan |
| --- | --- | --- |
| `APP_ENV` | `production` | Menonaktifkan perilaku pengembangan |
| `APP_DEBUG` | `false` | Tidak menampilkan stack trace dan query |
| `APP_KEY` | `base64:...` | Enkripsi cookie dan session; jangan dibagikan |
| `APP_URL` | URL HTTPS final | Dipakai untuk pembentukan URL |
| `SESSION_DRIVER` | `file` | Tabel `sessions` tidak dibuat oleh migration proyek |
| `SESSION_SECURE_COOKIE` | `true` | Cookie hanya dikirim melalui HTTPS |
| `CACHE_STORE` | `file` | Tidak ada tabel `cache` |
| `QUEUE_CONNECTION` | `sync` | Tidak ada cron/worker |
| `LOG_LEVEL` | `error` | Mengurangi ukuran log pada kuota terbatas |

Yang dilarang: mengunggah `.env` asli ke GitHub, menuliskan password database pada dokumen, menampilkan `APP_KEY`, dan mengaktifkan `APP_DEBUG=true` pada production.

## 10. Perawatan dan Pembaruan

1. Untuk memperbarui kode: unduh repository terbaru di lokal, jalankan `composer install --no-dev`, `php artisan optimize`, lalu unggah folder yang berubah.
2. Setelah mengubah `.env` di hosting, hapus cache konfigurasi. Karena tidak ada Artisan di server, hapus berkas `htdocs/bootstrap/cache/config.php`, `routes-v7.php`, dan isi `htdocs/storage/framework/views/` melalui File Manager.
3. Jangan menghapus `storage/app/public/payment_proofs`; berkas tersebut adalah bukti pembayaran pelanggan.
4. Cadangkan database secara berkala melalui menu **Export** pada phpMyAdmin.

## 11. Berkas Terkait

| Berkas | Fungsi |
| --- | --- |
| `deploy/infinityfree/htdocs.htaccess` | Routing ke `public/` dan penyajian `storage/` |
| `deploy/infinityfree/env.production.example` | Contoh `.env` production tanpa nilai rahasia |
| `deploy/infinityfree/fixpoint_service_schema.sql` | Skema database dari migration aktual |
| `docs/02-deployment/OFFLINE_DEPLOYMENT.md` | Fallback lokal tanpa internet |
| `run_local_offline.bat` | Skrip menjalankan aplikasi di LAN |
