<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\StatusController;
use App\Models\Schedule;

/*
|--------------------------------------------------------------------------
| Public Routes (Halaman Umum)
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/ketersediaan-jadwal', function () {
    $schedules = Schedule::where('status', 'AVAILABLE')
        ->whereColumn('current_booking', '<', 'max_booking')
        ->where('schedule_date', '>=', now()->toDateString())
        ->orderBy('schedule_date')
        ->orderBy('start_time')
        ->get();

    return view('ketersediaan-jadwal', compact('schedules'));
})->name('ketersediaan-jadwal');

Route::get('/booking', function () {
    $schedules = Schedule::where('status', 'AVAILABLE')
        ->whereColumn('current_booking', '<', 'max_booking')
        ->orderBy('schedule_date')
        ->orderBy('start_time')
        ->get();

    return view('booking', compact('schedules'));
})->name('booking');

Route::post('/booking', [BookingController::class, 'store'])->name('booking.store');

// Route Cek Status (Sudah terhubung ke StatusController)
Route::get('/cek-status', [StatusController::class, 'index'])->name('cek-status');
Route::get('/cek-status/result', [StatusController::class, 'show'])->name('cek-status.show');
Route::post('/cek-status/{code}/upload', [StatusController::class, 'upload'])->name('cek-status.upload');

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
| Protected Routes: Admin
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:ADMIN'])->group(function () {
    
    // Dashboard Admin Dinamis
    Route::get('/dashboard-admin', function () {
        $bookings = \App\Models\Booking::with(['customer', 'device', 'service', 'schedule'])
            ->orderBy('created_at', 'desc')
            ->get();

        $stats = [
            'active' => $bookings->whereIn('status', ['PENDING', 'WAITING_VERIFICATION', 'CONFIRMED'])->count(),
            'waiting' => $bookings->where('status', 'PENDING')->count(),
            'completed' => $bookings->where('status', 'COMPLETED')->count(),
            'schedules' => Schedule::where('status', 'AVAILABLE')->count()
        ];

        return view('dashboard-admin', compact('bookings', 'stats'));
    })->name('admin.dashboard');

    // Update Status Servis Manual
    Route::post('/admin/booking/update', function (\Illuminate\Http\Request $request) {
        $request->validate([
            'service_id_code' => 'required|exists:bookings,service_id_code',
            'status' => 'required|in:PENDING,WAITING_VERIFICATION,CONFIRMED,COMPLETED,REJECTED',
            'notes' => 'nullable|string'
        ]);

        $booking = \App\Models\Booking::where('service_id_code', $request->service_id_code)->first();
        $booking->update([
            'status' => $request->status,
            'notes' => $request->notes
        ]);

        return back()->with('success', 'Status booking berhasil diperbarui.');
    })->name('admin.booking.update');

    // Verifikasi Pembayaran (Tombol "Terima" di Dashboard)
    Route::put('/admin/booking/{id}/verify', function (\Illuminate\Http\Request $request, $id) {
        $booking = \App\Models\Booking::findOrFail($id);
        
        if ($booking->status !== 'WAITING_VERIFICATION') {
            return back()->with('error', 'Booking ini tidak dalam status menunggu verifikasi.');
        }

        $booking->update(['status' => 'CONFIRMED']);

        // Update status payment jika ada
        $payment = \App\Models\Payment::where('customer_id', $booking->customer_id)->first();
        if ($payment) {
            $payment->update(['payment_status' => 'PAID']);
        }

        return back()->with('success', 'Pembayaran diverifikasi. Status servis diubah menjadi Dikerjakan.');
    })->name('admin.booking.verify');
});

/*
|--------------------------------------------------------------------------
| Protected Routes: Pemilik Usaha
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:PEMILIK_USAHA'])->group(function () {
    Route::get('/dashboard-pemilik', function () {
        return view('dashboard-pemilik');
    })->name('pemilik.dashboard');
});