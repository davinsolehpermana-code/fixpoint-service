<?php

namespace Tests\Feature;

use App\Models\Schedule;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Menguji sakelar ketersediaan layanan (is_active) di panel Admin dan
 * proteksi order pada halaman booking pelanggan.
 */
class ServiceAvailabilityTest extends TestCase
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

    private function makeSchedule(): Schedule
    {
        return Schedule::create([
            'schedule_date' => now()->addDay()->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'max_booking' => 3,
            'current_booking' => 0,
            'status' => 'AVAILABLE',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function bookingPayload(Service $service, Schedule $schedule): array
    {
        return [
            'nama' => 'Pelanggan Uji',
            'telepon' => '081234567890',
            'jenis_perangkat' => 'Laptop',
            'merek_model' => 'ASUS VivoBook',
            'service_id' => $service->service_id,
            'keluhan' => 'Layar bergaris',
            'schedule_id' => $schedule->schedule_id,
            'payment_method' => 'CASH',
        ];
    }

    public function test_admin_dapat_memblokir_dan_membuka_layanan(): void
    {
        $admin = $this->makeUser('ADMIN', 'admin@example.test');
        $service = Service::create(['name' => 'Ganti LCD', 'is_active' => true]);

        $this->actingAs($admin)
            ->patch(route('admin.data-service.toggle', $service->service_id))
            ->assertRedirect(route('admin.data-service'))
            ->assertSessionHas('success');

        $this->assertFalse((bool) $service->fresh()->is_active);

        $this->actingAs($admin)
            ->patch(route('admin.data-service.toggle', $service->service_id))
            ->assertRedirect(route('admin.data-service'));

        $this->assertTrue((bool) $service->fresh()->is_active);
    }

    public function test_layanan_yang_diblokir_tidak_tampil_dan_tidak_bisa_dipesan(): void
    {
        $service = Service::create(['name' => 'Servis Diblokir', 'is_active' => false]);
        $schedule = $this->makeSchedule();

        $this->get(route('booking'))
            ->assertOk()
            ->assertDontSee('Servis Diblokir');

        $this->post(route('booking.store'), $this->bookingPayload($service, $schedule))
            ->assertSessionHasErrors('service_id');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_layanan_yang_tersedia_tetap_bisa_dipesan(): void
    {
        $service = Service::create(['name' => 'Servis Tersedia', 'is_active' => true]);
        $schedule = $this->makeSchedule();

        $this->get(route('booking'))
            ->assertOk()
            ->assertSee('Servis Tersedia');

        $this->post(route('booking.store'), $this->bookingPayload($service, $schedule))
            ->assertRedirect(route('booking'))
            ->assertSessionHas('service_id_code');

        $this->assertDatabaseCount('bookings', 1);
        $this->assertDatabaseHas('payments', [
            'payment_method' => 'CASH',
            'payment_status' => 'UNPAID',
        ]);
    }

    public function test_hanya_admin_yang_dapat_mengubah_ketersediaan_layanan(): void
    {
        $service = Service::create(['name' => 'Servis Terkunci', 'is_active' => true]);
        $owner = $this->makeUser('PEMILIK_USAHA', 'pemilik@example.test');

        $this->actingAs($owner)
            ->patch(route('admin.data-service.toggle', $service->service_id))
            ->assertForbidden();

        auth()->logout();

        $this->patch(route('admin.data-service.toggle', $service->service_id))
            ->assertRedirect(route('login'));

        $this->assertTrue((bool) $service->fresh()->is_active);
    }
}
