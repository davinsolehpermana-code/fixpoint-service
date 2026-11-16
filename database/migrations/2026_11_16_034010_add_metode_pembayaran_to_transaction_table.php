<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan metode pembayaran (QRIS, Tunai/CASH, Transfer) pada tabel payments
 * yang menyimpan transaksi servis. Kolom payment_method sudah ada di
 * create_payments_table untuk instalasi baru, sehingga migrasi ini juga
 * memperluas enum lama (QRIS, CASH) agar menerima TRANSFER.
 */
return new class extends Migration
{
    /** @var array<int, string> */
    private array $methods = ['QRIS', 'CASH', 'TRANSFER'];

    /** @var array<int, string> */
    private array $previousMethods = ['QRIS', 'CASH'];

    public function up(): void
    {
        if (!Schema::hasTable('payments')) {
            return;
        }

        // Instalasi lama yang belum punya kolom metode pembayaran.
        if (!Schema::hasColumn('payments', 'payment_method')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->enum('payment_method', $this->methods)
                    ->default('CASH')
                    ->after('amount');
            });

            return;
        }

        $this->updatePaymentMethodDefinition($this->methods);
    }

    public function down(): void
    {
        if (!Schema::hasTable('payments') || !Schema::hasColumn('payments', 'payment_method')) {
            return;
        }

        // Baris TRANSFER diubah ke CASH agar enum lama tetap valid saat dikembalikan.
        DB::table('payments')
            ->where('payment_method', 'TRANSFER')
            ->update(['payment_method' => 'CASH']);

        $this->updatePaymentMethodDefinition($this->previousMethods);
    }

    /**
     * @param  array<int, string>  $methods
     */
    private function updatePaymentMethodDefinition(array $methods): void
    {
        $definition = "ENUM('" . implode("','", $methods) . "')";

        if (DB::getDriverName() === 'mysql') {
            $current = DB::selectOne(
                "SHOW COLUMNS FROM `payments` WHERE Field = 'payment_method'"
            );

            if ($current && str_contains($current->Type, "'" . end($methods) . "'")) {
                return;
            }

            DB::statement(
                "ALTER TABLE `payments` MODIFY `payment_method` {$definition} NOT NULL DEFAULT 'CASH'"
            );

            return;
        }

        // SQLite (dipakai saat pengujian) menyimpan enum sebagai CHECK constraint,
        // jadi definisinya diubah lewat change() agar tidak menolak nilai baru.
        Schema::table('payments', function (Blueprint $table) use ($methods) {
            $table->enum('payment_method', $methods)->default('CASH')->change();
        });
    }
};
