<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Http\Request;

class StatusController extends Controller
{
    // 1. Tampilkan halaman form cek status
    public function index()
    {
        return view('status.cek-status');
    }

    // 2. Proses pencarian Service ID
    public function show(Request $request)
    {
        $code = $request->input('code');
        
        if (!$code) {
            return back()->with('error', 'Silakan masukkan Service ID.');
        }

        $booking = Booking::with(['customer', 'device', 'schedule', 'service'])
            ->where('service_id_code', $code)
            ->first();

        if (!$booking) {
            return back()->with('error', 'Service ID tidak ditemukan. Silakan periksa kembali kode Anda.');
        }

        // Ambil data payment jika ada
        $payment = $booking->payment;

        return view('status.cek-status', compact('booking', 'payment'));
    }

    // 3. Proses Upload Bukti Pembayaran (S2-05 & S2-02)
    public function upload(Request $request, $code)
    {
        // 1. Validasi File & Metode Pembayaran
        $request->validate([
            'payment_method' => 'required|in:QRIS,CASH', // ✅ Validasi pilihan QRIS atau CASH
            'payment_proof'  => 'required|image|mimes:jpeg,png,jpg|max:2048', // Max 2MB
        ]);

        $booking = Booking::where('service_id_code', $code)->firstOrFail();

        // 2. Cegah upload jika status sudah berubah
        if ($booking->status !== 'PENDING') {
            return back()->with('error', 'Booking ini sudah diproses atau sedang diverifikasi.');
        }

        // 3. Simpan file ke storage/app/public/bukti-bayar
        $path = $request->file('payment_proof')->store('bukti-bayar', 'public');

        // 4. Simpan ke tabel payments (sesuai ERD Anda)
        Payment::updateOrCreate(
            ['booking_id' => $booking->booking_id],
            [
                'customer_id'       => $booking->customer_id,
                'service_record_id' => null, // Belum ada service record (sudah di-nullable via migration)
                'processed_by'      => null,
                'amount'            => 0,
                'payment_method'    => $request->payment_method, // ✅ SEKARANG DINAMIS (Mengambil dari input user)
                'payment_status'    => 'UNPAID',
                'payment_proof'     => $path,
                'payment_date'      => now(),
            ]
        );

        // 5. Update status booking jadi menunggu verifikasi
        $booking->update(['status' => 'WAITING_VERIFICATION']);

        return back()->with('success', 'Bukti pembayaran berhasil diunggah! Menunggu verifikasi Admin.');
    }

    /**
     * Memproses pembatalan booking oleh pelanggan.
     * 
     * S2-05: Dual-Layer Validation (Server-side)
     * S2-06: Database Transaction untuk pembaruan status & kuota jadwal
     */
    public function cancel(Request $request, $code)
    {
        // 1. Validasi Alasan Pembatalan (Server-Side)
        $request->validate([
            'cancellation_reason' => 'required|string|max:500',
        ], [
            'cancellation_reason.required' => 'Alasan pembatalan wajib diisi.',
            'cancellation_reason.max' => 'Alasan pembatalan maksimal 500 karakter.',
        ]);

        return \Illuminate\Support\Facades\DB::transaction(function () use ($request, $code) {
            // 2. Cari booking berdasarkan Service ID
            $booking = Booking::where('service_id_code', $code)->firstOrFail();

            // 3. Batasi pembatalan: Hanya status PENDING yang boleh dibatalkan oleh pelanggan
            if (!in_array($booking->status, ['PENDING', 'WAITING_VERIFICATION'])) {
                return back()->with('error', 'Booking tidak dapat dibatalkan karena sudah dalam proses pengerjaan atau telah selesai.');
            }

            // 4. Update status dan alasan pembatalan pada booking
            $booking->update([
                'status' => 'CANCELLED',
                'cancellation_reason' => $request->cancellation_reason,
            ]);

            // 5. Kembalikan slot kuota pada jadwal terkait jika ada
            if ($booking->schedule_id) {
                $schedule = \App\Models\Schedule::find($booking->schedule_id);
                if ($schedule && $schedule->current_booking > 0) {
                    $schedule->decrement('current_booking');
                }
            }

            return back()->with('success', 'Booking berhasil dibatalkan.');
        });
    }
}