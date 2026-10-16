<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use App\Models\Schedule;
use App\Models\Booking;

class Booking extends Model

{
    protected $primaryKey = 'booking_id';

    protected $fillable = [
        'service_id_code',
        'customer_id',
        'device_id',
        'service_id',
        'schedule_id',
        'handled_by',
        'booking_date',
        'status',
        'notes',
        'cancellation_reason',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relasi Customer
    |--------------------------------------------------------------------------
    */

    public function customer()
    {
        return $this->belongsTo(
            Customer::class,
            'customer_id',
            'customer_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Relasi Device
    |--------------------------------------------------------------------------
    */

    public function device()
    {
        return $this->belongsTo(
            Device::class,
            'device_id',
            'device_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Relasi Service
    |--------------------------------------------------------------------------
    */

    public function service()
    {
        return $this->belongsTo(
            Service::class,
            'service_id',
            'service_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Relasi Schedule
    |--------------------------------------------------------------------------
    */

    public function schedule()
    {
        return $this->belongsTo(
            Schedule::class,
            'schedule_id',
            'schedule_id'
        );
    }

    public function payment()
    {
        return $this->hasOne(Payment::class, 'booking_id', 'booking_id');
    }

    public function updatebooking(Request $request)
    {
        $request->validate([
            'service_id_code' => 'required|exists:bookings,service_id_code',
            'status' => 'required|in:PENDING,WAITING_VERIFICATION,CONFIRMED,COMPLETED,REJECTED',
            'notes' => 'nullable|string',
            'amount' => 'nullable|numeric|min:0|max:9999999999.99',
            'payment_method' => 'nullable|in:CASH,QRIS',
        ]);

        $booking = Booking::where('service_id_code', $request->service_id_code)->firstOrFail();
        $booking->update([
            'status' => $request->status,
            'notes' => $request->notes,
        ]);

        // Simpan biaya & metode pembayaran ke tabel payments.
        // Jika metode pembayaran belum tersedia, biarkan data tetap belum lunas.
        if ($request->filled('amount') || $request->filled('payment_method')) {
            $payment = $booking->payment ?: Payment::whereNull('booking_id')
                ->where('customer_id', $booking->customer_id)
                ->latest('payment_id')
                ->first();
            $payment = $payment ?: new Payment;

            $payment->booking_id = $booking->booking_id;
            $payment->customer_id = $booking->customer_id;
            if ($request->filled('amount')) {
                $payment->amount = $request->amount;
            }

            if ($request->filled('payment_method')) {
                $payment->payment_method = $request->payment_method;
            }

            if (! $payment->payment_status) {
                $payment->payment_status = 'UNPAID';
            }

            $payment->service_record_id = null;
            $payment->processed_by = auth()->id();
            $payment->payment_date = now();
            $payment->save();
        }

        return back()->with('success', 'Status dan biaya berhasil diupdate.');
    }


public function store(Request $request)
{
    // Validasi input awal...
    $request->validate([
        'schedule_id' => 'required|exists:schedules,schedule_id',
        // ... validasi lainnya
    ]);

    // Ambil data jadwal dengan kuncian database untuk mencegah race condition
    $schedule = Schedule::where('schedule_id', $request->schedule_id)->lockForUpdate()->first();

    // Hitung jumlah booking yang aktif (selain dibatalkan atau ditolak)
    $activeBookingsCount = Booking::where('schedule_id', $request->schedule_id)
        ->whereNotIn('status', ['CANCELLED', 'REJECTED'])
        ->count();

    // Cek apakah slot sudah penuh
    if ($activeBookingsCount >= $schedule->max_booking) {
        return back()->withInput()->with('error', 'Maaf, slot pada jadwal tersebut sudah habis. Silakan pilih jadwal atau tanggal lain.');
    }

    // ... Lanjutkan proses simpan database & transaksi Anda ...
}
}
