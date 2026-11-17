
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('payments')) {
            throw new RuntimeException(
                'Tabel payments tidak ditemukan.'
            );
        }

        if (!Schema::hasColumn('payments', 'payment_method')) {
            throw new RuntimeException(
                'Kolom payment_method tidak ditemukan.'
            );
        }

        // Perubahan ENUM (SHOW COLUMNS + ALTER TABLE) hanya berlaku untuk MySQL.
        // SQLite/PostgreSQL dipakai pada pengujian otomatis, jadi dilewati agar
        // migrate:fresh pada lingkungan tes tidak gagal.
        if (!in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        // Pastikan metode TRANSFER belum ditambahkan.
        $column = DB::selectOne(
            "SHOW COLUMNS FROM `payments` WHERE Field = 'payment_method'"
        );

        $currentType = strtolower($column->Type ?? '');

        if (str_contains($currentType, "'transfer'")) {
            return;
        }

        // Tambahkan TRANSFER tanpa membuat ulang tabel.
        DB::statement("
            ALTER TABLE `payments`
            MODIFY `payment_method`
            ENUM('QRIS', 'CASH', 'TRANSFER')
            NOT NULL
        ");
    }

    public function down(): void
    {
        if (!in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        // Jangan menghapus TRANSFER jika sudah digunakan transaksi.
        $hasTransfer = DB::table('payments')
            ->where('payment_method', 'TRANSFER')
            ->exists();

        if ($hasTransfer) {
            throw new RuntimeException(
                'Rollback dibatalkan karena masih ada pembayaran TRANSFER.'
            );
        }

        DB::statement("
            ALTER TABLE `payments`
            MODIFY `payment_method`
            ENUM('QRIS', 'CASH')
            NOT NULL
        ");
    }
};
