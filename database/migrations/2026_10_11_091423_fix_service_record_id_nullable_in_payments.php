<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Hapus kolom lama yang tidak nullable
            $table->dropForeign(['service_record_id']);
            $table->dropColumn('service_record_id');
            
            // Buat ulang kolom yang sama, tapi sekarang BOLEH null (nullable)
            $table->unsignedBigInteger('service_record_id')->nullable()->after('customer_id');

            $table->foreign('service_record_id')
                  ->references('service_record_id')
                  ->on('service_records')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('service_record_id');
            $table->unsignedBigInteger('service_record_id')->after('customer_id');
        });
    }
};