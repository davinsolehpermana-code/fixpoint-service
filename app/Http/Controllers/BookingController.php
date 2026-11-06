<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Device;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\ServiceRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    /**
     * Menyimpan booking pelanggan dan mengurangi kuota jadwal secara atomik.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => [
                'required', 'string', 'max:255',
                'regex:/^[a-zA-Z\\s\\.\\,\\-]+$/',
            ],
            'telepon' => [
                'required', 'string', 'min:10', 'max:15',
                'regex:/^[0-9+\\-\\s()]+$/',
            ],
            'jenis_perangkat' => ['required', Rule::in(['Laptop', 'PC Desktop'])],
            // Merek/model hanya relevan untuk laptop; PC desktop cukup jenis perangkatnya.
            'merek_model' => ['nullable', 'string', 'max:255', 'required_unless:jenis_perangkat,PC Desktop'],
            'service_id' => [
                'required',
                'integer',
                Rule::exists('services', 'service_id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'keluhan' => ['required', 'string', 'max:1000'],
            'schedule_id' => ['required', 'integer', 'exists:schedules,schedule_id'],
            'payment_method' => ['required', Rule::in(['CASH', 'QRIS'])],
        ], [
            'nama.regex' => 'Nama hanya boleh berisi huruf, spasi, tanda titik, koma, atau strip.',
            'telepon.regex' => 'Nomor telepon hanya boleh berisi angka dan karakter + - ( ).',
            'telepon.min' => 'Nomor telepon minimal 10 digit.',
            'merek_model.required_unless' => 'Merek / model wajib diisi untuk perangkat laptop.',
            'service_id.exists' => 'Layanan yang dipilih tidak tersedia. Silakan pilih layanan lain.',
            'schedule_id.exists' => 'Jadwal yang dipilih tidak valid.',
            'keluhan.max' => 'Keluhan maksimal 1000 karakter.',
        ]);

        return DB::transaction(function () use ($validated) {
            $schedule = Schedule::query()
                ->where('schedule_id', $validated['schedule_id'])
                ->whereDate('schedule_date', '>=', now()->toDateString())
                ->lockForUpdate()
                ->first();

            if (
                !$schedule ||
                $schedule->status !== 'AVAILABLE' ||
                $schedule->current_booking >= $schedule->max_booking
            ) {
                return back()->withInput()->with(
                    'error',
                    'Maaf, jadwal yang Anda pilih sudah penuh atau tidak tersedia. Silakan pilih jadwal lain.'
                );
            }

            // Ambil kategori layanan dari tabel services, bukan nama yang dikunci di kode.
            $service = Service::query()
                ->where('service_id', $validated['service_id'])
                ->where('is_active', true)
                ->first();

            if (!$service) {
                return back()->withInput()->with(
                    'error',
                    'Layanan yang dipilih sudah tidak aktif. Silakan pilih layanan lain.'
                );
            }

            $customer = Customer::query()
                ->where('phone_number', $validated['telepon'])
                ->first();

            if (!$customer) {
                $customer = Customer::create([
                    'name' => $validated['nama'],
                    'phone_number' => $validated['telepon'],
                ]);
            }

            // PC desktop tidak memakai merek/model, kolomnya diisi string kosong
            // karena struktur tabel devices mewajibkan nilai (NOT NULL).
            $deviceName = $validated['jenis_perangkat'] === 'PC Desktop'
                ? ''
                : trim((string) ($validated['merek_model'] ?? ''));

            $device = Device::create([
                'customer_id' => $customer->customer_id,
                'device_type' => $validated['jenis_perangkat'],
                'brand' => $deviceName,
                'model' => $deviceName,
                'complaint' => $validated['keluhan'],
            ]);

            do {
                $serviceIdCode = 'FP-' . now()->format('Ymd') . '-' . Str::upper(Str::random(5));
            } while (Booking::query()->where('service_id_code', $serviceIdCode)->exists());

            $booking = Booking::create([
                'service_id_code' => $serviceIdCode,
                'customer_id' => $customer->customer_id,
                'device_id' => $device->device_id,
                'service_id' => $service->service_id,
                'schedule_id' => $schedule->schedule_id,
                'handled_by' => null,
                'booking_date' => now()->toDateString(),
                'status' => 'PENDING',
                'notes' => null,
            ]);

            // Metode pembayaran ditentukan pelanggan dan tidak diminta ulang dari Admin.
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

            $schedule->increment('current_booking');
            $schedule->refresh();

            // Kuota habis bukan berarti jadwal hilang, hanya berubah status menjadi FULL.
            if ($schedule->status === 'AVAILABLE' && (int) $schedule->current_booking >= (int) $schedule->max_booking) {
                $schedule->update(['status' => 'FULL']);
            }

            return redirect()->route('booking')
                ->with('success', 'Booking berhasil dikirim! Silakan simpan Service ID Anda.')
                ->with('service_id_code', $serviceIdCode);
        });
    }

    /**
     * Admin menerima booking. Catatan servis dibuat saat booking diterima
     * agar kolom service_records.handled_by yang wajib dapat diisi.
     */
    public function verifyBooking(int $id)
    {
        return DB::transaction(function () use ($id) {
            $booking = Booking::query()->where('booking_id', $id)->lockForUpdate()->firstOrFail();

            if (!in_array($booking->status, ['PENDING', 'WAITING_VERIFICATION'], true)) {
                return back()->with('error', 'Booking tidak bisa diverifikasi pada status saat ini.');
            }

            $adminId = Auth::id();

            $serviceRecord = ServiceRecord::query()->firstOrCreate(
                ['booking_id' => $booking->booking_id],
                [
                    'handled_by' => $adminId,
                    'service_status' => 'PENDING',
                ]
            );

            $booking->update([
                'status' => 'CONFIRMED',
                'handled_by' => $serviceRecord->handled_by ?: $adminId,
            ]);

            return back()->with('success', 'Booking diterima. Catatan servis berhasil disiapkan.');
        });
    }

    /**
     * Memperbarui diagnosis, estimasi, status servis, dan biaya akhir.
     * Metode pembayaran tetap membaca pilihan pelanggan dari tabel payments.
     */
    public function updateBookingStatus(Request $request)
    {
        $validated = $request->validate([
            'service_id_code' => ['required', 'exists:bookings,service_id_code'],
            'service_status' => ['required', Rule::in(['DIAGNOSIS', 'PROCESS', 'COMPLETED'])],
            'diagnosis' => ['nullable', 'string', 'max:5000'],
            'estimated_duration' => ['nullable', 'string', 'max:100'],
            'estimated_cost' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'final_cost' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'service_notes' => ['nullable', 'string', 'max:3000'],
        ], [
            'service_id_code.exists' => 'Service ID tidak ditemukan.',
            'estimated_cost.max' => 'Estimasi biaya melebihi batas yang diizinkan.',
            'final_cost.max' => 'Biaya akhir melebihi batas yang diizinkan.',
        ]);

        return DB::transaction(function () use ($validated) {
            $booking = Booking::query()
                ->where('service_id_code', $validated['service_id_code'])
                ->lockForUpdate()
                ->firstOrFail();

            if (!in_array($booking->status, ['CONFIRMED', 'COMPLETED'], true)) {
                throw ValidationException::withMessages([
                    'service_id_code' => 'Terima booking terlebih dahulu sebelum memperbarui proses servis.',
                ]);
            }

            $adminId = Auth::id();
            $serviceRecord = ServiceRecord::query()
                ->where('booking_id', $booking->booking_id)
                ->lockForUpdate()
                ->first();

            if (!$serviceRecord) {
                $serviceRecord = ServiceRecord::create([
                    'booking_id' => $booking->booking_id,
                    'handled_by' => $booking->handled_by ?: $adminId,
                    'service_status' => 'PENDING',
                ]);
            }

            $payment = Payment::query()
                ->where('booking_id', $booking->booking_id)
                ->lockForUpdate()
                ->first();

            if (!$payment) {
                throw ValidationException::withMessages([
                    'service_id_code' => 'Data pembayaran untuk booking ini tidak ditemukan. Periksa data booking terlebih dahulu.',
                ]);
            }

            if ($payment->payment_status === 'PAID') {
                throw ValidationException::withMessages([
                    'service_id_code' => 'Servis yang sudah dibayar tidak dapat diubah melalui form ini.',
                ]);
            }

            $statusOrder = [
                'PENDING' => 0,
                'DIAGNOSIS' => 1,
                'PROCESS' => 2,
                'COMPLETED' => 3,
            ];
            $currentServiceStatus = $serviceRecord->service_status ?: 'PENDING';
            if (($statusOrder[$validated['service_status']] ?? 0) < ($statusOrder[$currentServiceStatus] ?? 0)) {
                throw ValidationException::withMessages([
                    'service_status' => 'Status pengerjaan tidak dapat dikembalikan ke tahap sebelumnya.',
                ]);
            }

            if ($validated['service_status'] === 'COMPLETED' && !array_key_exists('final_cost', $validated)) {
                throw ValidationException::withMessages([
                    'final_cost' => 'Masukkan biaya akhir sebelum menandai servis selesai.',
                ]);
            }

            if ($validated['service_status'] === 'COMPLETED' && $validated['final_cost'] === null) {
                throw ValidationException::withMessages([
                    'final_cost' => 'Masukkan biaya akhir sebelum menandai servis selesai.',
                ]);
            }

            if ($validated['service_status'] === 'COMPLETED' && (float) $validated['final_cost'] <= 0) {
                throw ValidationException::withMessages([
                    'final_cost' => 'Biaya akhir harus lebih dari Rp0 sebelum servis diselesaikan.',
                ]);
            }

            $serviceRecord->handled_by = $serviceRecord->handled_by ?: $adminId;
            $serviceRecord->diagnosis = $validated['diagnosis'] ?? null;
            $serviceRecord->service_status = $validated['service_status'];

            if (array_key_exists('estimated_cost', $validated)) {
                $serviceRecord->estimated_cost = $validated['estimated_cost'];
            }

            if ($validated['service_status'] === 'PROCESS' && !$serviceRecord->started_at) {
                $serviceRecord->started_at = now();
            }

            if ($validated['service_status'] === 'COMPLETED') {
                $serviceRecord->final_cost = $validated['final_cost'];
                $serviceRecord->completed_at = $serviceRecord->completed_at ?: now();
            }

            // Estimasi waktu dan catatan disimpan di service_notes agar tidak perlu kolom/tabel baru.
            $noteParts = [];
            $duration = trim((string) ($validated['estimated_duration'] ?? ''));
            $notes = trim((string) ($validated['service_notes'] ?? ''));

            if ($duration !== '') {
                $noteParts[] = 'Estimasi pengerjaan: ' . $duration;
            }
            if ($notes !== '') {
                $noteParts[] = 'Catatan: ' . $notes;
            }
            $serviceRecord->service_notes = $noteParts ? implode("\n\n", $noteParts) : null;
            $serviceRecord->save();

            $booking->handled_by = $serviceRecord->handled_by;
            $booking->status = $validated['service_status'] === 'COMPLETED' ? 'COMPLETED' : 'CONFIRMED';
            $booking->save();

            if ($validated['service_status'] === 'COMPLETED') {
                // Tagihan akhir dihubungkan dengan catatan servis. Tetap UNPAID sampai Admin menerima pembayaran.
                $payment->service_record_id = $serviceRecord->service_record_id;
                $payment->amount = $serviceRecord->final_cost;
                $payment->payment_status = $payment->payment_status ?: 'UNPAID';
                $payment->save();
            }

            return back()->with('success', 'Diagnosis, estimasi, dan status servis berhasil diperbarui.');
        });
    }

    /** Mengonfirmasi pembayaran setelah uang Cash/QRIS benar-benar diterima. */
    public function markPaid(int $id)
    {
        return DB::transaction(function () use ($id) {
            $booking = Booking::query()->where('booking_id', $id)->lockForUpdate()->firstOrFail();

            if ($booking->status !== 'COMPLETED') {
                return back()->with('error', 'Pembayaran hanya dapat dikonfirmasi setelah servis selesai.');
            }

            $payment = Payment::query()
                ->where('booking_id', $booking->booking_id)
                ->lockForUpdate()
                ->first();

            if (!$payment) {
                return back()->with('error', 'Data pembayaran tidak ditemukan.');
            }

            if ($payment->payment_status === 'PAID') {
                return back()->with('success', 'Pembayaran untuk Service ID ini sudah lunas.');
            }

            if (!in_array($payment->payment_method, ['CASH', 'QRIS'], true)) {
                return back()->with('error', 'Metode pembayaran belum tercatat dengan benar.');
            }

            if (!is_numeric($payment->amount) || (float) $payment->amount <= 0) {
                return back()->with('error', 'Pembayaran belum dapat dikonfirmasi karena biaya akhir belum tersedia.');
            }

            $serviceRecord = ServiceRecord::query()
                ->where('booking_id', $booking->booking_id)
                ->first();

            if (!$serviceRecord || $serviceRecord->service_status !== 'COMPLETED') {
                return back()->with('error', 'Catatan servis belum berstatus selesai.');
            }

            $payment->service_record_id = $serviceRecord->service_record_id;
            $payment->payment_status = 'PAID';
            $payment->processed_by = Auth::id();
            $payment->payment_date = now();
            $payment->save();

            return back()->with('success', 'Pembayaran berhasil dikonfirmasi dan status transaksi menjadi lunas.');
        });
    }
}
