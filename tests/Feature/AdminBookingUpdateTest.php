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
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminBookingUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_amount_above_payment_column_limit_is_rejected_without_updating_booking(): void
    {
        $admin = User::create([
            'email' => 'admin@example.test',
            'password' => Hash::make('password'),
            'role' => 'ADMIN',
        ]);
        $customer = Customer::create([
            'name' => 'Test Customer',
            'phone_number' => '081234567890',
        ]);
        $device = Device::create([
            'customer_id' => $customer->customer_id,
            'device_type' => 'Laptop',
            'brand' => 'Test',
            'model' => 'Test Model',
            'complaint' => 'Testing payment amount validation',
        ]);
        $service = Service::create([
            'name' => 'Servis Laptop',
            'description' => 'Pemeriksaan laptop',
            'is_active' => true,
        ]);
        $schedule = Schedule::create([
            'schedule_date' => now()->addDay()->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'max_booking' => 1,
            'current_booking' => 1,
            'status' => 'AVAILABLE',
        ]);
        $booking = Booking::create([
            'service_id_code' => 'FP-TEST-001',
            'customer_id' => $customer->customer_id,
            'device_id' => $device->device_id,
            'service_id' => $service->service_id,
            'schedule_id' => $schedule->schedule_id,
            'booking_date' => now()->toDateString(),
            'status' => 'PENDING',
        ]);
        $payment = Payment::create([
            'booking_id' => $booking->booking_id,
            'customer_id' => $customer->customer_id,
            'service_record_id' => null,
            'processed_by' => null,
            'amount' => 0,
            'payment_method' => 'CASH',
            'payment_status' => 'UNPAID',
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.dashboard'))
            ->post(route('admin.booking.update'), [
                'service_id_code' => $booking->service_id_code,
                'status' => 'COMPLETED',
                'amount' => '50000000000',
                'payment_method' => 'CASH',
            ]);

        $response->assertRedirect(route('admin.dashboard'))
            ->assertSessionHasErrors([
                'amount' => 'Total biaya tidak boleh melebihi Rp 9.999.999.999,99.',
            ]);

        $this->assertDatabaseHas('bookings', [
            'booking_id' => $booking->booking_id,
            'status' => 'PENDING',
        ]);
        $this->assertDatabaseHas('payments', [
            'payment_id' => $payment->payment_id,
            'amount' => '0.00',
        ]);
    }
}
