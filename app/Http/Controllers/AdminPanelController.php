<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminPanelController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'need_action' => DB::table('bookings')
                ->whereIn('status', ['PENDING', 'WAITING_VERIFICATION'])
                ->count(),
            'diagnosis' => DB::table('service_records as sr')
                ->join('bookings as b', 'b.booking_id', '=', 'sr.booking_id')
                ->where('b.status', 'CONFIRMED')
                ->where('sr.service_status', 'PENDING')
                ->count(),
            'processing' => DB::table('service_records as sr')
                ->join('bookings as b', 'b.booking_id', '=', 'sr.booking_id')
                ->where('b.status', 'CONFIRMED')
                ->where('sr.service_status', 'PROCESS')
                ->count(),
            'unpaid' => DB::table('payments as p')
                ->join('bookings as b', 'b.booking_id', '=', 'p.booking_id')
                ->where('b.status', 'COMPLETED')
                ->where('p.payment_status', '!=', 'PAID')
                ->where('p.amount', '>', 0)
                ->count(),
        ];

        $recentBookings = DB::table('bookings as b')
            ->leftJoin('customers as c', 'c.customer_id', '=', 'b.customer_id')
            ->leftJoin('devices as d', 'd.device_id', '=', 'b.device_id')
            ->leftJoin('service_records as sr', 'sr.booking_id', '=', 'b.booking_id')
            ->leftJoin('payments as p', 'p.booking_id', '=', 'b.booking_id')
            ->select([
                'b.booking_id', 'b.service_id_code', 'b.status as booking_status', 'b.created_at',
                'c.name as customer_name', 'd.device_type', 'd.brand',
                'sr.service_status', 'p.payment_status', 'p.payment_method', 'p.amount',
            ])
            ->orderByDesc('b.created_at')
            ->limit(7)
            ->get();

        $upcomingSchedules = DB::table('schedules')
            ->whereDate('schedule_date', '>=', now()->toDateString())
            ->orderBy('schedule_date')
            ->orderBy('start_time')
            ->limit(5)
            ->get();

        return view('admin.dashboard-admin', compact('stats', 'recentBookings', 'upcomingSchedules'));
    }

    public function schedules()
    {
        $schedules = DB::table('schedules')
            ->orderByRaw('CASE WHEN schedule_date >= ? THEN 0 ELSE 1 END', [now()->toDateString()])
            ->orderBy('schedule_date')
            ->orderBy('start_time')
            ->paginate(15)
            ->withQueryString();

        return view('admin.jadwal-servis', compact('schedules'));
    }

    public function storeSchedule(Request $request)
    {
        $validated = $request->validate([
            'schedule_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'max_booking' => ['required', 'integer', 'min:1'],
        ]);

        $overlapExists = DB::table('schedules')
            ->whereDate('schedule_date', $validated['schedule_date'])
            ->where('start_time', '<', $validated['end_time'] . ':00')
            ->where('end_time', '>', $validated['start_time'] . ':00')
            ->exists();

        if ($overlapExists) {
            return back()
                ->withInput()
                ->withErrors(['start_time' => 'Jadwal pada waktu tersebut bertabrakan dengan jadwal yang sudah ada.']);
        }

        DB::table('schedules')->insert([
            'schedule_date' => $validated['schedule_date'],
            'start_time' => $validated['start_time'] . ':00',
            'end_time' => $validated['end_time'] . ':00',
            'max_booking' => $validated['max_booking'],
            'current_booking' => 0,
            'status' => 'AVAILABLE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('admin.schedules.index')->with('success', 'Jadwal servis berhasil ditambahkan.');
    }

    public function serviceProcess(Request $request)
    {
        $statusFilter = $request->query('status', 'active');

        $query = DB::table('bookings as b')
            ->leftJoin('customers as c', 'c.customer_id', '=', 'b.customer_id')
            ->leftJoin('devices as d', 'd.device_id', '=', 'b.device_id')
            ->leftJoin('services as s', 's.service_id', '=', 'b.service_id')
            ->leftJoin('service_records as sr', 'sr.booking_id', '=', 'b.booking_id')
            ->leftJoin('payments as p', 'p.booking_id', '=', 'b.booking_id')
            ->select([
                'b.booking_id', 'b.service_id_code', 'b.status as booking_status', 'b.booking_date',
                'c.name as customer_name', 'c.phone_number',
                'd.device_type', 'd.brand', 'd.model', 'd.complaint',
                's.name as service_name',
                'sr.service_record_id', 'sr.diagnosis', 'sr.estimated_cost', 'sr.final_cost',
                'sr.service_status', 'sr.service_notes', 'sr.started_at', 'sr.completed_at',
                'p.payment_method', 'p.payment_status', 'p.amount as payment_amount',
            ]);

        if ($statusFilter === 'waiting') {
            $query->whereIn('b.status', ['PENDING', 'WAITING_VERIFICATION']);
        } elseif ($statusFilter === 'active') {
            $query->whereIn('b.status', ['PENDING', 'WAITING_VERIFICATION', 'CONFIRMED']);
        } elseif ($statusFilter === 'completed') {
            $query->where('b.status', 'COMPLETED');
        } elseif ($statusFilter === 'all') {
            // Tanpa filter status.
        } else {
            $statusFilter = 'active';
            $query->whereIn('b.status', ['PENDING', 'WAITING_VERIFICATION', 'CONFIRMED']);
        }

        $search = trim((string) $request->query('q', ''));
        if ($search !== '') {
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('b.service_id_code', 'like', '%' . $search . '%')
                    ->orWhere('c.name', 'like', '%' . $search . '%')
                    ->orWhere('d.brand', 'like', '%' . $search . '%')
                    ->orWhere('d.model', 'like', '%' . $search . '%');
            });
        }

        $bookings = $query->orderByDesc('b.created_at')->paginate(15)->withQueryString();

        $manageableBookings = DB::table('bookings as b')
            ->join('customers as c', 'c.customer_id', '=', 'b.customer_id')
            ->leftJoin('devices as d', 'd.device_id', '=', 'b.device_id')
            ->leftJoin('service_records as sr', 'sr.booking_id', '=', 'b.booking_id')
            ->leftJoin('payments as p', 'p.booking_id', '=', 'b.booking_id')
            ->where('b.status', 'CONFIRMED')
            ->where(function ($query) {
                $query->whereNull('p.payment_status')->orWhere('p.payment_status', '!=', 'PAID');
            })
            ->select([
                'b.service_id_code', 'c.name as customer_name', 'd.device_type', 'd.brand',
                'sr.service_status', 'sr.diagnosis', 'sr.estimated_cost', 'sr.final_cost', 'sr.service_notes',
                'p.payment_method',
            ])
            ->orderByDesc('b.created_at')
            ->get();

        return view('admin.kelola-proses-servis', compact('bookings', 'manageableBookings', 'statusFilter', 'search'));
    }

    public function customers(Request $request)
    {
        $search = trim((string) $request->query('q', ''));

        $customers = DB::table('customers as c')
            ->select([
                'c.customer_id', 'c.name', 'c.phone_number', 'c.email', 'c.address',
            ])
            ->selectSub(function ($query) {
                $query->from('devices as d')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('d.customer_id', 'c.customer_id');
            }, 'device_count')
            ->selectSub(function ($query) {
                $query->from('bookings as b')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('b.customer_id', 'c.customer_id');
            }, 'booking_count')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('c.name', 'like', '%' . $search . '%')
                        ->orWhere('c.phone_number', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('c.name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.data-customer', compact('customers', 'search'));
    }

    public function devices(Request $request)
    {
        $search = trim((string) $request->query('q', ''));

        $devices = DB::table('devices as d')
            ->join('customers as c', 'c.customer_id', '=', 'd.customer_id')
            ->select([
                'd.device_id', 'd.device_type', 'd.brand', 'd.model', 'd.complaint',
                'c.name as customer_name', 'c.phone_number',
            ])
            ->selectSub(function ($query) {
                $query->from('bookings as b')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('b.device_id', 'd.device_id');
            }, 'booking_count')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('c.name', 'like', '%' . $search . '%')
                        ->orWhere('d.brand', 'like', '%' . $search . '%')
                        ->orWhere('d.model', 'like', '%' . $search . '%')
                        ->orWhere('d.device_type', 'like', '%' . $search . '%');
                });
            })
            ->orderByDesc('d.device_id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.data-perangkat', compact('devices', 'search'));
    }

    public function services(Request $request)
    {
        $search = trim((string) $request->query('q', ''));

        $services = DB::table('services')
            ->when($search !== '', function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.data-service', compact('services', 'search'));
    }

    public function storeService(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'estimated_price' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
        ]);

        DB::table('services')->insert([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'estimated_price' => $validated['estimated_price'] ?? null,
            'is_active' => $request->boolean('is_active'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('admin.data-service')->with('success', 'Layanan berhasil ditambahkan.');
    }

    public function updateService(Request $request, int $id)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'estimated_price' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
        ]);

        $updated = DB::table('services')->where('service_id', $id)->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'estimated_price' => $validated['estimated_price'] ?? null,
            'is_active' => $request->boolean('is_active'),
            'updated_at' => now(),
        ]);

        if (!$updated && !DB::table('services')->where('service_id', $id)->exists()) {
            abort(404);
        }

        return redirect()->route('admin.data-service')->with('success', 'Data layanan berhasil diperbarui.');
    }

    public function toggleService(int $id)
    {
        $service = DB::table('services')->where('service_id', $id)->first();
        abort_unless($service, 404);

        DB::table('services')->where('service_id', $id)->update([
            'is_active' => !$service->is_active,
            'updated_at' => now(),
        ]);

        return redirect()->route('admin.data-service')->with('success', 'Status layanan berhasil diperbarui.');
    }

    public function payments(Request $request)
    {
        $status = $request->query('status', 'all');
        $search = trim((string) $request->query('q', ''));

        $payments = DB::table('payments as p')
            ->join('bookings as b', 'b.booking_id', '=', 'p.booking_id')
            ->leftJoin('customers as c', 'c.customer_id', '=', 'p.customer_id')
            ->leftJoin('devices as d', 'd.device_id', '=', 'b.device_id')
            ->select([
                'p.payment_id', 'p.booking_id', 'p.amount', 'p.payment_method', 'p.payment_status', 'p.payment_date',
                'b.service_id_code', 'b.status as booking_status',
                'c.name as customer_name', 'd.brand', 'd.device_type',
            ])
            ->when($status === 'paid', fn ($query) => $query->where('p.payment_status', 'PAID'))
            ->when($status === 'unpaid', fn ($query) => $query->where('p.payment_status', '!=', 'PAID'))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('b.service_id_code', 'like', '%' . $search . '%')
                        ->orWhere('c.name', 'like', '%' . $search . '%');
                });
            })
            ->orderByDesc('p.payment_id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.pembayaran', compact('payments', 'status', 'search'));
    }

    public function serviceHistory(Request $request)
    {
        $search = trim((string) $request->query('q', ''));

        $records = DB::table('service_records as sr')
            ->join('bookings as b', 'b.booking_id', '=', 'sr.booking_id')
            ->leftJoin('customers as c', 'c.customer_id', '=', 'b.customer_id')
            ->leftJoin('devices as d', 'd.device_id', '=', 'b.device_id')
            ->leftJoin('services as s', 's.service_id', '=', 'b.service_id')
            ->leftJoin('payments as p', 'p.booking_id', '=', 'b.booking_id')
            ->where('sr.service_status', 'COMPLETED')
            ->select([
                'sr.service_record_id', 'sr.diagnosis', 'sr.estimated_cost', 'sr.final_cost', 'sr.service_notes', 'sr.started_at', 'sr.completed_at',
                'b.service_id_code', 'b.booking_date',
                'c.name as customer_name', 'd.device_type', 'd.brand', 'd.model', 's.name as service_name',
                'p.payment_method', 'p.payment_status',
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('b.service_id_code', 'like', '%' . $search . '%')
                        ->orWhere('c.name', 'like', '%' . $search . '%')
                        ->orWhere('d.brand', 'like', '%' . $search . '%');
                });
            })
            ->orderByDesc('sr.completed_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.riwayat-servis', compact('records', 'search'));
    }
}
