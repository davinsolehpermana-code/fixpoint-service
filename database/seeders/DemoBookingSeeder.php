<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Device;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\ServiceRecord;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

/**
 * Data demo booking dan pembayaran FixPoint Service.
 *
 * Seeder ini melengkapi data master dari DatabaseSeeder dengan variasi status
 * booking (PENDING, WAITING_VERIFICATION, CONFIRMED, COMPLETED, CANCELLED) dan
 * variasi pembayaran (CASH, QRIS, TRANSFER) beserta status UNPAID,
 * WAITING_VERIFICATION, dan PAID.
 *
 * Service ID demo memakai pola tetap (FP-DEMO-10xx) sehingga seeder boleh
 * dijalankan berulang tanpa membuat booking/pembayaran ganda.
 */
class DemoBookingSeeder extends Seeder
{
    /**
     * Sumber gambar contoh bukti pembayaran (hanya placeholder demo).
     */
    private const DEMO_PROOF_SOURCE = 'seeders/assets/demo-bukti-pembayaran.png';

    /**
     * Lokasi gambar bukti di disk public setelah disalin oleh seeder.
     */
    private const DEMO_PROOF_TARGET = 'payment_proofs/demo-bukti-pembayaran.png';

    public function run(): void
    {
        $admin = User::query()
            ->where('role', 'ADMIN')
            ->orderBy('user_id')
            ->first()
            ?? User::firstOrCreate(
                ['email' => 'admin@fixpoint.test'],
                ['password' => Hash::make('Admin12345'), 'role' => 'ADMIN']
            );

        $proofPath = $this->publishDemoProof();

        DB::transaction(function () use ($admin, $proofPath) {
            $scheduleIds = [];

            foreach ($this->scenarios() as $scenario) {
                $customer = Customer::firstOrCreate(
                    ['phone_number' => $scenario['phone']],
                    [
                        'name' => $scenario['name'],
                        'email' => $scenario['email'],
                        'address' => $scenario['address'],
                    ]
                );

                $device = Device::firstOrCreate(
                    [
                        'customer_id' => $customer->customer_id,
                        'brand' => $scenario['brand'],
                        'model' => $scenario['model'],
                    ],
                    [
                        'device_type' => $scenario['device_type'],
                        'complaint' => $scenario['complaint'],
                    ]
                );

                $service = Service::firstOrCreate(
                    ['name' => $scenario['service']],
                    [
                        'description' => null,
                        'estimated_price' => $scenario['estimated_cost'] ?? null,
                        'is_active' => true,
                    ]
                );

                $date = now()->startOfDay()->addDays($scenario['days']);

                $schedule = Schedule::firstOrCreate(
                    [
                        'schedule_date' => $date->toDateString(),
                        'start_time' => $scenario['start_time'],
                        'end_time' => $scenario['end_time'],
                    ],
                    [
                        'max_booking' => 4,
                        'current_booking' => 0,
                        'status' => 'AVAILABLE',
                    ]
                );

                // Jadwal lama ikut dihitung ulang bila tanggal booking bergeser.
                $previousScheduleId = Booking::query()
                    ->where('service_id_code', $scenario['code'])
                    ->value('schedule_id');

                if ($previousScheduleId) {
                    $scheduleIds[$previousScheduleId] = true;
                }

                $booking = Booking::updateOrCreate(
                    ['service_id_code' => $scenario['code']],
                    [
                        'customer_id' => $customer->customer_id,
                        'device_id' => $device->device_id,
                        'service_id' => $service->service_id,
                        'schedule_id' => $schedule->schedule_id,
                        'handled_by' => $scenario['record'] ? $admin->user_id : null,
                        'booking_date' => $date->copy()->setTime(8, 0),
                        'status' => $scenario['status'],
                        'notes' => $scenario['booking_notes'] ?? null,
                        'cancellation_reason' => $scenario['cancellation_reason'] ?? null,
                    ]
                );

                $scheduleIds[$schedule->schedule_id] = true;

                $serviceRecord = $this->upsertServiceRecord($booking, $scenario, $admin);

                $this->upsertPayment($booking, $customer, $scenario, $serviceRecord, $admin, $proofPath);
            }

            $this->syncScheduleQuota(array_keys($scheduleIds));
        });

        $this->command?->info('Data demo booking dan pembayaran berhasil disiapkan.');
    }

    /**
     * Menyalin gambar contoh bukti pembayaran ke disk public.
     * Mengembalikan null bila berkas sumber tidak tersedia.
     */
    private function publishDemoProof(): ?string
    {
        $source = database_path(self::DEMO_PROOF_SOURCE);

        if (! is_file($source)) {
            return null;
        }

        $contents = file_get_contents($source);

        if ($contents === false) {
            return null;
        }

        Storage::disk('public')->put(self::DEMO_PROOF_TARGET, $contents);

        return self::DEMO_PROOF_TARGET;
    }

    /**
     * Membuat catatan servis untuk booking yang sudah dikerjakan Admin.
     */
    private function upsertServiceRecord(Booking $booking, array $scenario, User $admin): ?ServiceRecord
    {
        $record = $scenario['record'];

        if ($record === null) {
            return ServiceRecord::query()
                ->where('booking_id', $booking->booking_id)
                ->first();
        }

        $completedAt = null;

        if ($record['service_status'] === 'COMPLETED') {
            $completedAt = now()->startOfDay()
                ->addDays($scenario['days'])
                ->setTime(15, 0);

            if ($completedAt->isFuture()) {
                $completedAt = now()->subHours(2);
            }
        }

        $startedAt = $completedAt
            ? $completedAt->copy()->subHours(3)
            : now()->startOfDay()->addDays($scenario['days'])->setTime(9, 0);

        if ($startedAt->isFuture()) {
            $startedAt = now()->subHour();
        }

        return ServiceRecord::updateOrCreate(
            ['booking_id' => $booking->booking_id],
            [
                'handled_by' => $admin->user_id,
                'diagnosis' => $record['diagnosis'],
                'estimated_cost' => $record['estimated_cost'],
                'final_cost' => $record['final_cost'] ?? null,
                'service_status' => $record['service_status'],
                'service_notes' => $record['service_notes'] ?? null,
                'started_at' => $startedAt,
                'completed_at' => $completedAt,
            ]
        );
    }

    /**
     * Membuat atau memperbarui pembayaran milik satu booking.
     */
    private function upsertPayment(
        Booking $booking,
        Customer $customer,
        array $scenario,
        ?ServiceRecord $serviceRecord,
        User $admin,
        ?string $proofPath
    ): void {
        $method = $scenario['payment_method'];
        $status = $scenario['payment_status'];

        $amount = (float) ($serviceRecord?->final_cost ?? 0);
        $completedAt = $serviceRecord?->completed_at;

        $paidAt = null;
        $uploadedAt = null;

        if ($completedAt) {
            if ($status === 'PAID') {
                $paidAt = $completedAt->copy()->addMinutes(45);
            } elseif ($status === 'WAITING_VERIFICATION') {
                $uploadedAt = $completedAt->copy()->addMinutes(20);
            }

            if ($paidAt && $paidAt->isFuture()) {
                $paidAt = now();
            }

            if ($uploadedAt && $uploadedAt->isFuture()) {
                $uploadedAt = now();
            }
        }

        Payment::updateOrCreate(
            ['booking_id' => $booking->booking_id],
            [
                'customer_id' => $customer->customer_id,
                'service_record_id' => $serviceRecord?->service_record_id,
                'processed_by' => $status === 'PAID' ? $admin->user_id : null,
                'amount' => $amount,
                'cash_received' => $method === 'CASH' && $status === 'PAID'
                    ? $scenario['cash_received']
                    : null,
                'payment_method' => $method,
                'payment_status' => $status,
                // Bukti hanya melekat pada pembayaran yang menunggu verifikasi.
                'payment_proof' => $status === 'WAITING_VERIFICATION' ? $proofPath : null,
                'payment_date' => match ($status) {
                    'PAID' => $paidAt,
                    'WAITING_VERIFICATION' => $uploadedAt,
                    default => null,
                },
                'notes' => null,
            ]
        );
    }

    /**
     * Menyelaraskan kuota jadwal dengan jumlah booking yang masih dihitung aktif.
     *
     * @param  array<int, int>  $scheduleIds
     */
    private function syncScheduleQuota(array $scheduleIds): void
    {
        foreach (array_unique($scheduleIds) as $scheduleId) {
            $schedule = Schedule::query()->find($scheduleId);

            if (! $schedule) {
                continue;
            }

            $used = Booking::query()
                ->where('schedule_id', $scheduleId)
                ->whereIn('status', ['PENDING', 'WAITING_VERIFICATION', 'CONFIRMED', 'COMPLETED'])
                ->count();

            $status = $schedule->status;

            if ($status !== 'CLOSED') {
                $status = $used >= (int) $schedule->max_booking ? 'FULL' : 'AVAILABLE';
            }

            $schedule->update([
                'current_booking' => $used,
                'status' => $status,
            ]);
        }
    }

    /**
     * Skenario demo: mencakup semua status booking dan metode pembayaran.
     *
     * @return array<int, array<string, mixed>>
     */
    private function scenarios(): array
    {
        return [
            [
                'code' => 'FP-DEMO-1001',
                'days' => -6,
                'start_time' => '09:00',
                'end_time' => '11:00',
                'status' => 'COMPLETED',
                'name' => 'Andi Pratama',
                'phone' => '081290000001',
                'email' => 'andi.pratama@example.test',
                'address' => 'Jl. Cihampelas No. 45, Bandung',
                'device_type' => 'Laptop',
                'brand' => 'ASUS',
                'model' => 'VivoBook 14 A416MA',
                'complaint' => 'Laptop mati total setelah terkena cairan.',
                'service' => 'Pembersihan Hardware dan Thermal Paste',
                'booking_notes' => 'Pelanggan meminta pengerjaan hari yang sama.',
                'record' => [
                    'service_status' => 'COMPLETED',
                    'diagnosis' => 'Motherboard terkena cairan pada area power rail. Korosi dibersihkan, beberapa kapasitor diganti, dan thermal paste diperbarui.',
                    'estimated_cost' => 150000,
                    'final_cost' => 185000,
                    'service_notes' => 'Sudah diuji nyala 60 menit dan suhu prosesor stabil.',
                ],
                'payment_method' => 'TRANSFER',
                'payment_status' => 'PAID',
            ],
            [
                'code' => 'FP-DEMO-1002',
                'days' => -13,
                'start_time' => '13:00',
                'end_time' => '15:00',
                'status' => 'COMPLETED',
                'name' => 'Budi Santoso',
                'phone' => '081290000002',
                'email' => 'budi.santoso@example.test',
                'address' => 'Jl. Dago Asri No. 12, Bandung',
                'device_type' => 'Laptop',
                'brand' => 'Lenovo',
                'model' => 'IdeaPad 3 14ITL6',
                'complaint' => 'Layar bergaris dan berkedip saat dinyalakan.',
                'service' => 'Penggantian LCD Laptop',
                'booking_notes' => null,
                'record' => [
                    'service_status' => 'COMPLETED',
                    'diagnosis' => 'Panel LCD rusak pada bagian inverter. Panel diganti dengan unit original dan kabel fleksibel dirapikan.',
                    'estimated_cost' => 850000,
                    'final_cost' => 925000,
                    'service_notes' => 'Tampilan sudah normal, tidak ada garis atau dead pixel.',
                ],
                'payment_method' => 'QRIS',
                'payment_status' => 'PAID',
            ],
            [
                'code' => 'FP-DEMO-1003',
                'days' => -4,
                'start_time' => '10:00',
                'end_time' => '12:00',
                'status' => 'COMPLETED',
                'name' => 'Citra Lestari',
                'phone' => '081290000003',
                'email' => 'citra.lestari@example.test',
                'address' => 'Kompleks Setiabudi Blok C-7, Bandung',
                'device_type' => 'PC Desktop',
                'brand' => 'Rakitan',
                'model' => 'Custom',
                'complaint' => 'PC restart sendiri saat menjalankan aplikasi berat.',
                'service' => 'Servis PC Desktop',
                'booking_notes' => 'Perangkat diantar langsung ke toko.',
                'record' => [
                    'service_status' => 'COMPLETED',
                    'diagnosis' => 'Debu menumpuk pada heatsink prosesor dan pasta thermal sudah mengering sehingga suhu cepat naik. Pembersihan menyeluruh dan penggantian pasta dilakukan.',
                    'estimated_cost' => 150000,
                    'final_cost' => 165000,
                    'service_notes' => 'Suhu idle turun dari 68 menjadi 42 derajat Celsius.',
                ],
                'payment_method' => 'CASH',
                'payment_status' => 'PAID',
                'cash_received' => 200000,
            ],
            [
                'code' => 'FP-DEMO-1004',
                'days' => -1,
                'start_time' => '09:00',
                'end_time' => '11:00',
                'status' => 'COMPLETED',
                'name' => 'Dedi Kurniawan',
                'phone' => '081290000004',
                'email' => 'dedi.kurniawan@example.test',
                'address' => 'Jl. Buah Batu No. 88, Bandung',
                'device_type' => 'Laptop',
                'brand' => 'HP',
                'model' => 'Pavilion 14-dv0053TU',
                'complaint' => 'Beberapa tombol keyboard tidak berfungsi.',
                'service' => 'Perbaikan Keyboard Laptop',
                'booking_notes' => 'Pelanggan mengirim bukti transfer melalui halaman cek status.',
                'record' => [
                    'service_status' => 'COMPLETED',
                    'diagnosis' => 'Flexible keyboard longgar akibat sering dibuka. Keyboard diganti dan seluruh tombol diuji satu per satu.',
                    'estimated_cost' => 250000,
                    'final_cost' => 285000,
                    'service_notes' => 'Semua tombol berfungsi normal setelah penggantian.',
                ],
                'payment_method' => 'TRANSFER',
                'payment_status' => 'WAITING_VERIFICATION',
            ],
            [
                'code' => 'FP-DEMO-1005',
                'days' => -2,
                'start_time' => '13:00',
                'end_time' => '15:00',
                'status' => 'COMPLETED',
                'name' => 'Eka Putri',
                'phone' => '081290000005',
                'email' => 'eka.putri@example.test',
                'address' => 'Jl. Sukajadi No. 21, Bandung',
                'device_type' => 'Laptop',
                'brand' => 'Acer',
                'model' => 'Aspire 5 A515-56',
                'complaint' => 'Engsel kiri patah dan casing retak.',
                'service' => 'Perbaikan Engsel dan Casing',
                'booking_notes' => null,
                'record' => [
                    'service_status' => 'COMPLETED',
                    'diagnosis' => 'Engsel kiri patah karena dudukan sekrup pada casing plastik pecah. Dudukan engsel diganti dan casing diperkuat.',
                    'estimated_cost' => 400000,
                    'final_cost' => 450000,
                    'service_notes' => 'Engsel sudah diuji buka-tutup 50 kali tanpa masalah.',
                ],
                'payment_method' => 'QRIS',
                'payment_status' => 'WAITING_VERIFICATION',
            ],
            [
                'code' => 'FP-DEMO-1006',
                'days' => 0,
                'start_time' => '09:00',
                'end_time' => '11:00',
                'status' => 'COMPLETED',
                'name' => 'Fajar Nugroho',
                'phone' => '081290000006',
                'email' => 'fajar.nugroho@example.test',
                'address' => 'Jl. Antapani Lama No. 9, Bandung',
                'device_type' => 'Laptop',
                'brand' => 'Dell',
                'model' => 'Inspiron 15 3501',
                'complaint' => 'Laptop sangat lambat dan sering freeze.',
                'service' => 'Upgrade SSD dan Migrasi Data',
                'booking_notes' => 'Data lama wajib dipertahankan.',
                'record' => [
                    'service_status' => 'COMPLETED',
                    'diagnosis' => 'Hardisk SATA mulai bad sector. Diganti SSD NVMe 512 GB dan seluruh data pelanggan dimigrasikan.',
                    'estimated_cost' => 300000,
                    'final_cost' => 350000,
                    'service_notes' => 'Booting turun dari 90 detik menjadi 14 detik. Pembayaran belum diterima.',
                ],
                'payment_method' => 'TRANSFER',
                'payment_status' => 'UNPAID',
            ],
            [
                'code' => 'FP-DEMO-1007',
                'days' => 0,
                'start_time' => '13:00',
                'end_time' => '15:00',
                'status' => 'CONFIRMED',
                'name' => 'Gita Maharani',
                'phone' => '081290000007',
                'email' => 'gita.maharani@example.test',
                'address' => 'Jl. Pasteur No. 31, Bandung',
                'device_type' => 'Laptop',
                'brand' => 'ASUS',
                'model' => 'ROG Strix G15 G513',
                'complaint' => 'Kapasitas RAM tidak cukup untuk aplikasi desain.',
                'service' => 'Upgrade RAM',
                'booking_notes' => 'Booking sudah diterima Admin dan sedang dikerjakan.',
                'record' => [
                    'service_status' => 'PROCESS',
                    'diagnosis' => 'RAM 8 GB tidak mencukupi. Direncanakan upgrade ke 16 GB dual channel.',
                    'estimated_cost' => 450000,
                    'service_notes' => 'Estimasi pengerjaan: 1-2 jam.',
                ],
                'payment_method' => 'TRANSFER',
                'payment_status' => 'UNPAID',
            ],
            [
                'code' => 'FP-DEMO-1008',
                'days' => 1,
                'start_time' => '09:00',
                'end_time' => '11:00',
                'status' => 'WAITING_VERIFICATION',
                'name' => 'Hadi Saputra',
                'phone' => '081290000008',
                'email' => 'hadi.saputra@example.test',
                'address' => 'Jl. Kiaracondong No. 14, Bandung',
                'device_type' => 'Laptop',
                'brand' => 'Acer',
                'model' => 'Swift 3 SF314-42',
                'complaint' => 'Sistem operasi gagal boot dan driver bermasalah.',
                'service' => 'Instalasi Ulang OS dan Driver',
                'booking_notes' => 'Booking baru masuk dan menunggu verifikasi Admin.',
                'record' => null,
                'payment_method' => 'QRIS',
                'payment_status' => 'UNPAID',
            ],
            [
                'code' => 'FP-DEMO-1009',
                'days' => 2,
                'start_time' => '13:00',
                'end_time' => '15:00',
                'status' => 'PENDING',
                'name' => 'Indah Permata',
                'phone' => '081290000009',
                'email' => 'indah.permata@example.test',
                'address' => 'Jl. Cibiru Raya No. 5, Bandung',
                'device_type' => 'PC Desktop',
                'brand' => 'Rakitan',
                'model' => 'Custom',
                'complaint' => 'Komputer sering blue screen dan antivirus mendeteksi malware.',
                'service' => 'Troubleshooting Software dan Virus',
                'booking_notes' => 'Pelanggan memilih pembayaran tunai di toko.',
                'record' => null,
                'payment_method' => 'CASH',
                'payment_status' => 'UNPAID',
            ],
            [
                'code' => 'FP-DEMO-1010',
                'days' => 3,
                'start_time' => '09:00',
                'end_time' => '11:00',
                'status' => 'PENDING',
                'name' => 'Joko Susilo',
                'phone' => '081290000010',
                'email' => 'joko.susilo@example.test',
                'address' => 'Jl. Margahayu No. 77, Bandung',
                'device_type' => 'Laptop',
                'brand' => 'MSI',
                'model' => 'Modern 14 B10MW',
                'complaint' => 'Laptop tidak menyala, dicurigai kerusakan motherboard.',
                'service' => 'Diagnosa dan Perbaikan Motherboard',
                'booking_notes' => null,
                'record' => null,
                'payment_method' => 'TRANSFER',
                'payment_status' => 'UNPAID',
            ],
            [
                'code' => 'FP-DEMO-1011',
                'days' => 4,
                'start_time' => '13:00',
                'end_time' => '15:00',
                'status' => 'CANCELLED',
                'name' => 'Kartika Sari',
                'phone' => '081290000011',
                'email' => 'kartika.sari@example.test',
                'address' => 'Jl. Ujung Berung No. 3, Bandung',
                'device_type' => 'Laptop',
                'brand' => 'Toshiba',
                'model' => 'Satellite C850',
                'complaint' => 'Port charger longgar dan baterai tidak mengisi.',
                'service' => 'Perbaikan Port Charger / DC Jack',
                'booking_notes' => null,
                'cancellation_reason' => 'Pelanggan membatalkan karena perangkat sudah diperbaiki di tempat lain.',
                'record' => null,
                'payment_method' => 'QRIS',
                'payment_status' => 'UNPAID',
            ],
        ];
    }
}
