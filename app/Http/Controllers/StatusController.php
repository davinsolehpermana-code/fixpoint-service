<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Schedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class StatusController extends Controller
{
    /**
     * Menampilkan halaman pencarian status servis.
     */
    public function index(): View
    {
        return view('status.cek-status');
    }

    /**
     * Mencari booking berdasarkan Service ID dan menampilkan data servis,
     * catatan servis, serta pembayaran yang terhubung dengan booking tersebut.
     */
    public function show(Request $request): View|RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50'],
        ], [
            'code.required' => 'Silakan masukkan Service ID.',
            'code.max' => 'Service ID tidak valid.',
        ]);

        $code = strtoupper(trim($validated['code']));

        $booking = Booking::query()
            ->with([
                'customer',
                'device',
                'schedule',
                'service',
                'payment',
            ])
            ->where('service_id_code', $code)
            ->first();

        if (!$booking) {
            return redirect()
                ->route('cek-status')
                ->withInput(['code' => $code])
                ->with('error', 'Service ID tidak ditemukan. Periksa kembali kode yang Anda masukkan.');
        }

        // Ambil catatan servis langsung dari tabel agar halaman tidak bergantung
        // pada relasi model yang mungkin belum ditambahkan di Booking.php.
        $serviceRecord = DB::table('service_records')
            ->where('booking_id', $booking->booking_id)
            ->orderByDesc('service_record_id')
            ->first();

        return view('status.cek-status', [
            'booking' => $booking,
            'payment' => $booking->payment,
            'serviceRecord' => $serviceRecord,
        ]);
    }

    /**
     * Membatalkan booking pelanggan yang belum masuk proses diagnosis/pengerjaan.
     * Slot jadwal dikembalikan dalam transaksi database yang sama.
     */
    public function cancel(Request $request, string $code): RedirectResponse
    {
        $validated = $request->validate([
            'cancellation_reason' => ['required', 'string', 'max:500'],
        ], [
            'cancellation_reason.required' => 'Alasan pembatalan wajib diisi.',
            'cancellation_reason.max' => 'Alasan pembatalan maksimal 500 karakter.',
        ]);

        $result = DB::transaction(function () use ($validated, $code) {
            $booking = Booking::query()
                ->where('service_id_code', strtoupper(trim($code)))
                ->lockForUpdate()
                ->firstOrFail();

            if (!in_array($booking->status, ['PENDING', 'WAITING_VERIFICATION'], true)) {
                return 'Booking tidak dapat dibatalkan karena sudah dikonfirmasi, sedang dikerjakan, atau telah selesai.';
            }

            $booking->update([
                'status' => 'CANCELLED',
                'cancellation_reason' => $validated['cancellation_reason'],
            ]);

            if ($booking->schedule_id) {
                $schedule = Schedule::query()
                    ->where('schedule_id', $booking->schedule_id)
                    ->lockForUpdate()
                    ->first();

                if ($schedule && $schedule->current_booking > 0) {
                    $schedule->decrement('current_booking');
                    $schedule->refresh();

                    // Slot kembali tersedia setelah pembatalan, kecuali admin menutupnya.
                    if ($schedule->status === 'FULL' && (int) $schedule->current_booking < (int) $schedule->max_booking) {
                        $schedule->update(['status' => 'AVAILABLE']);
                    }
                }
            }

            return null;
        });

        $redirect = redirect()->route('cek-status.show', ['code' => strtoupper(trim($code))]);

        if ($result !== null) {
            return $redirect->with('error', $result);
        }

        return $redirect->with('success', 'Booking berhasil dibatalkan. Slot jadwal telah dikembalikan.');
    }

    /**
     * Mengunggah bukti pembayaran QRIS setelah servis selesai dan tagihan tersedia.
     * Metode pembayaran tidak diubah oleh pelanggan; sistem memakai metode yang
     * sudah dipilih saat booking.
     */
    public function uploadProof(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'service_id_code' => ['required', 'string', 'exists:bookings,service_id_code'],
            'payment_proof' => [
                'required',
                'file',
                'image',
                'mimes:jpeg,png,jpg,webp',
                'mimetypes:image/jpeg,image/png,image/webp',
                'max:2048',
            ],
        ], [
            'payment_proof.required' => 'Wajib melampirkan foto bukti pembayaran.',
            'payment_proof.image' => 'File bukti pembayaran harus berupa gambar.',
            'payment_proof.mimes' => 'Format gambar hanya boleh JPG, JPEG, PNG, atau WEBP.',
            'payment_proof.mimetypes' => 'Isi file harus benar-benar berupa gambar JPG, PNG, atau WEBP.',
            'payment_proof.max' => 'Ukuran bukti pembayaran maksimal 2 MB.',
        ]);

        $code = strtoupper(trim($validated['service_id_code']));
        $newPath = $request->file('payment_proof')->store('payment_proofs', 'public');
        $oldPath = null;

        try {
            $result = DB::transaction(function () use ($code, $newPath, &$oldPath) {
                $booking = Booking::query()
                    ->where('service_id_code', $code)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($booking->status !== 'COMPLETED') {
                    return 'Bukti pembayaran hanya dapat dikirim setelah servis selesai.';
                }

                $payment = Payment::query()
                    ->where('booking_id', $booking->booking_id)
                    ->lockForUpdate()
                    ->first();

                if (!$payment) {
                    return 'Data pembayaran belum tersedia. Silakan hubungi Admin.';
                }

                if ($payment->payment_method !== 'QRIS') {
                    return 'Upload bukti hanya tersedia untuk pembayaran dengan metode QRIS.';
                }

                if (!is_numeric($payment->amount) || (float) $payment->amount <= 0) {
                    return 'Tagihan belum ditetapkan. Silakan periksa kembali setelah Admin memperbarui biaya servis.';
                }

                if ($payment->payment_status === 'PAID') {
                    return 'Pembayaran sudah dikonfirmasi lunas. Bukti tidak perlu diunggah kembali.';
                }

                if ($payment->payment_status === 'WAITING_VERIFICATION') {
                    return 'Bukti pembayaran sebelumnya sedang menunggu verifikasi Admin.';
                }

                $oldPath = $payment->payment_proof;

                $payment->update([
                    'payment_proof' => $newPath,
                    'payment_status' => 'WAITING_VERIFICATION',
                    'payment_date' => now(),
                    'processed_by' => null,
                    'notes' => null,
                ]);

                return null;
            });
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($newPath);
            throw $exception;
        }

        if ($result !== null) {
            Storage::disk('public')->delete($newPath);

            return redirect()
                ->route('cek-status.show', ['code' => $code])
                ->with('error', $result);
        }

        if ($oldPath && $oldPath !== $newPath) {
            Storage::disk('public')->delete($oldPath);
        }

        return redirect()
            ->route('cek-status.show', ['code' => $code])
            ->with('success', 'Bukti pembayaran berhasil dikirim. Pembayaran sedang menunggu verifikasi Admin.');
    }

    /**
     * Alias kompatibilitas untuk route upload lama, bila masih digunakan.
     * Metode pembayaran tetap mengikuti pilihan pelanggan saat booking.
     */
    public function upload(Request $request, string $code): RedirectResponse
    {
        $request->merge(['service_id_code' => $code]);

        return $this->uploadProof($request);
    }
}
