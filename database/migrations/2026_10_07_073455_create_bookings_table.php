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

            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('device_id');
            $table->unsignedBigInteger('service_id');
            $table->unsignedBigInteger('schedule_id');
            $table->unsignedBigInteger('handled_by')->nullable();

            $table->dateTime('booking_date');

            $table->enum('status', [
                'PENDING',
                'CONFIRMED',
                'REJECTED',
                'COMPLETED'
            ])->default('PENDING');

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->foreign('customer_id')
                  ->references('customer_id')
                  ->on('customers')
                  ->cascadeOnDelete();

            $table->foreign('device_id')
                  ->references('device_id')
                  ->on('devices')
                  ->cascadeOnDelete();

            $table->foreign('service_id')
                  ->references('service_id')
                  ->on('services')
                  ->restrictOnDelete();

            $table->foreign('schedule_id')
                  ->references('schedule_id')
                  ->on('schedules')
                  ->restrictOnDelete();

            $table->foreign('handled_by')
                  ->references('user_id')
                  ->on('users')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};