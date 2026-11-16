<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Device;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Verifikasi keamanan dasar S3-06:
 * validasi input, CSRF, escaping Blade, validasi MIME upload, dan
 * penanganan percobaan SQL injection.
 */
class SecurityVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role, string $email): User
    {
        return User::create([
            'email' => $email,
            'password' => bcrypt('password123'),
            'role' => $role,
        ]);
    }

    private function makeSchedule(): Schedule
    {
        return Schedule::create([
            'schedule_date' => now()->addDays(2)->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
            'max_booking' => 5,
            'current_booking' => 0,
            'status' => 'AVAILABLE',
        ]);
    }

    private function makeService(): Service
    {
        return Service::create([
            'name' => 'Servis Laptop',
            'description' => 'Pemeriksaan laptop',
            'is_active' => true,
        ]);
    }

    private function makeCompletedQrisBooking(): Booking
    {
        $customer = Customer::create([
            'name' => 'Pelanggan QRIS',
            'phone_number' => '081200009999',
        ]);

        $device = Device::create([
            'customer_id' => $customer->customer_id,
            'device_type' => 'Laptop',
            'brand' => 'Asus',
            'model' => 'Vivobook',
            'complaint' => 'Layar berkedip',
        ]);

        $booking = Booking::create([
            'service_id_code' => 'FP-SEC-QRIS-01',
            'customer_id' => $customer->customer_id,
            'device_id' => $device->device_id,
            'service_id' => $this->makeService()->service_id,
            'schedule_id' => $this->makeSchedule()->schedule_id,
            'booking_date' => now()->toDateString(),
            'status' => 'COMPLETED',
        ]);

        Payment::create([
            'booking_id' => $booking->booking_id,
            'customer_id' => $customer->customer_id,
            'amount' => 250000,
            'payment_method' => 'QRIS',
            'payment_status' => 'UNPAID',
            'payment_date' => now(),
        ]);

        return $booking;
    }

    public function test_sec_01_validasi_input_menolak_payload_berbahaya(): void
    {
        $service = $this->makeService();
        $schedule = $this->makeSchedule();

        $base = [
            'jenis_perangkat' => 'Laptop',
            'merek_model' => 'Asus',
            'service_id' => $service->service_id,
            'keluhan' => 'Laptop mati',
            'schedule_id' => $schedule->schedule_id,
            'payment_method' => 'CASH',
        ];

        $this->post('/booking', array_merge($base, [
            'nama' => '<script>alert(1)</script>',
            'telepon' => '081234567890',
        ]))->assertSessionHasErrors('nama');

        $this->post('/booking', array_merge($base, [
            'nama' => 'Budi Santoso',
            'telepon' => "' OR 1=1 --",
        ]))->assertSessionHasErrors('telepon');

        $this->post('/booking', array_merge($base, [
            'nama' => 'Budi Santoso',
            'telepon' => '081234567890',
            'keluhan' => str_repeat('A', 1001),
        ]))->assertSessionHasErrors('keluhan');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_sec_02_setiap_form_membawa_token_csrf(): void
    {
        $admin = $this->makeUser('ADMIN', 'sec02@fixpoint.test');
        $this->makeSchedule();

        $this->get('/booking')->assertOk()->assertSee('name="_token"', false);
        $this->actingAs($admin)->get('/admin/jadwal-servis')->assertOk()->assertSee('name="_token"', false);
        $this->actingAs($admin)->get('/admin/data-service')->assertOk()->assertSee('name="_token"', false);
    }

    public function test_sec_03_output_blade_terescape_sehingga_xss_tidak_dieksekusi(): void
    {
        $admin = $this->makeUser('ADMIN', 'sec03@fixpoint.test');

        DB::table('customers')->insert([
            'name' => '<script>alert("xss")</script>',
            'phone_number' => '081200007777',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get('/admin/data-customer');

        $response->assertOk();
        $response->assertSee('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', false);
        $response->assertDontSee('<script>alert("xss")</script>', false);
    }

    public function test_sec_04_upload_menolak_mime_type_yang_tidak_diizinkan(): void
    {
        Storage::fake('public');
        $booking = $this->makeCompletedQrisBooking();

        $this->from('/cek-status')->post('/cek-status/upload-proof', [
            'service_id_code' => $booking->service_id_code,
            'payment_proof' => UploadedFile::fake()->create('bukti.php', 20, 'application/x-php'),
        ])->assertSessionHasErrors('payment_proof');

        $this->from('/cek-status')->post('/cek-status/upload-proof', [
            'service_id_code' => $booking->service_id_code,
            'payment_proof' => UploadedFile::fake()->create('bukti.png', 20, 'text/plain'),
        ])->assertSessionHasErrors('payment_proof');

        $this->assertDatabaseHas('payments', [
            'booking_id' => $booking->booking_id,
            'payment_proof' => null,
            'payment_status' => 'UNPAID',
        ]);
    }

    public function test_sec_04b_upload_gambar_png_valid_diterima(): void
    {
        Storage::fake('public');
        $booking = $this->makeCompletedQrisBooking();

        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
        );

        $this->post('/cek-status/upload-proof', [
            'service_id_code' => $booking->service_id_code,
            'payment_proof' => UploadedFile::fake()->createWithContent('bukti.png', $png),
        ])->assertSessionHasNoErrors()->assertRedirect();

        $payment = Payment::where('booking_id', $booking->booking_id)->first();
        $this->assertSame('WAITING_VERIFICATION', $payment->payment_status);
        $this->assertNotNull($payment->payment_proof);
        Storage::disk('public')->assertExists($payment->payment_proof);
    }

    public function test_sec_05_input_pencarian_sql_injection_diperlakukan_sebagai_teks_biasa(): void
    {
        $admin = $this->makeUser('ADMIN', 'sec05@fixpoint.test');

        Customer::create([
            'name' => 'Pelanggan Aman',
            'phone_number' => '081200005555',
        ]);

        $response = $this->actingAs($admin)->get('/admin/data-customer?q=' . urlencode("' OR 1=1 --"));

        $response->assertOk();
        $response->assertSee('0 pelanggan');
        $response->assertDontSee('Pelanggan Aman');
        $response->assertDontSee('SQLSTATE');
    }

    public function test_sec_06_sql_injection_pada_id_jadwal_ditolak_validasi(): void
    {
        $service = $this->makeService();

        $this->post('/booking', [
            'nama' => 'Budi Santoso',
            'telepon' => '081234567890',
            'jenis_perangkat' => 'Laptop',
            'merek_model' => 'Asus',
            'service_id' => $service->service_id,
            'keluhan' => 'Laptop mati',
            'schedule_id' => '1 OR 1=1',
            'payment_method' => 'CASH',
        ])->assertSessionHasErrors('schedule_id');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_sec_07_password_seeder_tersimpan_sebagai_hash(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@fixpoint.test')->firstOrFail();

        $this->assertNotSame('Admin12345', $admin->password);
        $this->assertTrue(Hash::check('Admin12345', $admin->password));
    }

    public function test_sec_08_field_tambahan_dari_client_tidak_ikut_tersimpan(): void
    {
        $service = $this->makeService();
        $schedule = $this->makeSchedule();

        $this->post('/booking', [
            'nama' => 'Budi Santoso',
            'telepon' => '081234567890',
            'jenis_perangkat' => 'Laptop',
            'merek_model' => 'Asus',
            'service_id' => $service->service_id,
            'keluhan' => 'Laptop mati',
            'schedule_id' => $schedule->schedule_id,
            'payment_method' => 'CASH',
            'status' => 'COMPLETED',
            'handled_by' => 1,
        ])->assertSessionHasNoErrors();

        $booking = Booking::firstOrFail();
        $this->assertSame('PENDING', $booking->status);
        $this->assertNull($booking->handled_by);
    }
}
