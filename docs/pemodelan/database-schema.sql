-- =====================================================================
-- FixPoint Service — Database Schema
-- Sistem Informasi Booking & Manajemen Jasa Servis Laptop dan PC
-- ---------------------------------------------------------------------
-- Berkas ini disusun dari migration aktual project:
--   0001_01_01_000000_create_users_table
--   2026_10_06_163113_create_customers_table
--   2026_10_06_164715_create_services_table
--   2026_10_06_164817_create_schedules_table
--   2026_10_06_165000_create_devices_table
--   2026_10_07_073455_create_bookings_table
--   2026_10_07_073616_create_service_records_table
--   2026_10_07_073714_create_payments_table
--   2026_11_06_000000_add_reporting_indexes
--   2026_11_07_000000_add_missing_payment_columns
--   2026_11_09_000000_add_schedule_management_indexes
--
-- Catatan: berkas ini adalah dokumentasi struktur, BUKAN pengganti
-- `php artisan migrate`. Jangan menjalankan skrip ini pada database
-- produksi yang sudah berisi data.
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- 1. users — akun internal (Admin & Pemilik Usaha)
-- ---------------------------------------------------------------------
CREATE TABLE `users` (
  `user_id`    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email`      VARCHAR(255)    NOT NULL,
  `password`   VARCHAR(255)    NOT NULL,
  `role`       ENUM('ADMIN','PEMILIK_USAHA') NOT NULL,
  `created_at` TIMESTAMP       NULL DEFAULT NULL,
  `updated_at` TIMESTAMP       NULL DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 2. customers — data pelanggan (dibuat otomatis saat booking)
-- ---------------------------------------------------------------------
CREATE TABLE `customers` (
  `customer_id`  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`         VARCHAR(255)    NOT NULL,
  `phone_number` VARCHAR(255)    NOT NULL,
  `email`        VARCHAR(255)    NULL DEFAULT NULL,
  `address`      TEXT            NULL DEFAULT NULL,
  `created_at`   TIMESTAMP       NULL DEFAULT NULL,
  `updated_at`   TIMESTAMP       NULL DEFAULT NULL,
  PRIMARY KEY (`customer_id`),
  KEY `customers_phone_index` (`phone_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 3. services — katalog layanan
-- ---------------------------------------------------------------------
CREATE TABLE `services` (
  `service_id`      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`            VARCHAR(255)    NOT NULL,
  `description`     TEXT            NULL DEFAULT NULL,
  `estimated_price` DECIMAL(12,2)   NULL DEFAULT NULL,
  `is_active`       TINYINT(1)      NOT NULL DEFAULT 1,
  `created_at`      TIMESTAMP       NULL DEFAULT NULL,
  `updated_at`      TIMESTAMP       NULL DEFAULT NULL,
  PRIMARY KEY (`service_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 4. schedules — sesi jadwal servis beserta kapasitas slot
-- ---------------------------------------------------------------------
CREATE TABLE `schedules` (
  `schedule_id`     BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `schedule_date`   DATE            NOT NULL,
  `start_time`      TIME            NOT NULL,
  `end_time`        TIME            NOT NULL,
  `max_booking`     INT             NOT NULL DEFAULT 1,
  `current_booking` INT             NOT NULL DEFAULT 0,
  `status`          ENUM('AVAILABLE','FULL','CLOSED') NOT NULL DEFAULT 'AVAILABLE',
  `created_at`      TIMESTAMP       NULL DEFAULT NULL,
  `updated_at`      TIMESTAMP       NULL DEFAULT NULL,
  PRIMARY KEY (`schedule_id`),
  KEY `schedules_date_status_index` (`schedule_date`,`status`),
  KEY `schedules_overlap_index` (`schedule_date`,`start_time`,`end_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 5. devices — perangkat milik pelanggan
-- ---------------------------------------------------------------------
CREATE TABLE `devices` (
  `device_id`   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `customer_id` BIGINT UNSIGNED NOT NULL,
  `device_type` VARCHAR(255)    NOT NULL,
  `brand`       VARCHAR(255)    NOT NULL,
  `model`       VARCHAR(255)    NOT NULL,
  `complaint`   TEXT            NOT NULL,
  `created_at`  TIMESTAMP       NULL DEFAULT NULL,
  `updated_at`  TIMESTAMP       NULL DEFAULT NULL,
  PRIMARY KEY (`device_id`),
  KEY `devices_customer_id_foreign` (`customer_id`),
  CONSTRAINT `devices_customer_id_foreign`
    FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 6. bookings — transaksi booking servis
-- ---------------------------------------------------------------------
CREATE TABLE `bookings` (
  `booking_id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `service_id_code`     VARCHAR(255)    NOT NULL,
  `customer_id`         BIGINT UNSIGNED NOT NULL,
  `device_id`           BIGINT UNSIGNED NOT NULL,
  `service_id`          BIGINT UNSIGNED NOT NULL,
  `schedule_id`         BIGINT UNSIGNED NOT NULL,
  `handled_by`          BIGINT UNSIGNED NULL DEFAULT NULL,
  `booking_date`        DATETIME        NOT NULL,
  `status`              ENUM('PENDING','WAITING_VERIFICATION','CONFIRMED','REJECTED','COMPLETED','CANCELLED')
                        NOT NULL DEFAULT 'PENDING',
  `notes`               TEXT            NULL DEFAULT NULL,
  `cancellation_reason` TEXT            NULL DEFAULT NULL,
  `created_at`          TIMESTAMP       NULL DEFAULT NULL,
  `updated_at`          TIMESTAMP       NULL DEFAULT NULL,
  PRIMARY KEY (`booking_id`),
  UNIQUE KEY `bookings_service_id_code_unique` (`service_id_code`),
  KEY `bookings_customer_id_foreign` (`customer_id`),
  KEY `bookings_device_id_foreign` (`device_id`),
  KEY `bookings_service_id_foreign` (`service_id`),
  KEY `bookings_schedule_id_foreign` (`schedule_id`),
  KEY `bookings_handled_by_foreign` (`handled_by`),
  KEY `bookings_status_index` (`status`),
  KEY `bookings_date_index` (`booking_date`),
  KEY `bookings_schedule_status_index` (`schedule_id`,`status`),
  CONSTRAINT `bookings_customer_id_foreign`
    FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE,
  CONSTRAINT `bookings_device_id_foreign`
    FOREIGN KEY (`device_id`) REFERENCES `devices` (`device_id`) ON DELETE CASCADE,
  CONSTRAINT `bookings_service_id_foreign`
    FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`) ON DELETE RESTRICT,
  CONSTRAINT `bookings_schedule_id_foreign`
    FOREIGN KEY (`schedule_id`) REFERENCES `schedules` (`schedule_id`) ON DELETE RESTRICT,
  CONSTRAINT `bookings_handled_by_foreign`
    FOREIGN KEY (`handled_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 7. service_records — catatan pemeriksaan, diagnosis, dan biaya
-- ---------------------------------------------------------------------
CREATE TABLE `service_records` (
  `service_record_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `booking_id`        BIGINT UNSIGNED NOT NULL,
  `handled_by`        BIGINT UNSIGNED NOT NULL,
  `diagnosis`         TEXT            NULL DEFAULT NULL,
  `estimated_cost`    DECIMAL(12,2)   NULL DEFAULT NULL,
  `final_cost`        DECIMAL(12,2)   NULL DEFAULT NULL,
  `service_status`    ENUM('PENDING','DIAGNOSIS','PROCESS','COMPLETED') NOT NULL DEFAULT 'PENDING',
  `service_notes`     TEXT            NULL DEFAULT NULL,
  `started_at`        DATETIME        NULL DEFAULT NULL,
  `completed_at`      DATETIME        NULL DEFAULT NULL,
  `created_at`        TIMESTAMP       NULL DEFAULT NULL,
  `updated_at`        TIMESTAMP       NULL DEFAULT NULL,
  PRIMARY KEY (`service_record_id`),
  KEY `service_records_booking_id_foreign` (`booking_id`),
  KEY `service_records_handled_by_foreign` (`handled_by`),
  KEY `service_records_status_completed_index` (`service_status`,`completed_at`),
  CONSTRAINT `service_records_booking_id_foreign`
    FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE,
  CONSTRAINT `service_records_handled_by_foreign`
    FOREIGN KEY (`handled_by`) REFERENCES `users` (`user_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 8. payments — pembayaran CASH / QRIS manual (tanpa payment gateway)
-- ---------------------------------------------------------------------
CREATE TABLE `payments` (
  `payment_id`        BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `customer_id`       BIGINT UNSIGNED NOT NULL,
  `booking_id`        BIGINT UNSIGNED NULL DEFAULT NULL,
  `service_record_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `processed_by`      BIGINT UNSIGNED NULL DEFAULT NULL,
  `amount`            DECIMAL(12,2)   NOT NULL,
  `cash_received`     DECIMAL(12,2)   NULL DEFAULT NULL,
  `payment_method`    ENUM('QRIS','CASH') NOT NULL,
  `payment_status`    ENUM('UNPAID','WAITING_VERIFICATION','PAID') NOT NULL DEFAULT 'UNPAID',
  `payment_proof`     VARCHAR(255)    NULL DEFAULT NULL,
  `payment_date`      DATETIME        NULL DEFAULT NULL,
  `notes`             TEXT            NULL DEFAULT NULL,
  `created_at`        TIMESTAMP       NULL DEFAULT NULL,
  `updated_at`        TIMESTAMP       NULL DEFAULT NULL,
  PRIMARY KEY (`payment_id`),
  UNIQUE KEY `payments_booking_id_unique` (`booking_id`),
  KEY `payments_customer_id_foreign` (`customer_id`),
  KEY `payments_service_record_id_foreign` (`service_record_id`),
  KEY `payments_processed_by_foreign` (`processed_by`),
  KEY `payments_status_date_index` (`payment_status`,`payment_date`),
  KEY `payments_method_index` (`payment_method`),
  CONSTRAINT `payments_customer_id_foreign`
    FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE,
  CONSTRAINT `payments_booking_id_foreign`
    FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE,
  CONSTRAINT `payments_service_record_id_foreign`
    FOREIGN KEY (`service_record_id`) REFERENCES `service_records` (`service_record_id`) ON DELETE CASCADE,
  CONSTRAINT `payments_processed_by_foreign`
    FOREIGN KEY (`processed_by`) REFERENCES `users` (`user_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- Catatan implementasi
-- ---------------------------------------------------------------------
-- 1. Tabel personal_access_tokens dibuat oleh Laravel Sanctum, tetapi
--    API pada routes/api.php belum memakai guard token (masih session
--    auth pada grup api yang stateless) — lihat AUDIT-FIXPOINT.
-- 2. Database pengembangan saat ini juga memuat tabel di luar domain
--    aplikasi (Barang, pegawai, transaksi, pma__*) yang tidak dipakai
--    oleh kode FixPoint Service.
-- 3. Enum bookings.status memuat REJECTED, tetapi belum ada route atau
--    controller yang menetapkan status tersebut.
-- =====================================================================
