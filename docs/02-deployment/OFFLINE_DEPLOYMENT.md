# OFFLINE DEPLOYMENT (LAN CONTINGENCY) - FIXPOINT SERVICE

**Tahap:** S4-02 bagian Offline LAN Contingency
**Tanggal:** 15 November 2026
**Berkas utama:** `run_local_offline.bat`

---

## 1. Tujuan

Mode offline dipakai bila internet di lokasi presentasi terputus. Aplikasi tetap berjalan penuh pada satu laptop dan dapat dibuka dari perangkat penguji (ponsel/laptop lain) melalui jaringan WiFi atau hotspot yang sama. Tidak ada data yang dikirim ke internet karena seluruh komponen berjalan lokal.

## 2. Prasyarat di Laptop Penyelenggara

| Kebutuhan | Keterangan |
| --- | --- |
| PHP 8.3+ | Terpasang, mis. `C:\xampp\php\php.exe`, dan dapat dipanggil sebagai `php` |
| MySQL / MariaDB | Terpasang lokal, mis. XAMPP MySQL pada port 3306 |
| Database `fixpoint_service` | Sudah dibuat dan sudah dimigrasi |
| Folder `vendor/` | Sudah ada (hasil `composer install`) |
| Berkas `.env` | Sudah berisi `APP_KEY` dan kredensial MySQL lokal |
| Port 8000 | Tidak dipakai aplikasi lain |

## 3. Cara Menjalankan (target maksimal 2 menit)

1. Klik dua kali `run_local_offline.bat` pada folder proyek (atau jalankan dari cmd: `run_local_offline.bat`).
2. Skrip akan menampilkan langkah yang sedang dijalankan:
   - memeriksa PHP beserta versinya;
   - memeriksa MySQL pada port 3306 dan menyalakannya bila belum berjalan (service MySQL, atau `mysqld.exe` XAMPP);
   - memeriksa `.env`, dan menjalankan `php artisan key:generate --force` bila `APP_KEY` belum ada;
   - memeriksa koneksi database dengan `php artisan migrate:status`;
   - menjalankan `php artisan optimize:clear` untuk membersihkan cache;
   - memastikan `storage/app/public/payment_proofs` ada dan `public/storage` tertaut;
   - membaca alamat IPv4 aktif dengan `ipconfig` dan menampilkannya.
3. Alamat yang ditampilkan:
   - Di laptop itu sendiri: `http://127.0.0.1:8000`
   - Dari perangkat lain: `http://<IP-LAN-aktual>:8000` (contoh format saja, bukan IP tetap)
4. Buka alamat tersebut pada browser perangkat penguji.
5. Untuk menghentikan server: tekan `CTRL+C` pada jendela skrip. Server berhenti dan skrip menampilkan tombol `pause` agar jendela tidak langsung tertutup.

## 4. Yang Sudah Diuji dan Yang Belum

| Butir | Status |
| --- | --- |
| Skrip memeriksa PHP dan menampilkan versi | Diuji (PHP 8.4.26 terdeteksi) |
| Skrip memeriksa port 3306 dan MySQL lokal | Diuji (MySQL lokal berjalan) |
| `php artisan optimize:clear` dijalankan oleh skrip | Diuji |
| Folder `storage/app/public/payment_proofs` tersedia | Diuji (berisi 9 berkas bukti pembayaran dari pengujian) |
| `php artisan serve --host 0.0.0.0 --port 8000` | Diuji pada baris perintah terpisah: server menerima permintaan dari loopback |
| Akses dari perangkat lain melalui IP LAN | **Belum diuji** (memerlukan perangkat kedua pada jaringan yang sama) |
| Aturan firewall Windows untuk port 8000 | **Belum diuji**; lihat bagian 5 |

Catatan kejujuran: pengujian akses dari perangkat kedua tidak dapat dilakukan dalam lingkungan kerja ini karena tidak ada perangkat penguji kedua. Langkah pengujiannya ada pada bagian 7.

## 5. Firewall dan Port

Bila perangkat lain tidak dapat membuka `http://<IP-LAN>:8000`:

1. Periksa apakah server benar-benar mendengarkan pada semua antarmuka: `netstat -an | findstr :8000` harus menampilkan `0.0.0.0:8000` atau `[::]:8000`.
2. Izinkan port 8000 pada Windows Firewall (jalankan Command Prompt sebagai Administrator, sekali saja):

```
netsh advfirewall firewall add rule name="FixPoint Laravel 8000" dir=in action=allow protocol=TCP localport=8000
```

3. Pastikan jaringan pada Windows ditandai **Private**, bukan **Public**; pada profil Public Windows memblokir koneksi masuk secara default.
4. Pastikan perangkat penguji berada pada SSID/hotspot yang sama dan tidak memakai VPN.
5. Beberapa jaringan kampus/kantor mengaktifkan *client isolation* yang memblokir komunikasi antarperangkat. Dalam kondisi itu gunakan hotspot ponsel: laptop penyelenggara dan perangkat penguji terhubung ke hotspot yang sama.

## 6. Kesiapan Konten Offline

| Kebutuhan | Keterangan |
| --- | --- |
| CSS dan JavaScript | Tertanam di dalam Blade, tidak memerlukan internet |
| Font | Memakai font sistem, tidak memuat Google Fonts |
| Gambar | `public/images/logo.fixpoint.jpeg` dan `public/qris/qris.toko.png` tersedia lokal |
| QRIS | Gambar QRIS statis lokal; pembayaran tetap dilakukan pelanggan melalui aplikasi bank, tidak memerlukan server aplikasi |
| Bukti pembayaran | Diunggah ke `storage/app/public/payment_proofs` dan dibaca kembali lewat controller |
| Database | MySQL lokal; seluruh data uji dapat disiapkan sebelum presentasi |
| Ekspor PDF/Excel | Berjalan lokal dengan dompdf dan `SimpleXlsx` |

## 7. Prosedur Uji Cepat Sebelum Presentasi (5 menit)

1. Jalankan `run_local_offline.bat`, catat IP LAN yang muncul.
2. Buka `http://127.0.0.1:8000` di laptop: pastikan beranda tampil.
3. Dari ponsel pada WiFi/hotspot yang sama, buka `http://<IP-LAN>:8000`.
4. Buat satu booking uji dari ponsel; pastikan Service ID muncul.
5. Buka `/cek-status` pada laptop dengan Service ID tersebut; pastikan data tampil (membuktikan database lokal dipakai bersama).
6. Login Admin, terima booking, isi diagnosis dan biaya akhir, konfirmasi pembayaran CASH.
7. Unggah satu bukti QRIS dari ponsel pada booking QRIS; buka dari panel Admin untuk memastikan berkas tersimpan dan dapat dibuka kembali.
8. Buka `http://<IP-LAN>:8000/pemilik/laporan-pemasukan` sebagai Pemilik Usaha; pastikan angka pemasukan bertambah.

Bila langkah 3 gagal, ulangi bagian 5 sebelum mencoba lagi.

## 8. Data Uji yang Disarankan

| Data | Nilai |
| --- | --- |
| Jadwal | Satu slot hari ini dengan `max_booking` 3, satu slot besok, satu slot ditutup |
| Layanan | `Servis Laptop` dan `Servis PC Desktop` aktif |
| Akun | Admin dan Pemilik Usaha dengan password yang hanya diketahui penyaji |
| Booking QRIS | Satu booking selesai dengan tagihan, untuk demonstrasi unggah bukti |
| Booking CASH | Satu booking selesai dengan tagihan, untuk demonstrasi konfirmasi tunai |

## 9. Pemulihan Bila Terjadi Masalah

| Gejala | Tindakan |
| --- | --- |
| Skrip berhenti di pemeriksaan MySQL | Nyalakan MySQL dari XAMPP Control Panel, lalu jalankan ulang skrip |
| `artisan` gagal dijalankan | Periksa `vendor/` dan `.env`; jalankan `composer install` di laptop tersebut |
| Halaman putih atau error 500 | Buka `storage/logs/laravel.log`; jalankan `php artisan optimize:clear` |
| Berkas bukti tidak dapat dibuka | Pastikan `public/storage` ada (`php artisan storage:link`) dan folder `storage/app/public/payment_proofs` dapat ditulis |
| Perangkat lain tidak bisa terhubung | Ulangi bagian 5 (firewall, profil jaringan, SSID sama, tanpa VPN) |
| Port 8000 dipakai proses lain | Hentikan proses tersebut, atau ubah port pada skrip dan sesuaikan alamat yang dibuka |

## 10. Batasan Mode Offline

- Hanya dapat diakses dari jaringan lokal; tidak dapat dipakai pelanggan dari luar lokasi.
- Tanggung jawab keamanan jaringan ada pada penyelenggara; jangan memakai hotspot publik terbuka saat data pelanggan ditampilkan.
- Tidak ada HTTPS pada mode ini karena server pengembangan Laravel memakai HTTP. Ini hanya untuk demonstrasi lokal, bukan production.
- Data yang dibuat selama demonstrasi masuk ke database lokal dan sebaiknya dibersihkan atau didokumentasikan setelah presentasi.
