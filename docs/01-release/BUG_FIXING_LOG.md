# BUG FIXING LOG - SPRINT 4 (RELEASE CANDIDATE)

**Proyek:** FixPoint Service - Sistem Informasi Booking dan Manajemen Jasa Servis Laptop dan PC
**Tahap:** S4-01 Final Bug Fixing (Release Candidate)
**Tanggal pemeriksaan:** 15 November 2026
**Versi yang disiapkan:** `v1.0.0-rc`
**Branch kerja:** `feature/backend` (diintegrasikan ke `main`)

---

## 1. Ringkasan Bug

| Tingkat | Jumlah ditemukan | Ditutup | Masih terbuka |
| --- | --- | --- | --- |
| CRITICAL | 1 | 1 | 0 |
| MAJOR | 2 | 2 | 0 |
| MINOR | 3 | 1 | 2 |
| Catatan (bukan bug kode) | 2 | 1 | 1 |

Tidak ada bug CRITICAL atau MAJOR yang dibiarkan terbuka, sehingga Release Candidate dapat ditandai.

## 2. Tabel Perbaikan

| No | Tiket | Modul | Masalah | Penyebab | Solusi | File Berubah | Status |
| --- | --- | --- | --- | --- | --- | --- | --- |
| 1 | S4-01-01 | Autentikasi / Login | Pengujian penerimaan Sprint 3 gagal: login dengan role yang tidak sesuai tidak menghasilkan error pada field `email` (`Session missing error: email`) | `AuthController@login` mengirim pesan role melalui `withErrors(['role' => ...])`, sedangkan kontrak pengujian Sprint 3 dan pola pesan gagal login memakai key `email`. Pesan tampil, tetapi tidak konsisten dan menyalahi kontrak pengujian | Pesan role sekarang dikirim melalui key `email`, sama seperti pesan kredensial salah, sehingga form login menampilkan pesan pada tempat yang sama dan pengujian kembali lulus | `app/Http/Controllers/AuthController.php` | DITUTUP |
| 2 | S4-01-02 | API | Tiga route `api/*` menunjuk method controller yang tidak ada (`checkSchedules`, `show`, `updateStatus`) sehingga permintaan ke route tersebut berakhir dengan HTTP 500 | Route didaftarkan pada Sprint 2 sebelum method controller dibuat; route tidak pernah dipakai antarmuka dan tidak tercakup pengujian | Route yang belum berfungsi dihapus dari `routes/api.php` agar tidak ada endpoint publik yang menghasilkan 500. Endpoint yang benar-benar berfungsi (`POST /api/bookings`) dipertahankan. Pembuatan endpoint baru berada di luar Code Freeze dan dicatat sebagai pengembangan lanjutan | `routes/api.php` | DITUTUP |
| 3 | S4-01-03 | Middleware | Docblock `RoleMiddleware::handle()` rusak dan tidak dapat dibaca (`@param \Closure(\Illuminate\Http\Request):` dengan tanda kutip liar), menyulitkan penelusuran parameter | Kesalahan penulisan saat Sprint 3 | Docblock diperbaiki menjelaskan parameter `$next` dan daftar `$roles`; perilaku kode tidak diubah | `app/Http/Middleware/RoleMiddleware.php` | DITUTUP |
| 4 | S4-01-04 | Konfigurasi / Deployment | Instalasi baru gagal membuat session bila mengikuti `.env.example` apa adanya karena `SESSION_DRIVER=database` sementara migration proyek tidak membuat tabel `sessions`, `cache`, maupun `jobs` | `.env.example` adalah bawaan skeleton Laravel, sedangkan migration proyek memakai skema kustom tanpa tabel framework tersebut | Tidak mengubah `.env.example` (agar konfigurasi lokal yang sudah berjalan tidak rusak, sesuai Code Freeze). Solusi diterapkan pada berkas deployment: `deploy/infinityfree/env.production.example` memakai `SESSION_DRIVER=file`, `CACHE_STORE=file`, `QUEUE_CONNECTION=sync`, dan hal ini didokumentasikan pada `docs/02-deployment/DEPLOYMENT_GUIDE.md` serta Bab VII dokumentasi teknis | `deploy/infinityfree/env.production.example`, `docs/02-deployment/DEPLOYMENT_GUIDE.md`, `tools/docs/technical-docs.md` | DITUTUP (terdokumentasi) |
| 5 | S4-01-05 | Build asset | `public/build` tidak ada dan tidak ada direktif `@vite` di seluruh view, sementara `package.json` masih mencantumkan Vite/Tailwind, sehingga tim berisiko menjalankan `npm run build` tanpa efek | Sisa scaffolding Vite yang tidak dipakai; seluruh CSS ditulis di dalam Blade | Tidak ada perubahan kode. Perilaku dipastikan benar: aplikasi berjalan tanpa Node.js, dan fakta ini dicatat pada README serta dokumentasi teknis agar tidak menimbulkan langkah deployment yang salah | `README.md`, `tools/docs/technical-docs.md` (dokumentasi) | DITUTUP (bukan cacat kode) |
| 6 | S4-01-06 | Repo / Integrasi | Branch `main` tertinggal 10 commit di belakang `feature/backend`; pekerjaan Sprint 3 hanya ada di branch fitur | Pekerjaan belum di-merge ke `main` | `main` di-merge secara fast-forward dari `feature/backend` setelah seluruh pengujian lulus; tidak ada commit yang ditimpa | Riwayat Git (`main`) | DITUTUP |
| 7 | S4-01-07 | Repo / Integrasi | Perubahan `resources/views/layouts/admin.blade.php` belum di-commit sehingga berisiko hilang | Pekerjaan frontend masih di working tree | Perubahan di-commit apa adanya tanpa mengubah isinya, agar pekerjaan anggota tim tersimpan | `resources/views/layouts/admin.blade.php` | DITUTUP |
| 8 | S4-01-08 | Repo / Kebersihan kode | Terdapat controller dan satu berkas yang tidak terhubung ke route mana pun dan merujuk view yang tidak ada (`PageController` ke `welcome.welcome`, `CustomerController`/`ServiceController`/`UserController` ke `crud.*`, serta `app/Http/Controllers/dashboardpemilik.php` yang bukan kelas PHP valid) | Sisa pengembangan Sprint 1-2 yang ditinggalkan | Tidak dihapus pada tahap ini karena Code Freeze melarang perubahan besar dan penghapusan kode di luar kebutuhan bug fixing; tidak menimbulkan error saat aplikasi berjalan karena tidak ada route yang menunjuk ke sana | tidak ada (tercatat) | TERBUKA (prioritas rendah) |
| 9 | S4-01-09 | Dokumentasi | `docs/03-manual/USER_MANUAL.pdf` yang dinyatakan sudah selesai tidak ditemukan di repository (tidak ada di working tree, tidak terlacak Git, dan tidak ada di riwayat seluruh branch maupun tag) | Berkas kemungkinan disimpan di luar repository | Berkas tidak dibuat ulang dan tidak digantikan sesuai instruksi; keberadaan berkas dilaporkan kepada pengguna agar manual asli disalin ke `docs/03-manual/` sebelum penyerahan | tidak ada | TERBUKA (perlu tindakan pengguna) |

## 3. Pemeriksaan Kebersihan Kode Debug

| Pemeriksaan | Perintah / cara | Hasil |
| --- | --- | --- |
| `dd()`, `dump()`, `var_dump()`, `ray()` | Pencarian teks pada `app/`, `routes/`, `resources/`, `database/` | Tidak ditemukan |
| `console.log()` pada JavaScript di dalam Blade | Pencarian teks pada `resources/views` | Tidak ditemukan |
| Logging operasional | `storage/logs/laravel.log`, konfigurasi `LOG_LEVEL` | Dipertahankan; `report($exception)` pada `bootstrap/app.php` tetap dipakai untuk mencatat `QueryException` |

Catatan: pencarian `in_array(` juga diperiksa karena sekilas mirip `dd(`; seluruh kemunculannya adalah pemakaian fungsi PHP yang sah.

## 4. Hasil Pengujian Setelah Perbaikan

Seluruh pengujian dijalankan pada lingkungan pengembangan Windows, PHP 8.4.26, Composer 2.10.3, MySQL lokal.

| No | Perintah | Hasil |
| --- | --- | --- |
| 1 | `composer validate --no-check-publish` | `./composer.json is valid` |
| 2 | `php artisan test` | 30 pengujian lulus, 131 assertion, 0 gagal (sebelum perbaikan: 1 gagal, 29 lulus) |
| 3 | `php artisan route:list` | Seluruh route aplikasi terdaftar; tidak ada route yang menunjuk method yang tidak ada setelah perbaikan |
| 4 | `php artisan migrate:status` | 10 migration berstatus `Ran`, tidak ada yang tertunda |
| 5 | Pemeriksaan sintaks PHP (`php -l`) pada `app/`, `routes/`, `database/`, `tests/` | Tidak ada kesalahan sintaks |
| 6 | Pemeriksaan `storage/logs/laravel.log` | Tidak ada error baru dari alur pengujian; log lama berisi riwayat pengembangan |
| 7 | Pengujian alur booking (otomatis) | `test_booking_valid_tersimpan_dan_service_id_terbentuk`, `test_booking_pada_jadwal_penuh_ditolak`, `test_booking_tanpa_metode_pembayaran_ditolak`, `test_booking_dengan_nomor_telepon_tidak_valid_ditolak` lulus |
| 8 | Pengujian autentikasi dan hak akses | 8 pengujian login, tamu, Pemilik, dan Admin lulus |
| 9 | Pengujian laporan dan ekspor | 7 pengujian riwayat servis, pemasukan, PDF, dan XLSX lulus |
| 10 | Pengujian halaman error | `test_halaman_404_kustom_tampil_tanpa_stack_trace`, `test_halaman_403_tampil_saat_akses_ditolak` lulus |
| 11 | Pemeriksaan build production (lihat `docs/02-deployment/DEPLOYMENT_GUIDE.md`) | `php artisan optimize` berhasil dengan `APP_ENV=production`, `APP_DEBUG=false`; aplikasi merespons HTTP 200 pada `/` dan `/up` |

Pengujian yang **belum** dapat dilakukan:

- Pengujian browser otomatis (end-to-end). Repository belum memiliki Dusk/Playwright.
- Pengujian upload bukti pembayaran melalui berkas gambar nyata pada lingkungan production hosting, karena deployment publik belum dapat dijalankan (lihat laporan akhir).
- Pengujian dua akun berbeda pada URL publik.

## 5. Status Code Freeze

| Butir | Status |
| --- | --- |
| Penambahan fitur baru | Dihentikan sejak 15 November 2026 |
| Perubahan besar UI | Dihentikan |
| Perubahan alur bisnis | Dihentikan |
| Perubahan skema database | Dihentikan (tidak ada migration baru pada Sprint 4) |
| Refactor besar | Dihentikan |
| Fokus pekerjaan | Bug fixing, stabilitas, keamanan, dokumentasi, kesiapan rilis |

## 6. Status Git Tag

| Butir | Status |
| --- | --- |
| Annotated tag `v1.0.0-rc` | Dibuat setelah pengujian pada bagian 4 lulus dan `main` diperbarui |
| Tag lama yang ditimpa | Tidak ada (sebelumnya repository belum memiliki tag) |
| `git push --force` | Tidak digunakan |
| `git reset --hard` / `git clean -fd` | Tidak digunakan |
| Penghapusan branch | Tidak dilakukan |

## 7. Bug Terbuka dan Rencana

| No | Tiket | Ringkasan | Prioritas | Rencana |
| --- | --- | --- | --- | --- |
| 1 | S4-01-08 | Controller dan berkas sisa pengembangan yang tidak terpakai | Rendah | Dibersihkan pada iterasi berikutnya setelah penilaian, dengan memastikan tidak ada route yang bergantung padanya |
| 2 | S4-01-09 | `docs/03-manual/USER_MANUAL.pdf` tidak ada di repository | Sedang (administratif) | Pengguna menyalin berkas manual asli ke `docs/03-manual/` sebelum penyerahan; dokumen tersebut tidak dibuat ulang oleh agen |

Kedua butir di atas tidak memengaruhi stabilitas aplikasi yang dirilis dan bukan bug CRITICAL/MAJOR.
