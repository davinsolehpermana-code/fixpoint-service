<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\StatusController;
use App\Http\Controllers\AdminPanelController;
use App\Http\Controllers\PaymentProofController;
use App\Http\Controllers\ReportController;
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
    // Semua jadwal yang belum lewat tetap ditampilkan, termasuk yang penuh atau ditutup,
    // supaya pelanggan melihat status ketersediaannya (AVAILABLE / FULL / CLOSED).
    $schedules = Schedule::query()
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
Route::get('/cek-status/{code}/bukti-pembayaran', [PaymentProofController::class, 'showForCustomer'])->name('cek-status.proof');

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

    // Bukti pembayaran QRIS: hanya Admin yang dapat membuka dan meminta upload ulang.
    Route::get('/admin/pembayaran/{paymentId}/bukti', [PaymentProofController::class, 'show'])->name('admin.payments.proof');
    Route::post('/admin/pembayaran/{paymentId}/minta-upload-ulang', [PaymentProofController::class, 'requestReupload'])->name('admin.payments.request-reupload');

    // Struk resmi hanya dicetak Admin dari panel pembayaran.
    Route::get('/admin/pembayaran/{paymentId}/struk', [AdminPanelController::class, 'paymentReceipt'])->name('admin.payments.receipt');

    // Operasional
    Route::get('/admin/jadwal-servis', [AdminPanelController::class, 'schedules'])->name('admin.schedules.index');
    Route::post('/admin/jadwal-servis', [AdminPanelController::class, 'storeSchedule'])->name('admin.schedules.store');
    Route::patch('/admin/jadwal-servis/{scheduleId}/status', [AdminPanelController::class, 'updateScheduleStatus'])->name('admin.schedules.status');
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

    // Riwayat servis dengan filter rentang tanggal, lengkap dengan unduhan PDF/Excel.
    Route::get('/admin/riwayat-servis', [ReportController::class, 'serviceHistory'])->name('admin.service-history');
    Route::get('/admin/riwayat-servis/pdf', [ReportController::class, 'serviceHistoryPdf'])->name('admin.service-history.pdf');
    Route::get('/admin/riwayat-servis/excel', [ReportController::class, 'serviceHistoryExcel'])->name('admin.service-history.excel');

    // Proses servis dan pembayaran menggunakan BookingController yang sudah ada.
    Route::post('/admin/booking/update', [BookingController::class, 'updateBookingStatus'])->name('admin.booking.update');
    Route::put('/admin/booking/{id}/verify', [BookingController::class, 'verifyBooking'])->name('admin.booking.verify');
    Route::post('/admin/booking/{id}/cancel', [AdminPanelController::class, 'cancelBooking'])->name('admin.booking.cancel');
    Route::put('/admin/booking/{id}/mark-paid', [AdminPanelController::class, 'markPaid'])->name('admin.booking.markPaid');
});

/*
|--------------------------------------------------------------------------
| Owner Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:PEMILIK_USAHA'])->group(function () {

    // Pemilik hanya dapat melihat bukti QRIS (read-only).
    Route::get('/pemilik/pembayaran/{paymentId}/bukti', [PaymentProofController::class, 'show'])->name('pemilik.payments.proof');

    // Laporan read-only untuk Pemilik Usaha (halaman, PDF, dan Excel).
    Route::get('/pemilik/riwayat-servis', [ReportController::class, 'serviceHistory'])->name('pemilik.service-history');
    Route::get('/pemilik/riwayat-servis/pdf', [ReportController::class, 'serviceHistoryPdf'])->name('pemilik.service-history.pdf');
    Route::get('/pemilik/riwayat-servis/excel', [ReportController::class, 'serviceHistoryExcel'])->name('pemilik.service-history.excel');

    Route::get('/pemilik/laporan-pemasukan', [ReportController::class, 'income'])->name('pemilik.income');
    Route::get('/pemilik/laporan-pemasukan/pdf', [ReportController::class, 'incomePdf'])->name('pemilik.income.pdf');
    Route::get('/pemilik/laporan-pemasukan/excel', [ReportController::class, 'incomeExcel'])->name('pemilik.income.excel');

    Route::get('/dashboard-pemilik', function (Request $request) {

        // Tanggal yang dipilih untuk riwayat servis
        $selectedDate = $request->query('date');

        /*
        |--------------------------------------------------------------------------
        | Statistik Dashboard
        |--------------------------------------------------------------------------
        */
        $stats = [
            'active' => DB::table('service_records')
                ->where('service_status', 'PROCESS')
                ->count(),

            'waiting' => DB::table('bookings')
                ->whereIn('status', [
                    'PENDING',
                    'WAITING_VERIFICATION',
                    'CONFIRMED'
                ])
                ->count(),

            'completed' => DB::table('bookings')
                ->where('status', 'COMPLETED')
                ->count(),

            'payment_pending' => DB::table('payments as p')
                ->join('bookings as b', 'b.booking_id', '=', 'p.booking_id')
                ->where('b.status', 'COMPLETED')
                ->where(function ($query) {
                    $query->whereNull('p.payment_status')
                        ->orWhere('p.payment_status', '!=', 'PAID');
                })
                ->where('p.amount', '>', 0)
                ->count(),
        ];

        /*
        |--------------------------------------------------------------------------
        | Monitoring Servis
        |--------------------------------------------------------------------------
        */
        $paymentProofs = DB::table('payments as p')
            ->join('bookings as b', 'b.booking_id', '=', 'p.booking_id')
            ->leftJoin('customers as c', 'c.customer_id', '=', 'b.customer_id')
            ->leftJoin('devices as d', 'd.device_id', '=', 'b.device_id')
            ->where('p.payment_method', 'QRIS')
            ->whereNotNull('p.payment_proof')
            ->where('p.payment_proof', '<>', '')
            ->when($selectedDate, function ($query) use ($selectedDate) {
                $query->whereDate('b.booking_date', $selectedDate);
            })
            ->select([
                'p.payment_id',
                'p.amount',
                'p.payment_status',
                'p.payment_proof',
                'b.service_id_code',
                'c.name as customer_name',
                'd.brand',
                'd.device_type',
            ])
            ->orderByDesc('p.payment_date')
            ->limit(8)
            ->get();

        $bookings = DB::table('bookings as b')
            ->leftJoin('customers as c', 'c.customer_id', '=', 'b.customer_id')
            ->leftJoin('devices as d', 'd.device_id', '=', 'b.device_id')
            ->leftJoin('service_records as sr', 'sr.booking_id', '=', 'b.booking_id')
            ->leftJoin('payments as p', 'p.booking_id', '=', 'b.booking_id')
            ->select([
                'b.booking_id',
                'b.service_id_code',
                'b.status as booking_status',
                'b.booking_date',
                'b.created_at',

                'c.name as customer_name',

                'd.device_type',
                'd.brand',
                'd.model',

                'sr.service_status',
                'sr.diagnosis',
                'sr.estimated_cost',
                'sr.final_cost',
                'sr.completed_at',

                'p.payment_status',
                'p.payment_method',
                'p.amount',
            ])
            ->orderByDesc('b.created_at')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Riwayat Servis Berdasarkan Tanggal
        |--------------------------------------------------------------------------
        */
        $historyQuery = DB::table('service_records as sr')
            ->join('bookings as b', 'b.booking_id', '=', 'sr.booking_id')
            ->leftJoin('customers as c', 'c.customer_id', '=', 'b.customer_id')
            ->leftJoin('devices as d', 'd.device_id', '=', 'b.device_id')
            ->leftJoin('payments as p', 'p.booking_id', '=', 'b.booking_id')
            ->where('sr.service_status', 'COMPLETED')
            ->select([
                'b.service_id_code',
                'c.name as customer_name',
                'd.device_type',
                'd.brand',
                'd.model',
                'sr.diagnosis',
                'sr.final_cost',
                'sr.completed_at',
                'p.payment_status',
                'p.amount',
            ]);

        // Jika tanggal dipilih, filter riwayat berdasarkan tanggal selesai servis
        if ($selectedDate) {
            $historyQuery->whereDate('sr.completed_at', $selectedDate);
        }

        $serviceHistory = $historyQuery
            ->orderByDesc('sr.completed_at')
            ->limit(10)
            ->get();

        return view('pemilik.dashboard-pemilik', compact(
            'stats',
            'bookings',
            'serviceHistory',
            'selectedDate',
            'paymentProofs'
        ));
    })->name('pemilik.dashboard');

});