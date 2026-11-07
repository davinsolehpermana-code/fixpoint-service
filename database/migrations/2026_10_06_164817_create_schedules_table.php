<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id('schedule_id');
            $table->date('schedule_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('max_booking')->default(1);
            $table->integer('current_booking')->default(0);
            $table->enum('status', ['AVAILABLE', 'FULL', 'CLOSED'])
                  ->default('AVAILABLE');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};