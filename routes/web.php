<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\StatusController;
use App\Http\Controllers\AdminPanelController;
use App\Models\Schedule;
use App\Models\Service;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    $schedules = Schedule::query()
        ->where('status', 'AVAILABLE')
        ->whereColumn('current_booking', '<', 'max_booking')
        ->whereDate('schedule_date', '>=', now()->toDateString())
        ->orderBy('schedule_date')
        ->orderBy('start_time')
        ->limit(3)
        ->get();

    return view('beranda.welcome', compact('schedules'));
})->name('home');

Route::get('/ketersediaan-jadwal', function () {
    $schedules = Schedule::query()
        ->where('status', 'AVAILABLE')
        ->whereColumn('current_booking', '<', 'max_booking')
        ->whereDate('schedule_date', '>=', now()->toDateString())
        ->orderBy('schedule_date')
        ->orderBy('start_time')
        ->get();

    return view('jadwal.ketersediaan-jadwal', compact('schedules'));
})->name('ketersediaan-jadwal');

Route::get('/booking', function () {
    $schedules = Schedule::query()
        ->where('status', 'AVAILABLE')
        ->whereColumn('current_booking', '<', 'max_booking')
        ->whereDate('schedule_date', '>=', now()->toDateString())
        ->orderBy('schedule_date')
        ->orderBy('start_time')
        ->get();

    $services = Service::query()
        ->where('is_active', true)
        ->orderBy('name')
        ->get();

    return view('booking.booking', compact('schedules', 'services'));
})->name('booking');

Route::post('/booking', [BookingController::class, 'store'])->name('booking.store');

Route::get('/cek-status', [StatusController::class, 'index'])->name('cek-status');
Route::get('/cek-status/result', [StatusController::class, 'show'])->name('cek-status.show');
Route::post('/cek-status/{code}/cancel', [StatusController::class, 'cancel'])->name('cek-status.cancel');
Route::post('/cek-status/upload-proof', [StatusController::class, 'uploadProof'])->name('cek-status.upload-proof');

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
    Route::get('/dashboard-admin', [AdminPanelController::class, 'dashboard'])->name('admin.dashboard');

    // Operasional
    Route::get('/admin/jadwal-servis', [AdminPanelController::class, 'schedules'])->name('admin.schedules.index');
    Route::post('/admin/jadwal-servis', [AdminPanelController::class, 'storeSchedule'])->name('admin.schedules.store');
    Route::get('/admin/kelola-proses-servis', [AdminPanelController::class, 'serviceProcess'])->name('admin.service-process');

    // Data Master
    Route::get('/admin/data-customer', [AdminPanelController::class, 'customers'])->name('admin.data-customer');
    Route::get('/admin/data-perangkat', [AdminPanelController::class, 'devices'])->name('admin.data-perangkat');
    Route::get('/admin/data-service', [AdminPanelController::class, 'services'])->name('admin.data-service');
    Route::post('/admin/data-service', [AdminPanelController::class, 'storeService'])->name('admin.data-service.store');
    Route::put('/admin/data-service/{id}', [AdminPanelController::class, 'updateService'])->name('admin.data-service.update');
    Route::patch('/admin/data-service/{id}/toggle', [AdminPanelController::class, 'toggleService'])->name('admin.data-service.toggle');

    // Transaksi dan laporan
    Route::get('/admin/pembayaran', [AdminPanelController::class, 'payments'])->name('admin.payments');
    Route::get('/admin/riwayat-servis', [AdminPanelController::class, 'serviceHistory'])->name('admin.service-history');

    // Proses servis dan pembayaran menggunakan BookingController yang sudah ada.
    Route::post('/admin/booking/update', [BookingController::class, 'updateBookingStatus'])->name('admin.booking.update');
    Route::put('/admin/booking/{id}/verify', [BookingController::class, 'verifyBooking'])->name('admin.booking.verify');
    Route::put('/admin/booking/{id}/mark-paid', [BookingController::class, 'markPaid'])->name('admin.booking.markPaid');
});

/*
|--------------------------------------------------------------------------
| Owner Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:PEMILIK_USAHA'])->group(function () {
    Route::get('/dashboard-pemilik', function () {
        return view('pemilik.dashboard-pemilik');
    })->name('pemilik.dashboard');
});
