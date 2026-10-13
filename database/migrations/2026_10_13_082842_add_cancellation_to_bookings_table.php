<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Ubah kolom status enum agar mendukung 'CANCELLED'
        DB::statement("ALTER TABLE bookings MODIFY COLUMN status ENUM('PENDING', 'WAITING_VERIFICATION', 'CONFIRMED', 'REJECTED', 'COMPLETED', 'CANCELLED') NOT NULL DEFAULT 'PENDING'");

        // 2. Tambahkan kolom alasan pembatalan
        Schema::table('bookings', function (Blueprint $table) {
            $table->text('cancellation_reason')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('cancellation_reason');
        });

        DB::statement("ALTER TABLE bookings MODIFY COLUMN status ENUM('PENDING', 'WAITING_VERIFICATION', 'CONFIRMED', 'REJECTED', 'COMPLETED') NOT NULL DEFAULT 'PENDING'");
    }
};