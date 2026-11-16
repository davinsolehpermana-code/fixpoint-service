<?php

/**
 * Mengumpulkan fakta database nyata (indeks, jumlah baris, EXPLAIN) untuk
 * laporan S3-07. Jalankan: php scripts/sprint3/collect_db_facts.php
 */

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require __DIR__ . '/../../vendor/autoload.php';

$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$tables = ['users', 'customers', 'devices', 'services', 'schedules', 'bookings', 'service_records', 'payments'];
$indexes = [];

foreach ($tables as $table) {
    if (!Schema::hasTable($table)) {
        continue;
    }

    $indexes[$table] = collect(Schema::getIndexes($table))
        ->map(fn ($index) => [
            'name' => $index['name'],
            'columns' => array_values($index['columns']),
            'unique' => (bool) ($index['unique'] ?? false),
            'primary' => (bool) ($index['primary'] ?? false),
        ])
        ->values()
        ->all();
}

$counts = [];
foreach ($tables as $table) {
    if (Schema::hasTable($table)) {
        $counts[$table] = DB::table($table)->count();
    }
}

$explain = [];
$queries = [
    'filter_jadwal_tersedia' => "SELECT schedule_id FROM schedules WHERE schedule_date >= CURDATE() AND status = 'AVAILABLE' ORDER BY schedule_date, start_time",
    'hitung_booking_aktif_jadwal' => "SELECT COUNT(*) FROM bookings WHERE schedule_id = 1 AND status IN ('PENDING','WAITING_VERIFICATION','CONFIRMED','COMPLETED')",
    'laporan_pemasukan' => "SELECT SUM(amount) FROM payments WHERE payment_status = 'PAID' AND payment_date BETWEEN '2026-11-01 00:00:00' AND '2026-11-30 23:59:59'",
    'riwayat_servis' => "SELECT booking_id FROM service_records WHERE service_status = 'COMPLETED' AND completed_at >= '2026-11-01'",
    'pencarian_customer' => "SELECT customer_id FROM customers WHERE phone_number = '081200000001'",
];

foreach ($queries as $key => $sql) {
    $explain[$key] = [
        'sql' => $sql,
        'plan' => array_map(fn ($row) => (array) $row, DB::select('EXPLAIN ' . $sql)),
    ];
}

$facts = [
    'collected_at' => now()->toIso8601String(),
    'connection' => DB::connection()->getDriverName(),
    'database' => DB::connection()->getDatabaseName(),
    'server_version' => DB::selectOne('SELECT VERSION() AS version')->version ?? null,
    'tables' => $counts,
    'indexes' => $indexes,
    'explain' => $explain,
];

$out = __DIR__ . '/../../docs/sprint-3/pengumpulan/evidence/database_facts.json';
file_put_contents($out, json_encode($facts, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

echo 'Database: ' . $facts['database'] . ' (' . $facts['server_version'] . ')' . PHP_EOL;
echo 'Ditulis: ' . $out . PHP_EOL;
