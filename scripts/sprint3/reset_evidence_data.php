<?php

/**
 * Membersihkan data demo pada database bukti SQLite sebelum pengambilan
 * screenshot diulang. Script ini menolak berjalan bila koneksi bukan SQLite
 * atau nama database bukan evidence.sqlite, sehingga database MySQL proyek
 * tidak mungkin tersentuh.
 */

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../../vendor/autoload.php';

$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$driver = DB::connection()->getDriverName();
$database = (string) DB::connection()->getDatabaseName();

if ($driver !== 'sqlite' || !str_ends_with($database, 'evidence.sqlite')) {
    fwrite(STDERR, "Ditolak: script hanya untuk database bukti SQLite (driver={$driver}, database={$database}).\n");
    exit(1);
}

DB::transaction(function () {
    foreach (['payments', 'service_records', 'bookings', 'schedules', 'devices', 'customers'] as $table) {
        DB::table($table)->delete();
    }

    DB::table('customers')->insert([
        'name' => '<script>alert("xss")</script>',
        'phone_number' => '081200007777',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
});

echo "Database bukti dibersihkan: {$database}\n";
echo 'schedules=' . DB::table('schedules')->count()
    . ' bookings=' . DB::table('bookings')->count()
    . ' customers=' . DB::table('customers')->count()
    . ' users=' . DB::table('users')->count() . PHP_EOL;
