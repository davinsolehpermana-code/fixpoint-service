
<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome'); // Halaman Informasi Layanan
});

Route::get('/ketersediaan-jadwal', function () {
    return view('ketersediaan-jadwal');
});

Route::get('/booking', function () {
    return view('booking');
});
Route::get('/dashboard-admin', function () {
    return view('dashboard-admin');
});