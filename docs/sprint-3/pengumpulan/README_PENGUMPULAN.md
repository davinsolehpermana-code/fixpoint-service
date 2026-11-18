# README Pengumpulan Sprint 3 — FixPoint Service

Dokumen ini menjelaskan isi paket pengumpulan S3-06 (Basic Security) dan S3-07
(Performance & Usability + Pengelolaan Jadwal Admin) untuk aplikasi FixPoint Service.

Tanggal pengerjaan: 9 November 2026

## 1. Dokumen yang harus diunggah

| Checklist | Berkas | Lokasi |
|---|---|---|
| S3-06 laporan (Word) | S3-06_Basic_Security_Report.docx | S3-06_BASIC_SECURITY/ |
| S3-06 laporan (PDF) | S3-06_Basic_Security_Report.pdf | S3-06_BASIC_SECURITY/ |
| S3-06 rekap pengujian | S3-06_Security_Test_Report.xlsx | S3-06_BASIC_SECURITY/ |
| S3-07 laporan (Word) | S3-07_Performance_Usability_Report.docx | S3-07_PERFORMANCE/ |
| S3-07 laporan (PDF) | S3-07_Performance_Usability_Report.pdf | S3-07_PERFORMANCE/ |
| S3-07 pengukuran | S3-07_Performance_Test.xlsx | S3-07_PERFORMANCE/ |
| S3-07 index database | database_index.sql | S3-07_PERFORMANCE/ |
| S3-07 jadwal (Word) | S3-07_Admin_Schedule_Management_Report.docx | S3-07_ADMIN_SCHEDULE/ |
| S3-07 jadwal (PDF) | S3-07_Admin_Schedule_Management_Report.pdf | S3-07_ADMIN_SCHEDULE/ |
| S3-07 jadwal (Excel) | S3-07_Admin_Schedule_Test.xlsx | S3-07_ADMIN_SCHEDULE/ |

DOCX adalah sumber yang dapat diedit; PDF adalah dokumen final untuk pengumpulan.

## 2. Screenshot yang tersedia

- `S3-06_BASIC_SECURITY/screenshots/` — 01 validasi input booking,
  02 escaping XSS data customer, 03 pencarian SQL injection, 04 penolakan CSRF,
  05 penolakan akses 403.
- `S3-07_ADMIN_SCHEDULE/screenshots/` — 01 daftar jadwal, 02 form edit,
  03 perubahan kapasitas, 04 penolakan slot di bawah booking, 05 perubahan jam,
  06 modal konfirmasi hapus, 07 notifikasi hapus berhasil, 08 penolakan hapus
  karena booking, dan 10 tampilan mobile 390x844.
- `S3-07_PERFORMANCE/screenshots/` — 01 ketersediaan jadwal, 02 dashboard admin,
  03 riwayat servis, 04 breadcrumb jadwal, 05 mobile ketersediaan jadwal.

Semua screenshot berasal dari aplikasi yang benar-benar dijalankan pada server
evidence dengan database demo SQLite. Tidak ada data pelanggan asli.

## 3. Pengujian yang sudah PASS

- Test keamanan: **9 PASS** dari 9 test (0 FAIL).
- Test pengelolaan jadwal: **14 PASS** dari 14 test (0 FAIL).
- Seluruh suite aplikasi: **53 PASS** dari 53 test (0 FAIL).
- Pemeriksaan HTTP: guest dialihkan 302, owner mendapat 403 pada halaman jadwal,
  update/hapus tanpa token ditolak 419.

### Ringkasan performa (TTFB rata-rata, target 500 ms)

| Halaman | Rata-rata (ms) | Target (ms) | Status |
|---|---|---|---|
| - | - | - | BELUM DIUJI |

## 4. Pengujian yang FAIL atau belum diuji

- Tidak ada test otomatis yang FAIL pada suite saat dokumen dibuat.
- Indikator loading belum berhasil ditangkap sebagai screenshot karena request
  lokal selesai dalam hitungan milidetik. Item ini tidak diklaim lulus secara
  visual; implementasinya ditunjukkan melalui potongan kode pada laporan S3-07.
- Pengukuran performa dilakukan di lingkungan lokal, bukan produksi, dan tidak
  membandingkan sebelum/sesudah index secara terpisah. Yang tersedia adalah
  EXPLAIN query dan angka respons aktual.

## 5. Implementasi yang sudah selesai

- S3-06: validasi input backend, CSRF pada 17 form mutasi, escaping Blade
  (0 pemakaian `{!! !!}`), validasi MIME unggahan, query terparameter.
- S3-07: index `schedules_overlap_index` dan `bookings_schedule_status_index`,
  konsolidasi query daftar jadwal, breadcrumb, indikator loading, anti
  double-click, dan tampilan responsif.
- CRUD jadwal admin: tambah, edit kapasitas, edit tanggal/jam, hapus dengan
  perlindungan booking, modal konfirmasi, dan pembatasan role ADMIN.

## 6. Pekerjaan yang masih tertunda

- Pengukuran performa sebelum vs sesudah index pada dataset besar belum
  dilakukan.
- Uji beban dan pemindaian dependensi belum termasuk lingkup Sprint 3.
- Verifikasi visual pada perangkat fisik (tablet/mobile nyata) belum dilakukan;
  tangkapan responsif diambil melalui emulasi viewport Chromium.

## 7. Cara memverifikasi ulang

```bash
php artisan test
php artisan test --filter=SecurityVerificationTest
php artisan test --filter=AdminScheduleManagementTest
bash scripts/sprint3/measure_performance.sh
```

Berkas mentah bukti berada di `evidence/` (JUnit XML, CSV performa,
pemeriksaan HTTP, dan fakta database).
