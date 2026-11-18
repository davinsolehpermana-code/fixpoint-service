-- =====================================================================
-- FixPoint Service - Database Index untuk Sprint 3 (S3-07)
-- Dibuat: 9 November 2026
-- Migration sumber: database/migrations/2026_11_09_000000_add_schedule_management_indexes.php
-- Sifat: aditif. Tidak menghapus tabel, kolom, atau data.
-- =====================================================================

CREATE INDEX schedules_overlap_index ON schedules (tanggal, jam_mulai, jam_selesai);
CREATE INDEX bookings_schedule_status_index ON bookings (schedule_id, status);

-- Rollback (manual, jalankan hanya bila index akan dicabut):

-- Catatan: pada MySQL/MariaDB, index di atas dibuat oleh migration Laravel.
-- Perintah verifikasi: SHOW INDEX FROM schedules; SHOW INDEX FROM bookings;
