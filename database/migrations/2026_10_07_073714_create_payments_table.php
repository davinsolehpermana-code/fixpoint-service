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

            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('service_record_id');
            $table->unsignedBigInteger('processed_by');

            $table->decimal('amount', 12, 2);

            $table->enum('payment_method', [
                'QRIS',
                'CASH'
            ]);

            $table->enum('payment_status', [
                'UNPAID',
                'PAID'
            ])->default('UNPAID');

            $table->dateTime('payment_date')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->foreign('customer_id')
                  ->references('customer_id')
                  ->on('customers')
                  ->cascadeOnDelete();

            $table->foreign('service_record_id')
                  ->references('service_record_id')
                  ->on('service_records')
                  ->cascadeOnDelete();

            $table->foreign('processed_by')
                  ->references('user_id')
                  ->on('users')
                  ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};