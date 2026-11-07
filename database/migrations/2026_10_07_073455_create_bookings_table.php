<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id('booking_id');

            $table->string('service_id_code')->unique();

            $table->foreignId('customer_id')
                  ->constrained('customers', 'customer_id')
                  ->cascadeOnDelete();

            $table->foreignId('device_id')
                  ->constrained('devices', 'device_id')
                  ->cascadeOnDelete();

            $table->foreignId('service_id')
                  ->constrained('services', 'service_id')
                  ->restrictOnDelete();

            $table->foreignId('schedule_id')
                  ->constrained('schedules', 'schedule_id')
                  ->restrictOnDelete();

            $table->foreignId('handled_by')
                  ->nullable()
                  ->constrained('users', 'user_id')
                  ->nullOnDelete();

            $table->dateTime('booking_date');

            $table->enum('status', [
                'PENDING',
                'WAITING_VERIFICATION',
                'CONFIRMED',
                'REJECTED',
                'COMPLETED',
                'CANCELLED'
            ])->default('PENDING');

            $table->text('notes')->nullable();
            $table->text('cancellation_reason')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};