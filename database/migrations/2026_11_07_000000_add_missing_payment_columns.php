<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Database yang dibuat sebelum kolom cash_received dan status
 * WAITING_VERIFICATION ditambahkan ke create_payments_table masih memakai
 * skema lama, karena migrasi tersebut sudah tercatat "Ran" sehingga
 * perubahannya tidak pernah dieksekusi ulang. Migrasi ini menyelaraskan
 * skema lama tersebut tanpa mengubah apa pun pada instalasi baru.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('payments')) {
            return;
        }

        if (!Schema::hasColumn('payments', 'cash_received')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->decimal('cash_received', 12, 2)->nullable()->after('amount');
            });
        }

        $this->updatePaymentStatusEnum(['UNPAID', 'WAITING_VERIFICATION', 'PAID']);
    }

    public function down(): void
    {
        if (!Schema::hasTable('payments')) {
            return;
        }

        // Kembalikan ke enum lama; baris dengan status WAITING_VERIFICATION
        // diset ke UNPAID agar ALTER TABLE tidak gagal.
        DB::table('payments')
            ->where('payment_status', 'WAITING_VERIFICATION')
            ->update(['payment_status' => 'UNPAID']);

        $this->updatePaymentStatusEnum(['UNPAID', 'PAID']);

        if (Schema::hasColumn('payments', 'cash_received')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropColumn('cash_received');
            });
        }
    }

    /**
     * @param  array<int, string>  $statuses
     */
    private function updatePaymentStatusEnum(array $statuses): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $current = DB::selectOne(
            "SHOW COLUMNS FROM `payments` WHERE Field = 'payment_status'"
        );

        $definition = "enum('" . implode("','", $statuses) . "')";

        if ($current && str_contains($current->Type, $definition)) {
            return;
        }

        DB::statement(
            "ALTER TABLE `payments` MODIFY `payment_status` {$definition} NOT NULL DEFAULT 'UNPAID'"
        );
    }
};
