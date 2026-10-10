<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Device;
use App\Models\Schedule;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'telepon' => ['required', 'string', 'max:30'],
            'jenis_perangkat' => ['required', 'in:Laptop,PC Desktop'],
            'merek_model' => ['required', 'string', 'max:255'],
            'service' => ['required', 'in:laptop,pc'],
            'keluhan' => ['required', 'string'],
            'schedule_id' => [
                'required',
                'integer',
                'exists:schedules,schedule_id'
            ],
        ]);

        // Validasi kecocokan jenis perangkat dan layanan (Prioritas 2)
        if (
            ($validated['jenis_perangkat'] === 'Laptop' && $validated['service'] !== 'laptop') ||
            ($validated['jenis_perangkat'] === 'PC Desktop' && $validated['service'] !== 'pc')
        ) {
            throw ValidationException::withMessages([
                'service' => 'Jenis layanan harus sesuai dengan jenis perangkat yang dipilih.',
            ]);
        }

        $booking = DB::transaction(function () use ($validated) {
            $schedule = Schedule::where(
                'schedule_id',
                $validated['schedule_id']
            )->lockForUpdate()->firstOrFail();

            if (
                $schedule->status !== 'AVAILABLE' ||
                $schedule->current_booking >= $schedule->max_booking ||
                $schedule->schedule_date < now()->toDateString()
            ) {
                throw ValidationException::withMessages([
                    'schedule_id' => 'Jadwal sudah penuh atau tidak tersedia. Silakan pilih jadwal lain.',
                ]);
            }

            $serviceName = $validated['service'] === 'laptop'
                ? 'Servis Laptop'
                : 'Servis PC Desktop';

            $service = Service::where('name', $serviceName)
                ->where('is_active', true)
                ->first();

            if (!$service) {
                throw ValidationException::withMessages([
                    'service' => 'Layanan yang dipilih tidak tersedia.',
                ]);
            }

            $customer = Customer::create([
                'name' => $validated['nama'],
                'phone_number' => $validated['telepon'],
            ]);

            $device = Device::create([
                'customer_id' => $customer->customer_id,
                'device_type' => $validated['jenis_perangkat'],
                'brand' => $validated['merek_model'],
                'model' => $validated['merek_model'],
                'complaint' => $validated['keluhan'],
            ]);

            $booking = Booking::create([
                'service_id_code' => 'FPS-' . strtoupper(Str::random(8)),
                'customer_id' => $customer->customer_id,
                'device_id' => $device->device_id,
                'service_id' => $service->service_id,
                'schedule_id' => $schedule->schedule_id,
                'handled_by' => null,
                'booking_date' => now(),
                'status' => 'PENDING',
                'notes' => $validated['keluhan'],
            ]);

            $schedule->increment('current_booking');

            if ($schedule->current_booking >= $schedule->max_booking) {
                $schedule->update(['status' => 'FULL']);
            }

            return $booking;
        });

        // Redirect kembali ke halaman booking dengan flash message dan Service ID
        return redirect()
            ->route('booking')
            ->with('success', 'Booking berhasil dibuat.')
            ->with('service_id_code', $booking->service_id_code);
    }
}