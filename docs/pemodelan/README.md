# Pemodelan Sistem — FixPoint Service

Kumpulan diagram dan dokumentasi database untuk **FixPoint Service — Sistem Informasi Booking & Manajemen Jasa Servis Laptop dan PC Berbasis Web**.

Seluruh diagram disusun dari **implementasi aktual** di repository ini: `routes/web.php`, `routes/api.php`, controller di `app/Http/Controllers/`, model di `app/Models/`, view di `resources/views/`, serta seluruh migration di `database/migrations/`.

## Aktor

| Aktor | Autentikasi | Keterangan |
|-------|-------------|------------|
| Customer | Tidak | Booking dan cek status memakai Service ID, tanpa akun/registrasi |
| Admin | Ya | Mengelola jadwal, layanan, booking, proses servis, pembayaran, riwayat |
| Pemilik Usaha | Ya | Read-only: dashboard pemantauan, riwayat servis, laporan pemasukan |

**Teknisi bukan aktor aplikasi.** Seluruh pencatatan teknis dilakukan oleh Admin yang login.

## Struktur Berkas

```
docs/pemodelan/
├── README.md
├── use-case-diagram.puml          # PlantUML — use case 3 aktor
├── erd.mmd                        # Mermaid — ERD 8 tabel
├── database-schema.md             # Dokumentasi kolom, tipe, index, relasi
├── database-schema.sql            # DDL MySQL sesuai migration
├── sequence/
│   ├── sequence-booking.mmd
│   ├── sequence-pengelolaan-jadwal.mmd
│   ├── sequence-konfirmasi-booking.mmd
│   ├── sequence-proses-servis.mmd
│   ├── sequence-cek-status.mmd
│   ├── sequence-pembayaran.mmd
│   ├── sequence-upload-bukti-qris.mmd
│   └── sequence-monitoring-pemilik.mmd
├── flowchart/
│   ├── flowchart-customer.mmd
│   ├── flowchart-admin.mmd
│   └── flowchart-pemilik.mmd
├── user-flow/
│   ├── user-flow-customer.mmd
│   ├── user-flow-admin.mmd
│   └── user-flow-pemilik.mmd
└── activity/
    ├── activity-booking-customer.puml
    ├── activity-status-customer.puml
    ├── activity-kelola-jadwal-admin.puml
    ├── activity-kelola-booking-admin.puml
    ├── activity-proses-servis-admin.puml
    └── activity-monitoring-pemilik.puml
```

## Gambar Hasil Render

Seluruh diagram juga tersedia sebagai PNG di `docs/pemodelan/images/` (22 berkas, skala 2x):

| Kelompok | Berkas PNG |
|----------|------------|
| Use case | `use-case-diagram.png` |
| ERD | `erd.png` |
| Sequence (8) | `sequence-booking.png`, `sequence-pengelolaan-jadwal.png`, `sequence-konfirmasi-booking.png`, `sequence-proses-servis.png`, `sequence-cek-status.png`, `sequence-pembayaran.png`, `sequence-upload-bukti-qris.png`, `sequence-monitoring-pemilik.png` |
| Flowchart (3) | `flowchart-customer.png`, `flowchart-admin.png`, `flowchart-pemilik.png` |
| User flow (3) | `user-flow-customer.png`, `user-flow-admin.png`, `user-flow-pemilik.png` |
| Activity (6) | `activity-booking-customer.png`, `activity-status-customer.png`, `activity-kelola-jadwal-admin.png`, `activity-kelola-booking-admin.png`, `activity-proses-servis-admin.png`, `activity-monitoring-pemilik.png` |

Catatan tentang jadwal: Admin **dapat mengubah tanggal, jam, dan jumlah slot** melalui menu **Jadwal Servis** (`/admin/jadwal-servis` → Edit), atau lewat **Edit cepat** pada setiap baris tabel untuk mengubah jam dan slot tanpa berpindah halaman. Perubahan tersimpan di tabel `schedules` dan langsung tampil pada halaman **Ketersediaan Jadwal** pelanggan. Alur ini digambarkan di `sequence-pengelolaan-jadwal.mmd`, `activity-kelola-jadwal-admin.mmd`, dan `flowchart-admin.mmd`.

## Fungsi Setiap Diagram

### Use Case — `use-case-diagram.puml`
Memetakan seluruh use case ke tiga aktor: Customer (9 use case), Admin (16), Pemilik Usaha (6), plus Login/Logout bersama. Menegaskan batas sistem dan bahwa Customer tidak berakun.

### ERD — `erd.mmd`
Relasi 8 tabel domain: `users`, `customers`, `services`, `schedules`, `devices`, `bookings`, `service_records`, `payments`, lengkap dengan PK, FK, UK, dan kardinalitas sesuai foreign key yang benar-benar terpasang.

### Sequence Diagram
| Berkas | Alur yang digambarkan |
|--------|-----------------------|
| `sequence-booking.mmd` | Customer mengirim booking; validasi, transaksi, penguncian jadwal, pembuatan Service ID, pembayaran awal, increment kuota |
| `sequence-pengelolaan-jadwal.mmd` | Admin menambah, mengedit waktu/kuota, membuka-menutup, dan menghapus jadwal beserta seluruh cabang penolakan |
| `sequence-konfirmasi-booking.mmd` | Admin memverifikasi booking dan membuat catatan servis |
| `sequence-proses-servis.mmd` | Diagnosis, estimasi biaya, transisi status servis, biaya akhir, dan pembentukan tagihan |
| `sequence-cek-status.mmd` | Customer mencari Service ID, melihat status, dan membatalkan booking |
| `sequence-pembayaran.mmd` | Verifikasi CASH (uang diterima) dan QRIS (bukti), minta upload ulang, cetak struk |
| `sequence-upload-bukti-qris.mmd` | Validasi file, penyimpanan bukti, perubahan status pembayaran, penanganan file gagal |
| `sequence-monitoring-pemilik.mmd` | Statistik dashboard, bukti QRIS, riwayat servis, laporan pemasukan, dan ekspor |

### Flowchart
Menunjukkan alur keputusan (berhasil/gagal) per aktor:
- `flowchart-customer.mmd` — booking, Service ID, cek status, pembatalan, unggah bukti.
- `flowchart-admin.mmd` — login, kelola jadwal, layanan, verifikasi, diagnosis, pembayaran, laporan.
- `flowchart-pemilik.mmd` — login, monitoring, bukti, riwayat, laporan pemasukan.

### User Flow
Fokus pada **perpindahan halaman** yang dialami pengguna (bukan logika sistem): titik masuk, urutan halaman, dan tindakan lanjutan.

### Activity Diagram (PlantUML, swimlane)
| Berkas | Proses |
|--------|--------|
| `activity-booking-customer.puml` | Booking Customer dari form hingga Service ID |
| `activity-status-customer.puml` | Cek status, pembatalan, dan unggah bukti QRIS |
| `activity-kelola-jadwal-admin.puml` | Tambah, edit, tutup/buka, dan hapus jadwal |
| `activity-kelola-booking-admin.puml` | Verifikasi dan pembatalan booking |
| `activity-proses-servis-admin.puml` | Diagnosis hingga pembayaran lunas dan struk |
| `activity-monitoring-pemilik.puml` | Pemantauan dashboard, bukti, riwayat, laporan |

### Database Schema
- `database-schema.md` — dokumentasi tiap tabel: kolom, tipe, nullability, default, PK/FK/unique, index, relasi, dan catatan keterbatasan.
- `database-schema.sql` — DDL MySQL yang setara dengan migration (dokumentasi, bukan pengganti `php artisan migrate`).

## Cara Membuka Diagram

### Opsi 1 — Visual Studio Code (paling praktis)
1. Install ekstensi **PlantUML** (`jebbs.plantuml`) dan **Markdown Preview Mermaid Support** (`bierner.markdown-mermaid`).
2. Buka berkas `.puml`, tekan `Alt+D` untuk pratinjau.
3. Buka berkas `.mmd` melalui **Command Palette → Mermaid: Preview** (ekstensi Mermaid Preview) atau tempel isinya ke https://mermaid.live.

### Opsi 2 — Render PlantUML via CLI
```bash
# jika plantuml terpasang
plantuml -tpng docs/pemodelan/use-case-diagram.puml
plantuml -tpng docs/pemodelan/activity/*.puml

# atau via jar
java -jar plantuml.jar -tpng docs/pemodelan/activity/activity-booking-customer.puml
```
Hasil PNG/SVG akan berada di folder yang sama. Jangan commit hasil render bila tidak diminta.

### Opsi 3 — Render Mermaid via CLI
```bash
npx -y @mermaid-js/mermaid-cli -i docs/pemodelan/erd.mmd -o erd.png
npx -y @mermaid-js/mermaid-cli -i docs/pemodelan/sequence/sequence-booking.mmd -o booking.png
```
Atau buka https://mermaid.live lalu tempel isi berkas `.mmd`.

### Opsi 4 — GitHub
GitHub merender blok ` ```mermaid ` pada Markdown secara otomatis. Untuk melihat diagram, salin isi berkas `.mmd` ke dalam blok tersebut pada issue/README.

## Cara Render Ulang Gambar

PlantUML (butuh Java) dan Mermaid (memakai Chromium yang sudah ada di project):

```bash
# PlantUML — use case + 6 activity
java -Djava.awt.headless=true -jar .tools/plantuml/plantuml.jar -tpng \
  -o "$(pwd)/docs/pemodelan/images" \
  docs/pemodelan/use-case-diagram.puml docs/pemodelan/activity/*.puml

# Mermaid — ERD, 8 sequence, 3 flowchart, 3 user flow
node .tools/mermaid/render.cjs
```

Skrip `render.cjs` memakai `mermaid.render()` di Chromium headless lalu menyimpan elemen SVG sebagai PNG 2x. Bila `.tools/` belum ada, lihat bagian *Opsi 2/Opsi 3* di atas untuk instalasi manual.

## Catatan Validitas

- Diagram disusun manual berdasarkan pembacaan kode, bukan hasil generate otomatis.
- Seluruh diagram telah **dirender** menjadi PNG. PlantUML 1.2026.10 dan Mermaid 11 dipakai saat render; gambar diperiksa secara visual (tidak kosong, tidak terpotong).
- Validitas sintaks juga diperiksa struktural: blok `alt/else/end`, `if/endif`, `subgraph/end`, serta pasangan `@startuml/@enduml`.
- Bila menemukan perbedaan antara diagram dan aplikasi, **kode adalah sumber kebenaran**; perbarui diagram setelah perubahan kode.
- Rujukan audit lengkap (temuan bug dan keterbatasan) ada pada laporan audit Sprint 3, bukan di folder ini.

## Sumber Implementasi Utama

| Aspek | Berkas |
|-------|--------|
| Route & middleware | `routes/web.php`, `routes/api.php`, `bootstrap/app.php` |
| Booking & servis | `app/Http/Controllers/BookingController.php` |
| Cek status & bukti | `app/Http/Controllers/StatusController.php`, `PaymentProofController.php` |
| Jadwal, layanan, pembayaran | `app/Http/Controllers/AdminPanelController.php` |
| Laporan & ekspor | `app/Http/Controllers/ReportController.php` |
| Autentikasi | `app/Http/Controllers/AuthController.php`, `app/Http/Middleware/RoleMiddleware.php` |
| Skema database | `database/migrations/*.php` |
