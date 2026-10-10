<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id('device_id');

            $table->unsignedBigInteger('customer_id');

            $table->string('device_type');
            $table->string('brand');
            $table->string('model');
            $table->text('complaint');

            $table->timestamps();

            $table->foreign('customer_id')
                  ->references('customer_id')
                  ->on('customers')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};