<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Device;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\ServiceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Pengujian fungsional Sprint 3: autentikasi & otorisasi, validasi booking,
 * keamanan form, serta laporan riwayat servis dan pemasukan (filter + ekspor).
 */
class Sprint3AcceptanceTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role, string $email): User
    {
        return User::create([
            'email' => $email,
            'password' => Hash::make('password123'),
            'role' => $role,
        ]);
    }

    /**
     * Membuat satu servis selesai (opsional dengan pembayaran) pada tanggal tertentu.
     */
    private function makeCompletedService(string $completedAt, array $options = []): Booking
    {
        $customer = Customer::create([
            'name' => $options['customer'] ?? 'Pelanggan Uji',
            'phone_number' => $options['phone'] ?? '081200000001',
        ]);

        $device = Device::create([
            'customer_id' => $customer->customer_id,
            'device_type' => 'Laptop',
            'brand' => 'Asus',
            'model' => 'Vivobook',
            'complaint' => 'Laptop tidak menyala',
        ]);

        $service = Service::create([
            'name' => 'Servis Laptop',
            'description' => 'Pemeriksaan laptop',
            'is_active' => true,
        ]);

        $schedule = Schedule::create([
            'schedule_date' => Carbon::parse($completedAt)->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'max_booking' => 2,
            'current_booking' => 1,
            'status' => 'AVAILABLE',
        ]);

        $booking = Booking::create([
            'service_id_code' => $options['code'] ?? ('FP-UJI-' . strtoupper(substr(md5($completedAt . ($options['code'] ?? '')), 0, 6))),
            'customer_id' => $customer->customer_id,
            'device_id' => $device->device_id,
            'service_id' => $service->service_id,
            'schedule_id' => $schedule->schedule_id,
            'booking_date' => Carbon::parse($completedAt)->toDateString(),
            'status' => 'COMPLETED',
        ]);

        $admin = $this->makeUser('ADMIN', 'admin-' . uniqid() . '@fixpoint.test');

        $serviceRecord = ServiceRecord::create([
            'booking_id' => $booking->booking_id,
            'handled_by' => $admin->user_id,
            'diagnosis' => 'Kerusakan adaptor',
            'estimated_cost' => 150000,
            'final_cost' => $options['cost'] ?? 200000,
            'service_status' => 'COMPLETED',
            'started_at' => Carbon::parse($completedAt)->subDay(),
            'completed_at' => Carbon::parse($completedAt),
        ]);

        if (($options['payment'] ?? true) === true) {
            Payment::create([
                'booking_id' => $booking->booking_id,
                'customer_id' => $customer->customer_id,
                'service_record_id' => $serviceRecord->service_record_id,
                'processed_by' => $admin->user_id,
                'amount' => $options['amount'] ?? 200000,
                'cash_received' => $options['cash_received'] ?? null,
                'payment_method' => $options['method'] ?? 'CASH',
                'payment_status' => $options['status'] ?? 'PAID',
                'payment_date' => Carbon::parse($completedAt),
            ]);
        }

        return $booking;
    }

    // ---------------------------------------------------------------------
    // S3-01 Authentication & Authorization
    // ---------------------------------------------------------------------

    public function test_tamu_tidak_bisa_membuka_dashboard_admin(): void
    {
        $this->get('/dashboard-admin')->assertRedirect(route('login'));
    }

    public function test_tamu_tidak_bisa_membuka_laporan_internal(): void
    {
        $this->get('/admin/riwayat-servis')->assertRedirect(route('login'));
        $this->get('/admin/riwayat-servis/pdf')->assertRedirect(route('login'));
        $this->get('/pemilik/laporan-pemasukan')->assertRedirect(route('login'));
        $this->get('/pemilik/laporan-pemasukan/excel')->assertRedirect(route('login'));
    }

    public function test_pemilik_tidak_bisa_membuka_halaman_admin(): void
    {
        $owner = $this->makeUser('PEMILIK_USAHA', 'pemilik@fixpoint.test');

        $this->actingAs($owner)->get('/dashboard-admin')->assertForbidden();
        $this->actingAs($owner)->get('/admin/riwayat-servis')->assertForbidden();
        $this->actingAs($owner)->get('/admin/pembayaran')->assertForbidden();
    }

    public function test_admin_tidak_bisa_membuka_dashboard_pemilik(): void
    {
        $admin = $this->makeUser('ADMIN', 'admin@fixpoint.test');

        $this->actingAs($admin)->get('/dashboard-pemilik')->assertForbidden();
        $this->actingAs($admin)->get('/pemilik/laporan-pemasukan')->assertForbidden();
    }

    public function test_laporan_bisa_dibuka_sesuai_hak_akses(): void
    {
        $admin = $this->makeUser('ADMIN', 'admin2@fixpoint.test');
        $owner = $this->makeUser('PEMILIK_USAHA', 'pemilik2@fixpoint.test');

        // Riwayat servis: admin & pemilik (read-only) boleh akses.
        $this->actingAs($admin)->get('/admin/riwayat-servis')->assertOk();
        $this->actingAs($owner)->get('/pemilik/riwayat-servis')->assertOk();

        // Laporan pemasukan dipindahkan ke dashboard/panel pemilik usaha.
        $this->actingAs($owner)->get('/pemilik/laporan-pemasukan')->assertOk();
        $this->actingAs($admin)->get('/pemilik/laporan-pemasukan')->assertForbidden();
    }

    // ---------------------------------------------------------------------
    // Login
    // ---------------------------------------------------------------------

    public function test_login_admin_berhasil_diarahkan_ke_dashboard_admin(): void
    {
        $admin = $this->makeUser('ADMIN', 'login-admin@fixpoint.test');

        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password123',
            'role' => 'admin',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_login_dengan_password_salah_gagal(): void
    {
        $admin = $this->makeUser('ADMIN', 'salah@fixpoint.test');

        $this->from('/login')->post('/login', [
            'email' => $admin->email,
            'password' => 'password-salah',
            'role' => 'admin',
        ])->assertRedirect('/login')->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_dengan_role_tidak_sesuai_ditolak(): void
    {
        $owner = $this->makeUser('PEMILIK_USAHA', 'role-salah@fixpoint.test');

        $this->from('/login')->post('/login', [
            'email' => $owner->email,
            'password' => 'password123',
            'role' => 'admin',
        ])->assertRedirect('/login')->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    // ---------------------------------------------------------------------
    // Booking & validasi pelanggan
    // ---------------------------------------------------------------------

    private function activeSchedule(int $maxBooking = 2, int $currentBooking = 0, string $status = 'AVAILABLE'): array
    {
        $service = Service::create([
            'name' => 'Servis PC',
            'description' => 'Servis PC desktop',
            'is_active' => true,
        ]);

        $schedule = Schedule::create([
            'schedule_date' => now()->addDay()->toDateString(),
            'start_time' => '13:00:00',
            'end_time' => '14:00:00',
            'max_booking' => $maxBooking,
            'current_booking' => $currentBooking,
            'status' => $status,
        ]);

        return [$service, $schedule];
    }

    public function test_booking_valid_tersimpan_dan_service_id_terbentuk(): void
    {
        [$service, $schedule] = $this->activeSchedule();

        $this->post('/booking', [
            'nama' => 'Budi Santoso',
            'telepon' => '081234567890',
            'jenis_perangkat' => 'PC Desktop',
            'service_id' => $service->service_id,
            'keluhan' => 'PC restart sendiri',
            'schedule_id' => $schedule->schedule_id,
            'payment_method' => 'CASH',
        ])->assertRedirect('/booking')->assertSessionHas('success');

        $booking = Booking::firstOrFail();
        $this->assertStringStartsWith('FP-', $booking->service_id_code);
        $this->assertSame('PENDING', $booking->status);
        $this->assertDatabaseHas('payments', [
            'booking_id' => $booking->booking_id,
            'payment_method' => 'CASH',
            'payment_status' => 'UNPAID',
        ]);
        $this->assertSame(1, (int) $schedule->fresh()->current_booking);
    }

    public function test_booking_dengan_nomor_telepon_tidak_valid_ditolak(): void
    {
        [$service, $schedule] = $this->activeSchedule();

        $this->post('/booking', [
            'nama' => 'Budi Santoso',
            'telepon' => 'abc',
            'jenis_perangkat' => 'Laptop',
            'merek_model' => 'Asus',
            'service_id' => $service->service_id,
            'keluhan' => 'Mati total',
            'schedule_id' => $schedule->schedule_id,
            'payment_method' => 'CASH',
        ])->assertSessionHasErrors('telepon');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_booking_pada_jadwal_penuh_ditolak(): void
    {
        [$service, $schedule] = $this->activeSchedule(2, 2, 'FULL');

        $this->post('/booking', [
            'nama' => 'Siti Aminah',
            'telepon' => '081234567891',
            'jenis_perangkat' => 'PC Desktop',
            'service_id' => $service->service_id,
            'keluhan' => 'Blue screen',
            'schedule_id' => $schedule->schedule_id,
            'payment_method' => 'QRIS',
        ])->assertSessionHas('error');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_booking_tanpa_metode_pembayaran_ditolak(): void
    {
        [$service, $schedule] = $this->activeSchedule();

        $this->post('/booking', [
            'nama' => 'Siti Aminah',
            'telepon' => '081234567891',
            'jenis_perangkat' => 'PC Desktop',
            'service_id' => $service->service_id,
            'keluhan' => 'Blue screen',
            'schedule_id' => $schedule->schedule_id,
            'payment_method' => '',
        ])->assertSessionHasErrors('payment_method');
    }

    // ---------------------------------------------------------------------
    // S3-03 Laporan riwayat servis
    // ---------------------------------------------------------------------

    public function test_riwayat_servis_menampilkan_data_dan_menghormati_filter_tanggal(): void
    {
        $dalamRentang = $this->makeCompletedService('2026-11-05 10:00:00', ['code' => 'FP-INA-001']);
        $luarRentang = $this->makeCompletedService('2026-11-20 10:00:00', ['code' => 'FP-LUAR-002']);

        $admin = $this->makeUser('ADMIN', 'filter@fixpoint.test');

        $response = $this->actingAs($admin)->get('/admin/riwayat-servis?from=2026-11-04&to=2026-11-07');

        $response->assertOk()
            ->assertSee('FP-INA-001')
            ->assertDontSee('FP-LUAR-002')
            ->assertSee('1 servis selesai');
    }

    public function test_filter_tanggal_riwayat_servis_membalik_rentang_yang_tertukar(): void
    {
        $this->makeCompletedService('2026-11-05 09:00:00', ['code' => 'FP-BALIK-001']);
        $admin = $this->makeUser('ADMIN', 'balik@fixpoint.test');

        $this->actingAs($admin)
            ->get('/admin/riwayat-servis?from=2026-11-07&to=2026-11-04')
            ->assertOk()
            ->assertSee('FP-BALIK-001');
    }

    public function test_export_pdf_riwayat_servis_berhasil_diunduh(): void
    {
        $this->makeCompletedService('2026-11-06 11:00:00', ['code' => 'FP-PDF-001']);
        $admin = $this->makeUser('ADMIN', 'pdf@fixpoint.test');

        $response = $this->actingAs($admin)->get('/admin/riwayat-servis/pdf?from=2026-11-04&to=2026-11-07');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_export_excel_riwayat_servis_menghasilkan_xlsx(): void
    {
        $this->makeCompletedService('2026-11-06 12:00:00', ['code' => 'FP-XLS-001']);
        $admin = $this->makeUser('ADMIN', 'xls@fixpoint.test');

        $response = $this->actingAs($admin)->get('/admin/riwayat-servis/excel?from=2026-11-04&to=2026-11-07');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $file = $response->baseResponse->getFile()->getPathname();
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($file) === true);
        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        $this->assertNotFalse($sheet);
        $this->assertStringContainsString('FP-XLS-001', $sheet);
    }

    // ---------------------------------------------------------------------
    // S3-03 Laporan pemasukan
    // ---------------------------------------------------------------------

    public function test_laporan_pemasukan_hanya_menghitung_pembayaran_lunas(): void
    {
        $this->makeCompletedService('2026-11-05 09:00:00', [
            'code' => 'FP-LUNAS-001', 'amount' => 123000, 'method' => 'CASH', 'status' => 'PAID',
        ]);
        $this->makeCompletedService('2026-11-05 10:00:00', [
            'code' => 'FP-BELUM-002', 'amount' => 999000, 'method' => 'QRIS', 'status' => 'UNPAID',
        ]);

        $owner = $this->makeUser('PEMILIK_USAHA', 'pemasukan@fixpoint.test');

        $response = $this->actingAs($owner)->get('/pemilik/laporan-pemasukan?from=2026-11-04&to=2026-11-07');

        $response->assertOk()
            ->assertSee('Rp 123.000')
            ->assertDontSee('Rp 999.000')
            ->assertSee('FP-LUNAS-001')
            ->assertDontSee('FP-BELUM-002');
    }

    public function test_laporan_pemasukan_bisa_difilter_berdasarkan_metode(): void
    {
        $this->makeCompletedService('2026-11-05 09:00:00', [
            'code' => 'FP-CASH-001', 'amount' => 150000, 'method' => 'CASH', 'status' => 'PAID',
        ]);
        $this->makeCompletedService('2026-11-05 10:00:00', [
            'code' => 'FP-QRIS-002', 'amount' => 275000, 'method' => 'QRIS', 'status' => 'PAID',
        ]);

        $owner = $this->makeUser('PEMILIK_USAHA', 'metode@fixpoint.test');

        $this->actingAs($owner)
            ->get('/pemilik/laporan-pemasukan?method=QRIS')
            ->assertOk()
            ->assertSee('FP-QRIS-002')
            ->assertDontSee('FP-CASH-001');
    }

    public function test_export_pdf_dan_excel_pemasukan_berhasil(): void
    {
        $this->makeCompletedService('2026-11-06 13:00:00', [
            'code' => 'FP-EXP-001', 'amount' => 210000, 'method' => 'CASH', 'status' => 'PAID',
        ]);
        $owner = $this->makeUser('PEMILIK_USAHA', 'ekspor@fixpoint.test');

        $pdf = $this->actingAs($owner)->get('/pemilik/laporan-pemasukan/pdf?from=2026-11-04&to=2026-11-07');
        $pdf->assertOk();
        $pdf->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $pdf->getContent());

        $excel = $this->actingAs($owner)->get('/pemilik/laporan-pemasukan/excel?from=2026-11-04&to=2026-11-07');
        $excel->assertOk();
        $this->assertStringStartsWith('PK', $excel->baseResponse->getFile()->getContent());
    }

    public function test_pemilik_bisa_mengunduh_laporan_tetapi_tidak_bisa_membuka_pembayaran_admin(): void
    {
        $owner = $this->makeUser('PEMILIK_USAHA', 'pemilik-laporan@fixpoint.test');

        $this->actingAs($owner)->get('/pemilik/laporan-pemasukan/excel')->assertOk();
        $this->actingAs($owner)->get('/pemilik/riwayat-servis/pdf')->assertOk();
        $this->actingAs($owner)->get('/admin/pembayaran')->assertForbidden();
    }

    // ---------------------------------------------------------------------
    // S3-02 Global error handling
    // ---------------------------------------------------------------------

    public function test_halaman_404_kustom_tampil_tanpa_stack_trace(): void
    {
        $response = $this->get('/halaman-yang-tidak-ada');

        $response->assertNotFound();
        $response->assertSee('Halaman Tidak Ditemukan');
        $response->assertDontSee('SQLSTATE');
        $response->assertDontSee('/vendor/');
    }

    public function test_halaman_403_tampil_saat_akses_ditolak(): void
    {
        $owner = $this->makeUser('PEMILIK_USAHA', 'pemilik-403@fixpoint.test');

        $response = $this->actingAs($owner)->get('/dashboard-admin');

        $response->assertForbidden();
        $response->assertSee('Akses Ditolak');
        $response->assertDontSee('SQLSTATE');
    }
}
