<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\Booking;
use App\Models\Schedule;
use App\Models\Customer;
use App\Models\Device;
use App\Models\Service;
use App\Models\Payment;
use Illuminate\Support\Str;

class ApiBookingController extends Controller
{
    /**
     * S2-03 & S2-05: Pembuatan Booking dengan Dual-Layer Validation & Sanitization
     */
    public function store(Request $request)
    {
        // =====================================================
        // S2-05: VALIDASI SISI SERVER & PESAN RAMAH PENGGUNA
        // =====================================================
        $validator = Validator::make($request->all(), [
            'nama' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z\s\.\,\-]+$/' // Hanya huruf, spasi, titik, koma, strip
            ],
            'telepon' => [
                'required',
                'string',
                'min:10',
                'max:15',
                'regex:/^[0-9+\-\s()]+$/' // Hanya angka dan simbol telepon valid
            ],
            'jenis_perangkat' => 'required|in:Laptop,PC Desktop',
            'merek_model' => 'required|string|max:255',
            'service' => 'required|in:laptop,pc',
            'keluhan' => 'required|string|max:1000',
            'schedule_id' => 'required|integer|exists:schedules,schedule_id',
            'payment_method' => 'required|in:CASH,QRIS',
        ], [
            // Pesan error kustom yang ramah pengguna (S2-05 Standard)
            'nama.required' => 'Nama lengkap wajib diisi.',
            'nama.regex' => 'Nama hanya boleh berisi huruf, spasi, tanda titik, koma, atau strip.',
            'telepon.required' => 'Nomor telepon wajib diisi.',
            'telepon.min' => 'Nomor telepon minimal harus 10 digit.',
            'telepon.regex' => 'Format nomor telepon tidak valid.',
            'jenis_perangkat.required' => 'Jenis perangkat harus dipilih.',
            'merek_model.required' => 'Merek atau model perangkat wajib diisi.',
            'service.required' => 'Kategori layanan wajib dipilih.',
            'keluhan.required' => 'Detail keluhan perangkat wajib diisi.',
            'keluhan.max' => 'Keluhan maksimal 1000 karakter.',
            'schedule_id.required' => 'Jadwal servis wajib dipilih.',
            'schedule_id.exists' => 'Jadwal yang Anda pilih tidak valid atau sudah tidak tersedia.',
            'payment_method.required' => 'Metode pembayaran wajib dipilih.',
        ]);

        // Jika validasi gagal, kembalikan respons JSON dengan kode 422
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi data gagal. Silakan periksa kembali input Anda.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            return DB::transaction(function () use ($request) {
                // 1. Kunci jadwal untuk mencegah race condition (S2-06)
                $schedule = Schedule::where('schedule_id', $request->schedule_id)
                    ->lockForUpdate()
                    ->first();

                if (!$schedule || $schedule->status !== 'AVAILABLE' || $schedule->current_booking >= $schedule->max_booking) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Maaf, jadwal yang Anda pilih sudah penuh atau tidak tersedia.',
                        'errors' => ['schedule_id' => ['Slot jadwal sudah habis.']]
                    ], 422);
                }

                // 2. Tentukan Layanan
                $serviceName = $request->service === 'laptop' ? 'Servis Laptop' : 'Servis PC Desktop';
                $service = Service::where('name', $serviceName)->first();

                if (!$service) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Layanan yang dipilih tidak ditemukan di sistem.'
                    ], 422);
                }

                // 3. Cari atau Buat Customer (Idempotent berdasarkan nomor telepon)
                $customer = Customer::where('phone_number', $request->telepon)->first();
                if (!$customer) {
                    $customer = Customer::create([
                        'name' => $request->nama,
                        'phone_number' => $request->telepon,
                    ]);
                }

                // 4. Simpan Data Perangkat
                $device = Device::create([
                    'customer_id' => $customer->customer_id,
                    'device_type' => $request->jenis_perangkat,
                    'brand' => $request->merek_model,
                    'model' => $request->merek_model,
                    'complaint' => $request->keluhan,
                ]);

                // 5. Generate Service ID Code Unik
                do {
                    $serviceIdCode = 'FP-' . now()->format('Ymd') . '-' . Str::upper(Str::random(5));
                } while (Booking::where('service_id_code', $serviceIdCode)->exists());

                // 6. Simpan Booking
                $booking = Booking::create([
                    'service_id_code' => $serviceIdCode,
                    'customer_id' => $customer->customer_id,
                    'device_id' => $device->device_id,
                    'service_id' => $service->service_id,
                    'schedule_id' => $schedule->schedule_id,
                    'handled_by' => null,
                    'booking_date' => now()->toDateString(),
                    'status' => 'PENDING',
                    'notes' => $request->keluhan,
                ]);

                // 7. Catat Pembayaran Awal (UNPAID)
                Payment::create([
                    'booking_id' => $booking->booking_id,
                    'customer_id' => $customer->customer_id,
                    'service_record_id' => null,
                    'processed_by' => null,
                    'amount' => 0,
                    'payment_method' => $request->payment_method,
                    'payment_status' => 'UNPAID',
                    'payment_date' => now(),
                ]);

                // 8. Increment Kuota Jadwal
                $schedule->increment('current_booking');

                return response()->json([
                    'success' => true,
                    'message' => 'Booking berhasil dibuat. Silakan simpan Service ID Anda.',
                    'data' => [
                        'service_id_code' => $booking->service_id_code,
                        'status' => $booking->status,
                        'created_at' => $booking->created_at,
                    ]
                ], 201);
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan pada server saat memproses transaksi.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}