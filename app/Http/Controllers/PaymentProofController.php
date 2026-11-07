<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PaymentProofController extends Controller
{
    /** Admin dan Pemilik dapat melihat bukti melalui route yang dilindungi role middleware. */
    public function show(int $paymentId)
    {
        $payment = Payment::query()->findOrFail($paymentId);

        return $this->streamProof($payment);
    }

    /** Pelanggan membuka bukti milik Service ID yang sedang ia cek. */
    public function showForCustomer(string $code)
    {
        $booking = Booking::query()
            ->with('payment')
            ->where('service_id_code', strtoupper(trim($code)))
            ->firstOrFail();

        abort_unless($booking->status === 'COMPLETED', 404);
        abort_unless($booking->payment, 404);

        return $this->streamProof($booking->payment);
    }

    /** Mengirim gambar sebagai inline response tanpa membangun URL storage publik di Blade. */
    private function streamProof(Payment $payment)
    {
        abort_unless($payment->payment_method === 'QRIS', 404);

        $path = (string) $payment->payment_proof;
        abort_if($path === '', 404, 'Bukti pembayaran belum tersedia.');

        foreach (['local', 'public'] as $diskName) {
            $disk = Storage::disk($diskName);

            if (!$disk->exists($path)) {
                continue;
            }

            $mime = $disk->mimeType($path) ?: '';
            abort_unless(in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true), 404);

            return response()->file($disk->path($path), [
                'Content-Type' => $mime,
                'Content-Disposition' => 'inline; filename="bukti-pembayaran"',
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, no-store, max-age=0',
                'X-Frame-Options' => 'SAMEORIGIN',
            ]);
        }

        abort(404, 'File bukti pembayaran tidak ditemukan.');
    }

    /**
     * Minta pelanggan mengunggah ulang. File lama dipertahankan sampai upload baru sukses.
     * Status UNPAID + payment_proof terisi menjadi penanda tanpa menambah kolom database.
     * Catatan admin (opsional) disimpan di payments.notes dan tampil di halaman cek status.
     */
    public function requestReupload(Request $request, int $paymentId): RedirectResponse
    {
        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:200'],
        ], [
            'note.max' => 'Catatan penolakan maksimal 200 karakter.',
        ]);

        $note = trim((string) ($validated['note'] ?? ''));

        $updated = DB::transaction(function () use ($paymentId, $note) {
            $payment = Payment::query()
                ->where('payment_id', $paymentId)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $payment->payment_method !== 'QRIS'
                || $payment->payment_status !== 'WAITING_VERIFICATION'
                || !filled($payment->payment_proof)
            ) {
                return false;
            }

            $booking = Booking::query()
                ->where('booking_id', $payment->booking_id)
                ->lockForUpdate()
                ->first();

            if (!$booking || $booking->status !== 'COMPLETED') {
                return false;
            }

            $payment->update([
                'payment_status' => 'UNPAID',
                'processed_by' => null,
                'notes' => $note !== '' ? $note : null,
            ]);

            return true;
        });

        if (!$updated) {
            return back()->with('error', 'Bukti tidak dapat diminta ulang karena status pembayaran sudah berubah.');
        }

        return back()->with(
            'success',
            $note !== ''
                ? 'Bukti pembayaran ditolak. Pelanggan diminta mengunggah ulang dengan catatan: ' . $note
                : 'Bukti pembayaran ditolak. Pelanggan diminta mengunggah ulang bukti QRIS.'
        );
    }
}
