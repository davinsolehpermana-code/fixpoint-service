<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\StatusController;
use App\Models\Schedule;

/*
|--------------------------------------------------------------------------
| Public Routes (Halaman Umum untuk Customer)
|--------------------------------------------------------------------------
*/

// Beranda
Route::get('/', function () {
    $schedules = Schedule::where('status', 'AVAILABLE')
        ->whereColumn('current_booking', '<', 'max_booking')
        ->where('schedule_date', '>=', now()->toDateString())
        ->orderBy('schedule_date')
        ->orderBy('start_time')
        ->limit(3)
        ->get();
    return view('beranda.welcome', compact('schedules'));
})->name('home');

// Ketersediaan Jadwal
Route::get('/ketersediaan-jadwal', function () {
    $schedules = Schedule::where('status', 'AVAILABLE')
        ->whereColumn('current_booking', '<', 'max_booking')
        ->where('schedule_date', '>=', now()->toDateString())
        ->orderBy('schedule_date')
        ->orderBy('start_time')
        ->get();
    return view('jadwal.ketersediaan-jadwal', compact('schedules'));
})->name('ketersediaan-jadwal');

// Booking Service
Route::get('/booking', function () {
    $schedules = Schedule::where('status', 'AVAILABLE')
        ->whereColumn('current_booking', '<', 'max_booking')
        ->where('schedule_date', '>=', now()->toDateString())
        ->orderBy('schedule_date')
        ->orderBy('start_time')
        ->get();
    return view('booking.booking', compact('schedules'));
})->name('booking');

Route::post('/booking', [BookingController::class, 'store'])->name('booking.store');

// Cek Status Service
Route::get('/cek-status', [StatusController::class, 'index'])->name('cek-status');
Route::get('/cek-status/result', [StatusController::class, 'show'])->name('cek-status.show');
Route::post('/cek-status/{code}/cancel', [StatusController::class, 'cancel'])->name('cek-status.cancel'); // 👈 Tambahkan route ini

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.process');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:ADMIN'])->group(function () {

    // Dashboard Admin
    Route::get('/dashboard-admin', function () {
        $bookings = \App\Models\Booking::with(['customer', 'device', 'service', 'schedule'])
            ->orderBy('created_at', 'desc')
            ->get();
        $stats = [
            'active'    => $bookings->whereIn('status', ['PENDING', 'WAITING_VERIFICATION', 'CONFIRMED','CANCELLED'])->count(),
            'waiting'   => $bookings->where('status', 'PENDING')->count(),
            'completed' => $bookings->where('status', 'COMPLETED')->count(),
            'schedules' => Schedule::where('status', 'AVAILABLE')->count(),
        ];
        return view('admin.dashboard-admin', compact('bookings', 'stats'));
    })->name('admin.dashboard');

    // ✅ 1. UPDATE STATUS & INPUT BIAYA (Simpan harga + metode pembayaran)
    Route::post('/admin/booking/update', function (\Illuminate\Http\Request $request) {
        $request->validate([
            'service_id_code'  => 'required|exists:bookings,service_id_code',
            'status'           => 'required|in:PENDING,WAITING_VERIFICATION,CONFIRMED,COMPLETED,REJECTED,CANCELLED',
            'notes'            => 'nullable|string',
            'amount'           => 'nullable|numeric|min:0|max:9999999999.99',
            'payment_method'   => 'nullable|in:CASH,QRIS',
        ], [
            'amount.max' => 'Total biaya tidak boleh melebihi Rp 9.999.999.999,99.',
        ]);

        $booking = \App\Models\Booking::where('service_id_code', $request->service_id_code)->first();
        $booking->update([
            'status' => $request->status,
            'notes'  => $request->notes,
        ]);

        // Simpan biaya & metode ke tabel payments
        if ($request->filled('amount') || $request->filled('payment_method')) {
            $payment = \App\Models\Payment::firstOrNew(['customer_id' => $booking->customer_id]);
            if ($request->filled('amount')) $payment->amount = $request->amount;
            if ($request->filled('payment_method')) $payment->payment_method = $request->payment_method;
            if (!$payment->payment_status) $payment->payment_status = 'UNPAID';
            $payment->service_record_id = null;
            $payment->processed_by = auth()->id();
            $payment->payment_date = now();
            $payment->save();
        }

        return back()->with('success', 'Status dan biaya berhasil diperbarui.');
    })->name('admin.booking.update');

    // ✅ 2. VERIFIKASI / TERIMA (HANYA ubah status booking, JANGAN ubah payment!)
    // INI YANG DIPERBAIKI - tidak ada lagi kode yang ubah payment jadi PAID
    Route::put('/admin/booking/{id}/verify', function ($id) {
        $booking = \App\Models\Booking::findOrFail($id);

        if (!in_array($booking->status, ['PENDING', 'WAITING_VERIFICATION'])) {
            return back()->with('error', 'Booking tidak bisa diverifikasi.');
        }

        // HANYA ubah status booking menjadi CONFIRMED
        $booking->update(['status' => 'CONFIRMED']);

        return back()->with('success', 'Booking diterima. Status diubah menjadi Sedang Dikerjakan.');
    })->name('admin.booking.verify');

    // ✅ 3. SELESAI
    Route::put('/admin/booking/{id}/complete', function ($id) {
        $booking = \App\Models\Booking::findOrFail($id);
        $booking->update(['status' => 'COMPLETED']);
        return back()->with('success', 'Servis ditandai SELESAI.');
    })->name('admin.booking.complete');

    // ✅ 4. TANDAI LUNAS
    Route::put('/admin/booking/{id}/mark-paid', function ($id) {
        $booking = \App\Models\Booking::findOrFail($id);

        $payment = \App\Models\Payment::firstOrNew(['customer_id' => $booking->customer_id]);
        $payment->payment_status = 'PAID';
        $payment->payment_date = now();
        if (!$payment->payment_method) $payment->payment_method = 'CASH';
        if (!$payment->amount) $payment->amount = 0; // Mencegah error default value
        $payment->processed_by = auth()->id();
        $payment->save();

        return back()->with('success', 'Pembayaran ditandai LUNAS.');
    })->name('admin.booking.markPaid');

    // ✅ 5. TAMBAH JADWAL
    Route::post('/admin/schedules', function (\Illuminate\Http\Request $request) {
        $request->validate([
            'schedule_date' => 'required|date|after_or_equal:today',
            'start_time'    => 'required|date_format:H:i',
            'end_time'      => 'required|date_format:H:i|after:start_time',
            'max_booking'   => 'required|integer|min:1',
        ]);

        \App\Models\Schedule::create([
            'schedule_date'   => $request->schedule_date,
            'start_time'      => $request->start_time . ':00',
            'end_time'        => $request->end_time . ':00',
            'max_booking'     => $request->max_booking,
            'current_booking' => 0,
            'status'          => 'AVAILABLE',
        ]);

        return back()->with('success_schedule', 'Jadwal berhasil ditambahkan!');
    })->name('admin.schedules.store');
});

/*
|--------------------------------------------------------------------------
| Owner Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:PEMILIK_USAHA'])->group(function () {
    Route::get('/dashboard-pemilik', function () {
        return view('dashboard-pemilik');
    })->name('pemilik.dashboard');
});