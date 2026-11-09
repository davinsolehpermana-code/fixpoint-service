<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indeks tambahan untuk pengelolaan jadwal servis:
 * - schedules(schedule_date, start_time, end_time) dipakai saat memeriksa
 *   benturan jam pada tanggal yang sama ketika jadwal dibuat/diubah;
 * - bookings(schedule_id, status) dipakai saat menghitung booking aktif
 *   milik satu jadwal sebelum kuota diubah atau jadwal dihapus.
 *
 * Migration ini hanya menambah indeks (tidak mengubah kolom/data) dan
 * melewati indeks yang sudah ada agar aman dijalankan berulang.
 */
return new class extends Migration
{
    /**
     * @var array<string, array<string, array<int, string>>>
     */
    private array $indexes = [
        'schedules' => [
            'schedules_overlap_index' => ['schedule_date', 'start_time', 'end_time'],
        ],
        'bookings' => [
            'bookings_schedule_status_index' => ['schedule_id', 'status'],
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

                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->index($columns, $name));
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

                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropIndex($name));
            }
        }
    }
};
