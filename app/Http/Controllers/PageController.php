<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Schedule; // Pastikan import ini ada

class PageController extends Controller
{
    public function index()
    {
        // Ambil 3 jadwal terdekat yang statusnya AVAILABLE, diurutkan dari tanggal paling dekat
        $schedules = Schedule::where('status', 'AVAILABLE')
            ->where('current_booking', '<', \DB::raw('max_booking')) // Pastikan slot masih ada
            ->orderBy('schedule_date', 'asc')
            ->limit(3)
            ->get();

        return view('welcome.welcome', compact('schedules')); // Kirim data ke view
    }
}