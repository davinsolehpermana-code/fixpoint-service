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
        return view('cek-status');
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
        $payment = Payment::where('customer_id', $booking->customer_id)->first();

        return view('cek-status', compact('booking', 'payment'));
    }

    // 3. Proses Upload Bukti Pembayaran (S2-05 & S2-02)
    public function upload(Request $request, $code)
    {
        // Validasi File (S2-05)
        $request->validate([
            'payment_proof' => 'required|image|mimes:jpeg,png,jpg|max:2048', // Max 2MB
        ]);

        $booking = Booking::where('service_id_code', $code)->firstOrFail();

        // Cegah upload jika status sudah berubah
        if ($booking->status !== 'PENDING') {
            return back()->with('error', 'Booking ini sudah diproses atau sedang diverifikasi.');
        }

        // Simpan file ke storage/app/public/bukti-bayar
        $path = $request->file('payment_proof')->store('bukti-bayar', 'public');

        // Simpan ke tabel payments (sesuai ERD Anda)
        Payment::updateOrCreate(
            ['customer_id' => $booking->customer_id],
            [
                'service_record_id' => null, // Belum ada service record
                'processed_by' => null,
                'amount' => 0,
                'payment_method' => 'QRIS',
                'payment_status' => 'UNPAID',
                'payment_proof' => $path,
                'payment_date' => now(),
            ]
        );

        // Update status booking jadi menunggu verifikasi
        $booking->update(['status' => 'WAITING_VERIFICATION']);

        return back()->with('success', 'Bukti pembayaran berhasil diunggah! Menunggu verifikasi Admin.');
    }
}