<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('booking_id')
                ->nullable()
                ->after('customer_id')
                ->constrained('bookings', 'booking_id')
                ->cascadeOnDelete();
            $table->unique('booking_id');
            $table->unsignedBigInteger('processed_by')->nullable()->change();
        });

        $legacyPayments = DB::table('payments')
            ->whereNull('booking_id')
            ->orderByDesc('payment_id')
            ->get();
        $assignedCustomers = [];

        foreach ($legacyPayments as $payment) {
            if (isset($assignedCustomers[$payment->customer_id])) {
                continue;
            }

            $booking = DB::table('bookings')
                ->where('customer_id', $payment->customer_id)
                ->orderByDesc('booking_id')
                ->first();

            if ($booking) {
                DB::table('payments')
                    ->where('payment_id', $payment->payment_id)
                    ->update(['booking_id' => $booking->booking_id]);
                $assignedCustomers[$payment->customer_id] = true;
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique(['booking_id']);
            $table->dropForeign(['booking_id']);
            $table->dropColumn('booking_id');
        });
    }
};
