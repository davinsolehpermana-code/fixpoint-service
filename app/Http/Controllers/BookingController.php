<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Device;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;

class BookingController extends Controller
{
    /**
     * Menyimpan booking baru dari formulir pelanggan.
     * 
     * S2-05: Dual-Layer Validation (Server-Side)
     * S2-06: Database Transaction (ACID) + Anti Double Booking
     */
    public function store(Request $request)
    {
        // =====================================================
        // S2-05: VALIDASI SISI SERVER YANG KETAT
        // =====================================================
        $validated = $request->validate([
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
                'regex:/^[0-9+\-\s()]+$/' // Hanya angka, +, -, spasi, ()
            ],
            'jenis_perangkat' => 'required|in:Laptop,PC Desktop',
            'merek_model' => 'required|string|max:255',
            'service' => 'required|in:laptop,pc',
            'keluhan' => 'required|string|max:1000', // Dikurangi dari 5000 untuk keamanan
            'schedule_id' => 'required|integer|exists:schedules,schedule_id',
            'payment_method' => 'required|in:CASH,QRIS',
        ], [
            // S2-05: Pesan error ramah pengguna (user-friendly)
            'nama.regex' => 'Nama hanya boleh berisi huruf, spasi, tanda titik, koma, atau strip.',
            'telepon.regex' => 'Nomor telepon hanya boleh berisi angka dan karakter + - ( ).',
            'telepon.min' => 'Nomor telepon minimal 10 digit.',
            'keluhan.max' => 'Keluhan maksimal 1000 karakter.',
            'schedule_id.exists' => 'Jadwal yang dipilih tidak valid.',
        ]);

        // =====================================================
        // S2-06: DATABASE TRANSACTION (ACID - Atomicity)
        // Jika salah satu langkah gagal, SEMUA dibatalkan (ROLLBACK)
        // =====================================================
        return DB::transaction(function () use ($validated) {
            
            // -------------------------------------------------
            // LANGKAH 1: KUNCI JADWAL (Anti Double Booking)
            // lockForUpdate() mencegah race condition
            // -------------------------------------------------
            $schedule = Schedule::where('schedule_id', $validated['schedule_id'])
                ->lockForUpdate() // 🔒 Mencegah user lain ambil slot yang sama di milidetik yang sama
                ->first();

            // Validasi ulang ketersediaan slot (S2-02: Core Business Flow)
            if (!$schedule || $schedule->status !== 'AVAILABLE' || $schedule->current_booking >= $schedule->max_booking) {
                return back()->withInput()->with('error', 'Maaf, jadwal yang Anda pilih sudah penuh atau tidak tersedia. Silakan pilih jadwal lain.');
            }

            // -------------------------------------------------
            // LANGKAH 2: CARI LAYANAN
            // -------------------------------------------------
            $serviceName = $validated['service'] === 'laptop' 
                ? 'Servis Laptop' 
                : 'Servis PC Desktop';
            
            $service = Service::where('name', $serviceName)->first();
            
            if (!$service) {
                throw new Exception('Layanan tidak ditemukan di sistem. Silakan hubungi admin.');
            }

            // -------------------------------------------------
            // LANGKAH 3: CARI ATAU BUAT CUSTOMER (Idempotent)
            // Mencegah duplikasi customer dengan nomor telepon sama
            // -------------------------------------------------
            $customer = Customer::where('phone_number', $validated['telepon'])->first();
            
            if (!$customer) {
                $customer = Customer::create([
                    'name' => $validated['nama'],
                    'phone_number' => $validated['telepon'],
                ]);
            }

            // -------------------------------------------------
            // LANGKAH 4: SIMPAN PERANGKAT
            // -------------------------------------------------
            $device = Device::create([
                'customer_id' => $customer->customer_id,
                'device_type' => $validated['jenis_perangkat'],
                'brand' => $validated['merek_model'],
                'model' => $validated['merek_model'],
                'complaint' => $validated['keluhan'],
            ]);

            // -------------------------------------------------
            // LANGKAH 5: GENERATE SERVICE ID UNIK
            // Format: FP-YYYYMMDD-XXXXX (contoh: FP-20261011-ABC12)
            // -------------------------------------------------
            do {
                $serviceIdCode = 'FP-' 
                    . now()->format('Ymd') 
                    . '-' 
                    . Str::upper(Str::random(5));
            } while (Booking::where('service_id_code', $serviceIdCode)->exists());

            // -------------------------------------------------
            // LANGKAH 6: SIMPAN BOOKING
            // -------------------------------------------------
            $booking = Booking::create([
                'service_id_code' => $serviceIdCode,
                'customer_id' => $customer->customer_id,
                'device_id' => $device->device_id,
                'service_id' => $service->service_id,
                'schedule_id' => $schedule->schedule_id,
                'handled_by' => null,
                'booking_date' => now()->toDateString(),
                'status' => 'PENDING',
                'notes' => $validated['keluhan'],
            ]);

            // -------------------------------------------------
            // LANGKAH 7: BUAT RECORD PAYMENT AWAL
            // Status: UNPAID, Amount: 0 (akan diupdate admin nanti)
            // -------------------------------------------------
            Payment::create([
                'booking_id' => $booking->booking_id,
                'customer_id' => $customer->customer_id,
                'service_record_id' => null,
                'processed_by' => null,
                'amount' => 0,
                'payment_method' => $validated['payment_method'],
                'payment_status' => 'UNPAID',
                'payment_date' => now(),
            ]);

            // -------------------------------------------------
            // LANGKAH 8: UPDATE KUOTA JADWAL (Terakhir!)
            // Hanya dilakukan jika semua langkah di atas sukses
            // -------------------------------------------------
            $schedule->increment('current_booking');

            // -------------------------------------------------
            // LANGKAH 9: RETURN SUKSES
            // Transaction otomatis di-COMMIT oleh Laravel di sini
            // -------------------------------------------------
            return redirect()->route('booking')
                ->with('success', 'Booking berhasil dikirim! Silakan simpan Service ID Anda.')
                ->with('service_id_code', $serviceIdCode);
        });
    }

    /**
     * Memperbarui status booking dan mencatat pembayaran.
     * Digunakan oleh Admin untuk update status & input biaya.
     * 
     * S2-02: Core Business Flow (Admin Side)
     * S2-05: Validasi input biaya & metode pembayaran
     */
    public function updateBookingStatus(Request $request)
    {
       
        // S2-05: Validasi ketat untuk update status
        $validated = $request->validate([
            'service_id_code' => 'required|exists:bookings,service_id_code',
            'status' => 'required|in:PENDING,WAITING_VERIFICATION,CONFIRMED,COMPLETED,REJECTED,CANCELLED', // 👈 Tambahkan 'CANCELLED' di sini
            'notes' => 'nullable|string|max:1000',
            'amount' => 'nullable|numeric|min:0|max:99999999.99',
            'payment_method' => 'nullable|in:CASH,QRIS',
        ], [
            'amount.max' => 'Total biaya tidak boleh lebih dari Rp 99.999.999,99',
            'status.in' => 'Status yang dipilih tidak valid.',
        ]);
        

        // Cari booking berdasarkan Service ID
        $booking = Booking::where('service_id_code', $validated['service_id_code'])->firstOrFail();

        // Perbarui status dan catatan booking
        $booking->update([
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? $booking->notes,
        ]);

        // Simpan pembayaran jika nominal atau metode diisi
        if ($request->filled('amount') || $request->filled('payment_method')) {
            // Cari payment yang sudah ada untuk booking ini
            $payment = $booking->payment ?? Payment::whereNull('booking_id')
                ->where('customer_id', $booking->customer_id)
                ->latest('payment_id')
                ->first();

            // Jika tidak ada, buat baru
            $payment = $payment ?: new Payment;

            $payment->booking_id = $booking->booking_id;
            $payment->customer_id = $booking->customer_id;

            if ($request->filled('amount')) {
                $payment->amount = $validated['amount'];
            }

            if ($request->filled('payment_method')) {
                $payment->payment_method = $validated['payment_method'];
            }

            // Default status: UNPAID (belum lunas)
            // Admin harus klik "Tandai Lunas" secara manual nanti
            if (!$payment->payment_status) {
                $payment->payment_status = 'UNPAID';
            }

            $payment->service_record_id = null;
            $payment->processed_by = Auth::id();
            $payment->payment_date = now();
            $payment->save();
        }

        return back()->with('success', 'Status dan biaya berhasil diperbarui.');
    }
}