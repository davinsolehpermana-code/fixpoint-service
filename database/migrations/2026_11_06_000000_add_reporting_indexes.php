<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indeks untuk kolom yang sering dipakai filter/pencarian pada laporan dan
 * dashboard. Kolom foreign key sudah memiliki indeks otomatis dari constraint,
 * jadi hanya kolom status/tanggal/metode yang ditambahkan di sini.
 */
return new class extends Migration
{
    /**
     * @var array<string, array<string, array<int, string>>>
     */
    private array $indexes = [
        'payments' => [
            'payments_status_date_index' => ['payment_status', 'payment_date'],
            'payments_method_index' => ['payment_method'],
        ],
        'service_records' => [
            'service_records_status_completed_index' => ['service_status', 'completed_at'],
        ],
        'bookings' => [
            'bookings_status_index' => ['status'],
            'bookings_date_index' => ['booking_date'],
        ],
        'schedules' => [
            'schedules_date_status_index' => ['schedule_date', 'status'],
        ],
        'customers' => [
            'customers_phone_index' => ['phone_number'],
        ],
    ];

    public function up(): void
    {
        foreach ($this->indexes as $table => $definitions) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            $existing = collect(Schema::getIndexes($table))
                ->map(fn ($index) => implode(',', data_get($index, 'columns', [])))
                ->all();

            foreach ($definitions as $name => $columns) {
                if (in_array(implode(',', $columns), $existing, true)) {
                    continue;
                }

                Schema::table($table, fn (Blueprint $table) => $table->index($columns, $name));
            }
        }
    }

    public function down(): void
    {
        foreach ($this->indexes as $table => $definitions) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            $existing = collect(Schema::getIndexes($table))
                ->pluck('name')
                ->all();

            foreach (array_keys($definitions) as $name) {
                if (!in_array($name, $existing, true)) {
                    continue;
                }

                Schema::table($table, fn (Blueprint $table) => $table->dropIndex($name));
            }
        }
    }
};
