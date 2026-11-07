<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id('payment_id');

            $table->foreignId('customer_id')
                  ->constrained('customers', 'customer_id')
                  ->cascadeOnDelete();

            // Relasi utama: satu booking hanya punya satu pembayaran.
            $table->foreignId('booking_id')
                  ->nullable()
                  ->unique()
                  ->constrained('bookings', 'booking_id')
                  ->cascadeOnDelete();

            $table->foreignId('service_record_id')
                  ->nullable()
                  ->constrained('service_records', 'service_record_id')
                  ->cascadeOnDelete();

            $table->foreignId('processed_by')
                  ->nullable()
                  ->constrained('users', 'user_id')
                  ->restrictOnDelete();

            $table->decimal('amount', 12, 2);
            $table->decimal('cash_received', 12, 2)->nullable();

            $table->enum('payment_method', [
                'QRIS',
                'CASH'
            ]);

            $table->enum('payment_status', [
                'UNPAID',
                'WAITING_VERIFICATION',
                'PAID'
            ])->default('UNPAID');

            $table->string('payment_proof')->nullable();
            $table->dateTime('payment_date')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
