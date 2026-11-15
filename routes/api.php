<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ApiBookingController;

/*
|--------------------------------------------------------------------------
| API Routes - FixPoint Service (S2-03)
|--------------------------------------------------------------------------
| Hanya endpoint yang benar-benar memiliki implementasi pada controller yang
| didaftarkan. Aplikasi utama FixPoint Service adalah Laravel Web Monolithic
| (Blade), sehingga dokumentasi lengkap seluruh route ada pada
| docs/04-api/API_DOCUMENTATION.md.
*/

Route::post('/bookings', [ApiBookingController::class, 'store']);