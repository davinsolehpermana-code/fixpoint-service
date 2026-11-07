<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_records', function (Blueprint $table) {
            $table->id('service_record_id');

            $table->unsignedBigInteger('booking_id');
            $table->unsignedBigInteger('handled_by');

            $table->text('diagnosis')->nullable();
            $table->decimal('estimated_cost', 12, 2)->nullable();
            $table->decimal('final_cost', 12, 2)->nullable();

            $table->enum('service_status', [
                'PENDING',
                'DIAGNOSIS',
                'PROCESS',
                'COMPLETED'
            ])->default('PENDING');

            $table->text('service_notes')->nullable();

            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();

            $table->timestamps();

            $table->foreign('booking_id')
                  ->references('booking_id')
                  ->on('bookings')
                  ->cascadeOnDelete();

            $table->foreign('handled_by')
                  ->references('user_id')
                  ->on('users')
                  ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_records');
    }
};