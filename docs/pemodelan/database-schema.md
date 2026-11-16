# Database Schema — FixPoint Service

Dokumentasi struktur database **aktual** aplikasi FixPoint Service. Sumber: seluruh berkas `database/migrations/` dan inspeksi langsung `Schema::getColumns()`, `Schema::getIndexes()`, `Schema::getForeignKeys()` pada koneksi MySQL/MariaDB project.

- DBMS: MySQL 8 / MariaDB 10.4 (project berjalan pada MariaDB 10.4.32)
- Charset: `utf8mb4` / `utf8mb4_unicode_ci`, engine InnoDB
- Tabel domain: **8**; tabel framework: `migrations`, `personal_access_tokens`

## 1. Daftar Tabel

| No | Tabel | Fungsi | Dibuat oleh |
|----|-------|--------|-------------|
| 1 | `users` | Akun internal Admin dan Pemilik Usaha | `UserSeeder` |
| 2 | `customers` | Data pelanggan, dibuat otomatis saat booking | `BookingController@store` |
| 3 | `services` | Katalog layanan, estimasi harga, status aktif | Admin (Data Service) |
| 4 | `schedules` | Sesi jadwal, kapasitas slot, status ketersediaan | Admin (Jadwal Servis) |
| 5 | `devices` | Perangkat pelanggan dan keluhan awal | `BookingController@store` |
| 6 | `bookings` | Transaksi booking dan statusnya | `BookingController@store` |
| 7 | `service_records` | Diagnosis, estimasi, biaya akhir, status servis | `BookingController@verifyBooking` |
| 8 | `payments` | Pembayaran CASH/QRIS, bukti, verifikasi | `BookingController@store` |

## 2. Detail Tabel

### 2.1 `users`

| Kolom | Tipe | Null | Default | Keterangan |
|-------|------|------|---------|------------|
| `user_id` | BIGINT UNSIGNED | Tidak | AUTO_INCREMENT | PK |
| `email` | VARCHAR(255) | Tidak | — | UNIQUE (`users_email_unique`) |
| `password` | VARCHAR(255) | Tidak | — | Hash bcrypt |
| `role` | ENUM('ADMIN','PEMILIK_USAHA') | Tidak | — | Hak akses |
| `created_at` | TIMESTAMP | Ya | NULL | — |
| `updated_at` | TIMESTAMP | Ya | NULL | — |

Tidak ada `email_verified_at`, `remember_token`, maupun role Teknisi.

### 2.2 `customers`

| Kolom | Tipe | Null | Default | Keterangan |
|-------|------|------|---------|------------|
| `customer_id` | BIGINT UNSIGNED | Tidak | AUTO_INCREMENT | PK |
| `name` | VARCHAR(255) | Tidak | — | Nama pelanggan |
| `phone_number` | VARCHAR(255) | Tidak | — | Kunci pencocokan saat booking; tidak unik |
| `email` | VARCHAR(255) | Ya | NULL | Belum diisi aplikasi |
| `address` | TEXT | Ya | NULL | Belum diisi aplikasi |
| `created_at` | TIMESTAMP | Ya | NULL | — |
| `updated_at` | TIMESTAMP | Ya | NULL | — |

Index: `customers_phone_index` (`phone_number`).

### 2.3 `services`

| Kolom | Tipe | Null | Default | Keterangan |
|-------|------|------|---------|------------|
| `service_id` | BIGINT UNSIGNED | Tidak | AUTO_INCREMENT | PK |
| `name` | VARCHAR(255) | Tidak | — | Contoh "Servis Laptop" |
| `description` | TEXT | Ya | NULL | Deskripsi |
| `estimated_price` | DECIMAL(12,2) | Ya | NULL | Estimasi harga |
| `is_active` | TINYINT(1) | Tidak | 1 | Hanya layanan aktif yang dapat dipilih |
| `created_at` | TIMESTAMP | Ya | NULL | — |
| `updated_at` | TIMESTAMP | Ya | NULL | — |

### 2.4 `schedules`

| Kolom | Tipe | Null | Default | Keterangan |
|-------|------|------|---------|------------|
| `schedule_id` | BIGINT UNSIGNED | Tidak | AUTO_INCREMENT | PK |
| `schedule_date` | DATE | Tidak | — | Tanggal sesi |
| `start_time` | TIME | Tidak | — | Jam mulai |
| `end_time` | TIME | Tidak | — | Jam selesai, harus > `start_time` |
| `max_booking` | INT | Tidak | 1 | Kapasitas slot |
| `current_booking` | INT | Tidak | 0 | Counter slot terpakai |
| `status` | ENUM('AVAILABLE','FULL','CLOSED') | Tidak | 'AVAILABLE' | FULL otomatis saat kuota habis |
| `created_at` | TIMESTAMP | Ya | NULL | — |
| `updated_at` | TIMESTAMP | Ya | NULL | — |

Index: `schedules_date_status_index`, `schedules_overlap_index` (`schedule_date`,`start_time`,`end_time`). Bentrok jadwal divalidasi aplikasi, bukan constraint DB.

### 2.5 `devices`

| Kolom | Tipe | Null | Default | Keterangan |
|-------|------|------|---------|------------|
| `device_id` | BIGINT UNSIGNED | Tidak | AUTO_INCREMENT | PK |
| `customer_id` | BIGINT UNSIGNED | Tidak | — | FK → `customers` (CASCADE) |
| `device_type` | VARCHAR(255) | Tidak | — | `Laptop` / `PC Desktop` |
| `brand` | VARCHAR(255) | Tidak | — | `''` untuk PC Desktop |
| `model` | VARCHAR(255) | Tidak | — | `''` untuk PC Desktop |
| `complaint` | TEXT | Tidak | — | Keluhan awal |
| `created_at` | TIMESTAMP | Ya | NULL | — |
| `updated_at` | TIMESTAMP | Ya | NULL | — |

### 2.6 `bookings`

| Kolom | Tipe | Null | Default | Keterangan |
|-------|------|------|---------|------------|
| `booking_id` | BIGINT UNSIGNED | Tidak | AUTO_INCREMENT | PK |
| `service_id_code` | VARCHAR(255) | Tidak | — | UNIQUE, format `FP-YYYYMMDD-XXXXX` |
| `customer_id` | BIGINT UNSIGNED | Tidak | — | FK → `customers` (CASCADE) |
| `device_id` | BIGINT UNSIGNED | Tidak | — | FK → `devices` (CASCADE) |
| `service_id` | BIGINT UNSIGNED | Tidak | — | FK → `services` (RESTRICT) |
| `schedule_id` | BIGINT UNSIGNED | Tidak | — | FK → `schedules` (RESTRICT) |
| `handled_by` | BIGINT UNSIGNED | Ya | NULL | FK → `users.user_id` (SET NULL) |
| `booking_date` | DATETIME | Tidak | — | `now()` saat booking dibuat |
| `status` | ENUM('PENDING','WAITING_VERIFICATION','CONFIRMED','REJECTED','COMPLETED','CANCELLED') | Tidak | 'PENDING' | REJECTED belum pernah ditetapkan kode |
| `notes` | TEXT | Ya | NULL | Catatan tambahan |
| `cancellation_reason` | TEXT | Ya | NULL | Alasan pembatalan |
| `created_at` | TIMESTAMP | Ya | NULL | — |
| `updated_at` | TIMESTAMP | Ya | NULL | — |

Index: `bookings_status_index`, `bookings_date_index`, `bookings_schedule_status_index` + index FK.

### 2.7 `service_records`

| Kolom | Tipe | Null | Default | Keterangan |
|-------|------|------|---------|------------|
| `service_record_id` | BIGINT UNSIGNED | Tidak | AUTO_INCREMENT | PK |
| `booking_id` | BIGINT UNSIGNED | Tidak | — | FK → `bookings` (CASCADE); tidak unik |
| `handled_by` | BIGINT UNSIGNED | Tidak | — | FK → `users.user_id` (RESTRICT) |
| `diagnosis` | TEXT | Ya | NULL | Hasil pemeriksaan |
| `estimated_cost` | DECIMAL(12,2) | Ya | NULL | Estimasi biaya |
| `final_cost` | DECIMAL(12,2) | Ya | NULL | Wajib saat COMPLETED |
| `service_status` | ENUM('PENDING','DIAGNOSIS','PROCESS','COMPLETED') | Tidak | 'PENDING' | Hanya boleh maju |
| `service_notes` | TEXT | Ya | NULL | Estimasi durasi + catatan Admin |
| `started_at` | DATETIME | Ya | NULL | Diisi saat PROCESS |
| `completed_at` | DATETIME | Ya | NULL | Diisi saat COMPLETED |
| `created_at` | TIMESTAMP | Ya | NULL | — |
| `updated_at` | TIMESTAMP | Ya | NULL | — |

Index: `service_records_status_completed_index` (`service_status`,`completed_at`).

### 2.8 `payments`

| Kolom | Tipe | Null | Default | Keterangan |
|-------|------|------|---------|------------|
| `payment_id` | BIGINT UNSIGNED | Tidak | AUTO_INCREMENT | PK |
| `customer_id` | BIGINT UNSIGNED | Tidak | — | FK → `customers` (CASCADE); duplikasi booking |
| `booking_id` | BIGINT UNSIGNED | Ya | NULL | FK → `bookings` (CASCADE), UNIQUE |
| `service_record_id` | BIGINT UNSIGNED | Ya | NULL | FK → `service_records` (CASCADE) |
| `processed_by` | BIGINT UNSIGNED | Ya | NULL | FK → `users.user_id` (RESTRICT) |
| `amount` | DECIMAL(12,2) | Tidak | — | 0 saat booking, `final_cost` saat selesai |
| `cash_received` | DECIMAL(12,2) | Ya | NULL | Wajib untuk CASH |
| `payment_method` | ENUM('QRIS','CASH') | Tidak | — | Dipilih pelanggan |
| `payment_status` | ENUM('UNPAID','WAITING_VERIFICATION','PAID') | Tidak | 'UNPAID' | — |
| `payment_proof` | VARCHAR(255) | Ya | NULL | Path bukti QRIS (disk public) |
| `payment_date` | DATETIME | Ya | NULL | Diisi saat booking, upload, dan verifikasi |
| `notes` | TEXT | Ya | NULL | Catatan penolakan bukti |
| `created_at` | TIMESTAMP | Ya | NULL | — |
| `updated_at` | TIMESTAMP | Ya | NULL | — |

Index: `payments_booking_id_unique`, `payments_status_date_index`, `payments_method_index`.

## 3. Relasi dan Kardinalitas

| Relasi | Kardinalitas | Foreign Key | On Delete |
|--------|--------------|-------------|-----------|
| customers → devices | 1 : N | `devices.customer_id` | CASCADE |
| customers → bookings | 1 : N | `bookings.customer_id` | CASCADE |
| customers → payments | 1 : N | `payments.customer_id` | CASCADE |
| devices → bookings | 1 : N | `bookings.device_id` | CASCADE |
| services → bookings | 1 : N | `bookings.service_id` | RESTRICT |
| schedules → bookings | 1 : N | `bookings.schedule_id` | RESTRICT |
| bookings → service_records | 1 : 1 (aplikasi) | `service_records.booking_id` | CASCADE |
| bookings → payments | 1 : 1 (DB unique) | `payments.booking_id` | CASCADE |
| service_records → payments | 1 : 1 (aplikasi) | `payments.service_record_id` | CASCADE |
| users → bookings | 1 : N | `bookings.handled_by` | SET NULL |
| users → service_records | 1 : N | `service_records.handled_by` | RESTRICT |
| users → payments | 1 : N | `payments.processed_by` | RESTRICT |

## 4. Index Terpasang

| Tabel | Index | Kolom | Tujuan |
|-------|-------|-------|--------|
| customers | `customers_phone_index` | phone_number | Pencarian pelanggan |
| schedules | `schedules_date_status_index` | schedule_date, status | Filter jadwal publik |
| schedules | `schedules_overlap_index` | schedule_date, start_time, end_time | Cek bentrok |
| bookings | `bookings_status_index` | status | Filter status |
| bookings | `bookings_date_index` | booking_date | Filter tanggal |
| bookings | `bookings_schedule_status_index` | schedule_id, status | Hitung booking aktif |
| service_records | `service_records_status_completed_index` | service_status, completed_at | Riwayat & laporan |
| payments | `payments_status_date_index` | payment_status, payment_date | Laporan pemasukan |
| payments | `payments_method_index` | payment_method | Filter CASH/QRIS |

## 5. Catatan dan Keterbatasan

1. `bookings.status` memuat `REJECTED`, tetapi belum ada route/controller yang menetapkannya.
2. `service_records.booking_id` tidak unik, sedangkan aplikasi mengasumsikan satu catatan servis per booking.
3. `payments.customer_id` menduplikasi `bookings.customer_id` dan harus dijaga konsisten oleh aplikasi.
4. `payment_date` diisi sejak booking dibuat (status UNPAID), lalu ditimpa saat upload bukti dan verifikasi.
5. `bookings.booking_date` adalah waktu pembuatan booking, bukan tanggal sesi jadwal.
6. Migration `2026_11_07_000000_add_missing_payment_columns` berstatus untracked di Git meski sudah dijalankan.
7. Database pengembangan memuat tabel di luar domain (`Barang`, `pegawai`, `transaksi`, `pma__*`) yang tidak dipakai aplikasi.
8. Tidak ada tabel audit log, refund, atau riwayat perubahan jadwal.

## 6. Cara Memverifikasi

```bash
php artisan migrate:status
php artisan db:show
php artisan db:table bookings
```

```bash
php artisan tinker --execute="dump(Illuminate\Support\Facades\Schema::getColumns('bookings'));"
```
