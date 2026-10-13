<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Booking;
use App\Models\Schedule;
use App\Models\Service;
use Tests\TestCase;

class BookingPaymentMethodTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_selected_payment_method_is_saved_for_the_booking_and_status_page(): void
    {
        Service::create([
            'name' => 'Servis Laptop',
            'description' => 'Pemeriksaan laptop',
            'is_active' => true,
        ]);

        $schedule = Schedule::create([
            'schedule_date' => now()->addDay()->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'max_booking' => 2,
            'current_booking' => 0,
            'status' => 'AVAILABLE',
        ]);

        foreach (['CASH', 'QRIS'] as $index => $paymentMethod) {
            $response = $this->post('/booking', [
                'nama' => 'Pelanggan ' . $paymentMethod,
                'telepon' => '0812345678' . $index,
                'jenis_perangkat' => 'Laptop',
                'merek_model' => 'Test Laptop',
                'service' => 'laptop',
                'keluhan' => 'Tes pilihan pembayaran',
                'schedule_id' => $schedule->schedule_id,
                'payment_method' => $paymentMethod,
            ]);

            $response->assertRedirect('/booking');
            $booking = Booking::latest('booking_id')->firstOrFail();

            $this->assertDatabaseHas('payments', [
                'booking_id' => $booking->booking_id,
                'customer_id' => $booking->customer_id,
                'payment_method' => $paymentMethod,
                'payment_status' => 'UNPAID',
                'amount' => 0,
            ]);
            $this->assertSame($paymentMethod, $booking->payment->payment_method);

            if ($paymentMethod === 'CASH') {
                $booking->payment->update(['payment_status' => 'PAID']);
            }

            $statusPage = $this->get(route('cek-status.show', ['code' => $booking->service_id_code]))
                ->assertOk()
                ->assertSee($paymentMethod);

            if ($paymentMethod === 'CASH') {
                $statusPage->assertSee('Belum Bayar')
                    ->assertDontSee('Pembayaran Lunas');
            }
        }

        $this->post('/booking', [
            'nama' => 'Pelanggan tanpa metode',
            'telepon' => '081234567899',
            'jenis_perangkat' => 'Laptop',
            'merek_model' => 'Test Laptop',
            'service' => 'laptop',
            'keluhan' => 'Tes validasi metode pembayaran',
            'schedule_id' => $schedule->schedule_id,
            'payment_method' => '',
        ])->assertSessionHasErrors('payment_method');
    }
}
