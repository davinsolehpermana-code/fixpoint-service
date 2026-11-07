use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

public function dashboardPemilik(Request $request)
{
    // Tanggal yang dipilih. Default: hari ini.
    $selectedDate = $request->query('date', now()->toDateString());

    // Statistik berdasarkan tanggal booking
    $stats = [
        'active' => DB::table('bookings as b')
            ->leftJoin('service_records as sr', 'sr.booking_id', '=', 'b.booking_id')
            ->whereDate('b.booking_date', $selectedDate)
            ->where('b.status', 'CONFIRMED')
            ->where('sr.service_status', 'PROCESS')
            ->count(),

        'waiting' => DB::table('bookings')
            ->whereDate('booking_date', $selectedDate)
            ->whereIn('status', ['PENDING', 'WAITING_VERIFICATION'])
            ->count(),

        'completed' => DB::table('bookings')
            ->whereDate('booking_date', $selectedDate)
            ->where('status', 'COMPLETED')
            ->count(),

        'payment_pending' => DB::table('payments as p')
            ->join('bookings as b', 'b.booking_id', '=', 'p.booking_id')
            ->whereDate('b.booking_date', $selectedDate)
            ->where('b.status', 'COMPLETED')
            ->where(function ($query) {
                $query->whereNull('p.payment_status')
                    ->orWhere('p.payment_status', '!=', 'PAID');
            })
            ->where('p.amount', '>', 0)
            ->count(),
    ];

    // Data servis berdasarkan tanggal
    $bookings = DB::table('bookings as b')
        ->leftJoin('customers as c', 'c.customer_id', '=', 'b.customer_id')
        ->leftJoin('devices as d', 'd.device_id', '=', 'b.device_id')
        ->leftJoin('service_records as sr', 'sr.booking_id', '=', 'b.booking_id')
        ->leftJoin('payments as p', 'p.booking_id', '=', 'b.booking_id')
        ->whereDate('b.booking_date', $selectedDate)
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

            'p.payment_status',
            'p.payment_method',
            'p.amount as payment_amount',
        ])
        ->orderByDesc('b.created_at')
        ->get();

    // Jadwal servis berdasarkan tanggal yang dipilih
    $schedules = DB::table('schedules')
        ->whereDate('schedule_date', $selectedDate)
        ->orderBy('start_time')
        ->get();

    return view('pemilik.dashboard-pemilik', compact(
        'stats',
        'bookings',
        'schedules',
        'selectedDate'
    ));
}