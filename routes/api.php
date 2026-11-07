<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ApiBookingController;

/*
|--------------------------------------------------------------------------
| API Routes - FixPoint Service (S2-03)
|--------------------------------------------------------------------------
*/

// Public API
Route::get('/schedules', [ApiBookingController::class, 'checkSchedules']);
Route::post('/bookings', [ApiBookingController::class, 'store']);
Route::get('/bookings/{code}', [ApiBookingController::class, 'show']);

// Admin Protected API (Menggunakan session & role middleware yang sama dengan web)
Route::middleware(['auth', 'role:ADMIN'])->group(function () {
    Route::put('/bookings/{code}/status', [ApiBookingController::class, 'updateStatus']);
});