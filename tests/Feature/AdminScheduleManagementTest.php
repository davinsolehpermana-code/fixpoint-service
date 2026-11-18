<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Device;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pengujian CRUD pengelolaan jadwal servis pada Dashboard Admin (SCH-01 … SCH-12).
 */
class AdminScheduleManagementTest extends TestCase
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

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeSchedule(array $overrides = []): Schedule
    {
        return Schedule::create(array_merge([
            'schedule_date' => now()->addDays(3)->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
            'max_booking' => 5,
            'current_booking' => 0,
            'status' => 'AVAILABLE',
        ], $overrides));
    }

    private function attachBooking(Schedule $schedule, string $code, string $status = 'PENDING'): Booking
    {
        $customer = Customer::create([
            'name' => 'Pelanggan Jadwal',
            'phone_number' => '0813' . random_int(10000000, 99999999),
        ]);

        $device = Device::create([
            'customer_id' => $customer->customer_id,
            'device_type' => 'Laptop',
            'brand' => 'Lenovo',
            'model' => 'ThinkPad',
            'complaint' => 'Keyboard tidak berfungsi',
        ]);

        $service = Service::create([
            'name' => 'Servis Laptop',
            'description' => 'Pemeriksaan laptop',
            'is_active' => true,
        ]);

        return Booking::create([
            'service_id_code' => $code,
            'customer_id' => $customer->customer_id,
            'device_id' => $device->device_id,
            'service_id' => $service->service_id,
            'schedule_id' => $schedule->schedule_id,
            'booking_date' => now()->toDateString(),
            'status' => $status,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(Schedule $schedule, array $overrides = []): array
    {
        return array_merge([
            'schedule_date' => substr((string) $schedule->schedule_date, 0, 10),
            'start_time' => substr((string) $schedule->start_time, 0, 5),
            'end_time' => substr((string) $schedule->end_time, 0, 5),
            'max_booking' => (int) $schedule->max_booking,
            'status' => $schedule->status === 'CLOSED' ? 'CLOSED' : 'AVAILABLE',
        ], $overrides);
    }

    public function test_sch_01_admin_membuka_daftar_jadwal(): void
    {
        $admin = $this->makeUser('ADMIN', 'sch01@fixpoint.test');
        $this->makeSchedule();

        $this->actingAs($admin)
            ->get(route('admin.schedules.index'))
            ->assertOk()
            ->assertSee('Kelola Jadwal Servis')
            ->assertSee('Daftar Jadwal Servis')
            ->assertSee('Edit')
            ->assertSee('Hapus');
    }

    public function test_sch_02_admin_membuka_form_edit_dengan_nilai_lama(): void
    {
        $admin = $this->makeUser('ADMIN', 'sch02@fixpoint.test');
        $schedule = $this->makeSchedule();

        $this->actingAs($admin)
            ->get(route('admin.schedules.edit', $schedule->schedule_id))
            ->assertOk()
            ->assertSee('Edit Jadwal Servis')
            ->assertSee('value="09:00"', false)
            ->assertSee('value="11:00"', false)
            ->assertSee('value="' . substr((string) $schedule->schedule_date, 0, 10) . '"', false);
    }

    public function test_sch_03_admin_mengubah_kapasitas_slot(): void
    {
        $admin = $this->makeUser('ADMIN', 'sch03@fixpoint.test');
        $schedule = $this->makeSchedule(['max_booking' => 5, 'current_booking' => 2]);
        $this->attachBooking($schedule, 'FP-SCH-03-A');
        $this->attachBooking($schedule, 'FP-SCH-03-B');

        $this->actingAs($admin)
            ->put(route('admin.schedules.update', $schedule->schedule_id), $this->payload($schedule, ['max_booking' => 8]))
            ->assertRedirect(route('admin.schedules.index'))
            ->assertSessionHas('success', fn (string $message) => str_contains($message, 'sisa 6 slot'));

        $fresh = $schedule->fresh();
        $this->assertSame(8, (int) $fresh->max_booking);
        $this->assertSame(2, (int) $fresh->current_booking, 'Jumlah booking lama tidak boleh berubah.');
        $this->assertSame(2, Booking::where('schedule_id', $schedule->schedule_id)->count());
        $this->assertSame('AVAILABLE', $fresh->status);
    }

    public function test_sch_04_admin_menurunkan_slot_di_bawah_jumlah_booking_ditolak(): void
    {
        $admin = $this->makeUser('ADMIN', 'sch04@fixpoint.test');
        $schedule = $this->makeSchedule(['max_booking' => 5, 'current_booking' => 2]);
        $this->attachBooking($schedule, 'FP-SCH-04-A');
        $this->attachBooking($schedule, 'FP-SCH-04-B');

        $this->actingAs($admin)
            ->from(route('admin.schedules.edit', $schedule->schedule_id))
            ->put(route('admin.schedules.update', $schedule->schedule_id), $this->payload($schedule, ['max_booking' => 1]))
            ->assertRedirect(route('admin.schedules.edit', $schedule->schedule_id))
            ->assertSessionHas('error', fn (string $message) => str_contains($message, 'slot terpakai'));

        $this->assertSame(5, (int) $schedule->fresh()->max_booking);
        $this->assertSame(2, (int) $schedule->fresh()->current_booking);
    }

    public function test_sch_05_admin_mengubah_jam_mulai_dan_selesai(): void
    {
        $admin = $this->makeUser('ADMIN', 'sch05@fixpoint.test');
        $schedule = $this->makeSchedule();

        $this->actingAs($admin)
            ->put(route('admin.schedules.update', $schedule->schedule_id), $this->payload($schedule, [
                'start_time' => '13:00',
                'end_time' => '15:00',
            ]))
            ->assertRedirect(route('admin.schedules.index'))
            ->assertSessionHas('success', fn (string $message) => str_contains($message, 'Tanggal dan jam jadwal sudah diperbarui'));

        $fresh = $schedule->fresh();
        $this->assertSame('13:00:00', $fresh->start_time);
        $this->assertSame('15:00:00', $fresh->end_time);
    }

    public function test_sch_06_jam_selesai_lebih_awal_daripada_jam_mulai_ditolak(): void
    {
        $admin = $this->makeUser('ADMIN', 'sch06@fixpoint.test');
        $schedule = $this->makeSchedule();

        $this->actingAs($admin)
            ->from(route('admin.schedules.edit', $schedule->schedule_id))
            ->put(route('admin.schedules.update', $schedule->schedule_id), $this->payload($schedule, [
                'start_time' => '09:00',
                'end_time' => '08:00',
            ]))
            ->assertSessionHasErrors('end_time');

        $this->assertSame('09:00:00', $schedule->fresh()->start_time);
        $this->assertSame('11:00:00', $schedule->fresh()->end_time);
    }

    public function test_sch_07_perubahan_waktu_jadwal_yang_sudah_terikat_booking_dilindungi(): void
    {
        $admin = $this->makeUser('ADMIN', 'sch07@fixpoint.test');
        $schedule = $this->makeSchedule(['current_booking' => 1]);
        $this->attachBooking($schedule, 'FP-SCH-07-A');

        $this->actingAs($admin)
            ->from(route('admin.schedules.edit', $schedule->schedule_id))
            ->put(route('admin.schedules.update', $schedule->schedule_id), $this->payload($schedule, [
                'start_time' => '13:00',
                'end_time' => '15:00',
            ]))
            ->assertSessionHas('error', fn (string $message) => str_contains($message, 'sudah memiliki 1 booking'));

        $fresh = $schedule->fresh();
        $this->assertSame('09:00:00', $fresh->start_time);
        $this->assertSame('11:00:00', $fresh->end_time);
        $this->assertDatabaseHas('bookings', [
            'service_id_code' => 'FP-SCH-07-A',
            'schedule_id' => $schedule->schedule_id,
        ]);
    }

    public function test_sch_08_admin_menghapus_jadwal_tanpa_booking(): void
    {
        $admin = $this->makeUser('ADMIN', 'sch08@fixpoint.test');
        $schedule = $this->makeSchedule();

        $this->actingAs($admin)
            ->delete(route('admin.schedules.destroy', $schedule->schedule_id))
            ->assertRedirect(route('admin.schedules.index'))
            ->assertSessionHas('success', 'Jadwal servis berhasil dihapus.');

        $this->assertDatabaseMissing('schedules', ['schedule_id' => $schedule->schedule_id]);
    }

    public function test_sch_09_admin_menghapus_jadwal_yang_memiliki_booking_ditolak(): void
    {
        $admin = $this->makeUser('ADMIN', 'sch09@fixpoint.test');
        $schedule = $this->makeSchedule(['current_booking' => 1]);
        $booking = $this->attachBooking($schedule, 'FP-SCH-09-A');

        $this->actingAs($admin)
            ->delete(route('admin.schedules.destroy', $schedule->schedule_id))
            ->assertSessionHas('error', fn (string $message) => str_contains($message, 'masih memiliki 1 booking'));

        $this->assertDatabaseHas('schedules', ['schedule_id' => $schedule->schedule_id]);
        $this->assertDatabaseHas('bookings', ['booking_id' => $booking->booking_id]);
    }

    public function test_sch_10_pemilik_usaha_tidak_bisa_membuka_form_edit_jadwal(): void
    {
        $owner = $this->makeUser('PEMILIK_USAHA', 'sch10@fixpoint.test');
        $schedule = $this->makeSchedule();

        $this->actingAs($owner)
            ->get(route('admin.schedules.edit', $schedule->schedule_id))
            ->assertForbidden();
    }

    public function test_sch_11_pemilik_usaha_tidak_bisa_menghapus_jadwal(): void
    {
        $owner = $this->makeUser('PEMILIK_USAHA', 'sch11@fixpoint.test');
        $schedule = $this->makeSchedule();

        $this->actingAs($owner)
            ->delete(route('admin.schedules.destroy', $schedule->schedule_id))
            ->assertForbidden();

        $this->assertDatabaseHas('schedules', ['schedule_id' => $schedule->schedule_id]);
    }

    public function test_sch_12_form_jadwal_selalu_membawa_token_csrf(): void
    {
        $admin = $this->makeUser('ADMIN', 'sch12@fixpoint.test');
        $schedule = $this->makeSchedule(['current_booking' => 1]);
        $this->attachBooking($schedule, 'FP-SCH-12-A');

        $this->actingAs($admin)
            ->get(route('admin.schedules.index'))
            ->assertOk()
            ->assertSee('name="_token"', false);

        $this->actingAs($admin)
            ->get(route('admin.schedules.edit', $schedule->schedule_id))
            ->assertOk()
            ->assertSee('name="_token"', false);

        // Penolakan request tanpa token CSRF yang valid (HTTP 419) diuji langsung
        // pada server berjalan, karena middleware CSRF dilewati pada unit test.
    }

    public function test_jadwal_bertabrakan_pada_tanggal_yang_sama_ditolak(): void
    {
        $admin = $this->makeUser('ADMIN', 'overlap@fixpoint.test');
        $schedule = $this->makeSchedule();
        $this->makeSchedule(['start_time' => '13:00:00', 'end_time' => '15:00:00']);

        $this->actingAs($admin)
            ->from(route('admin.schedules.edit', $schedule->schedule_id))
            ->put(route('admin.schedules.update', $schedule->schedule_id), $this->payload($schedule, [
                'start_time' => '14:00',
                'end_time' => '16:00',
            ]))
            ->assertSessionHas('error', fn (string $message) => str_contains($message, 'bertabrakan'));

        $this->assertSame('09:00:00', $schedule->fresh()->start_time);
    }

    public function test_status_penuh_ditetapkan_otomatis_saat_kuota_tidak_bertambah(): void
    {
        $admin = $this->makeUser('ADMIN', 'full@fixpoint.test');
        $schedule = $this->makeSchedule(['max_booking' => 2, 'current_booking' => 2, 'status' => 'FULL']);

        // Kuota ditambah melebihi slot terpakai: jadwal kembali tersedia.
        $this->actingAs($admin)
            ->put(route('admin.schedules.update', $schedule->schedule_id), $this->payload($schedule, ['max_booking' => 4]))
            ->assertRedirect(route('admin.schedules.index'));

        $this->assertSame('AVAILABLE', $schedule->fresh()->status);

        // Kuota dikembalikan tepat sebesar slot terpakai: status menjadi penuh.
        $this->actingAs($admin)
            ->put(route('admin.schedules.update', $schedule->schedule_id), $this->payload($schedule, ['max_booking' => 2]))
            ->assertRedirect(route('admin.schedules.index'));

        $this->assertSame(2, (int) $schedule->fresh()->current_booking);
        $this->assertSame('FULL', $schedule->fresh()->status);
    }

    public function test_sch_13_edit_cepat_slot_dan_jam_tersedia_di_daftar_jadwal(): void
    {
        $admin = $this->makeUser('ADMIN', 'sch13@fixpoint.test');
        $this->makeSchedule();

        $this->actingAs($admin)
            ->get(route('admin.schedules.index'))
            ->assertOk()
            ->assertSee('Edit cepat')
            ->assertSee('data-quick-edit', false)
            ->assertSee('name="max_booking"', false);
    }

    public function test_sch_14_edit_cepat_slot_dan_jam_tersimpan(): void
    {
        $admin = $this->makeUser('ADMIN', 'sch14@fixpoint.test');
        $schedule = $this->makeSchedule();

        $this->actingAs($admin)
            ->put(route('admin.schedules.update', $schedule->schedule_id), $this->payload($schedule, [
                'start_time' => '08:00',
                'end_time' => '10:00',
                'max_booking' => 9,
            ]))
            ->assertRedirect(route('admin.schedules.index'))
            ->assertSessionHas('success', fn (string $message) => str_contains($message, 'Kapasitas 9 slot'));

        $fresh = $schedule->fresh();
        $this->assertSame('08:00:00', $fresh->start_time);
        $this->assertSame('10:00:00', $fresh->end_time);
        $this->assertSame(9, (int) $fresh->max_booking);
    }
}
