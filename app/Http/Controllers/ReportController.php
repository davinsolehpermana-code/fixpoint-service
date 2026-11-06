<?php

namespace App\Http\Controllers;

use App\Support\SimpleXlsx;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Laporan Admin & Pemilik Usaha: riwayat servis (dengan rentang tanggal)
 * dan laporan pemasukan. Setiap laporan tersedia sebagai halaman, PDF
 * siap cetak, dan berkas Excel (.xlsx).
 */
class ReportController extends Controller
{
    public function serviceHistory(Request $request)
    {
        [$from, $to] = $this->dateRange($request);
        $search = trim((string) $request->query('q', ''));

        $query = $this->serviceHistoryQuery($from, $to, $search);

        $records = (clone $query)
            ->orderByDesc('sr.completed_at')
            ->paginate(15)
            ->withQueryString();

        $summaryQuery = $this->serviceHistoryQuery($from, $to, $search);

        $summary = [
            'total_servis' => $summaryQuery->count(),
            'total_biaya' => (float) (clone $summaryQuery)->sum('sr.final_cost'),
        ];

        return view('admin.riwayat-servis', [
            'records' => $records,
            'summary' => $summary,
            'search' => $search,
            'from' => $from,
            'to' => $to,
            'periodLabel' => $this->periodLabel($from, $to),
            'routePrefix' => $this->routePrefix(),
        ]);
    }

    public function serviceHistoryPdf(Request $request): Response
    {
        [$from, $to] = $this->dateRange($request);
        $search = trim((string) $request->query('q', ''));

        $records = $this->serviceHistoryQuery($from, $to, $search)
            ->orderBy('sr.completed_at')
            ->get();

        return $this->pdf(
            'admin.exports.riwayat-servis-pdf',
            [
                'records' => $records,
                'from' => $from,
                'to' => $to,
                'search' => $search,
                'periodLabel' => $this->periodLabel($from, $to),
                'totalBiaya' => (float) $records->sum('final_cost'),
                'printedAt' => Carbon::now(),
            ],
            'laporan-riwayat-servis-' . $this->fileSlug($from, $to) . '.pdf',
            $request
        );
    }

    public function serviceHistoryExcel(Request $request): Response
    {
        [$from, $to] = $this->dateRange($request);
        $search = trim((string) $request->query('q', ''));

        $records = $this->serviceHistoryQuery($from, $to, $search)
            ->orderBy('sr.completed_at')
            ->get();

        $rows = $records->map(fn ($record) => [
            $record->completed_at ? Carbon::parse($record->completed_at)->format('d/m/Y H:i') : '-',
            $record->service_id_code,
            $record->customer_name ?? '-',
            $record->phone_number ?? '-',
            trim(($record->device_type ?? '') . ' ' . ($record->brand ?? '') . ' ' . ($record->model ?? '')) ?: '-',
            $record->service_name ?? '-',
            $record->complaint ?? '-',
            $record->booking_date ? Carbon::parse($record->booking_date)->format('d/m/Y') : '-',
            'Selesai',
            $record->diagnosis ?? '-',
            $record->estimated_cost !== null ? (float) $record->estimated_cost : '-',
            (float) ($record->final_cost ?? 0),
            $record->payment_method ?? '-',
            $record->payment_status === 'PAID' ? 'Lunas' : 'Belum Bayar',
        ])->all();

        $rows[] = ['', '', '', '', '', '', '', '', '', 'TOTAL', '', (float) $records->sum('final_cost'), '', ''];

        return SimpleXlsx::download(
            'laporan-riwayat-servis-' . $this->fileSlug($from, $to) . '.xlsx',
            'Riwayat Servis',
            [
                'Tanggal Selesai',
                'Service ID',
                'Pelanggan',
                'No. Telepon',
                'Perangkat',
                'Layanan',
                'Keluhan Awal',
                'Tanggal Booking',
                'Status Servis',
                'Diagnosis',
                'Estimasi (Rp)',
                'Biaya Akhir (Rp)',
                'Metode Bayar',
                'Status Bayar',
            ],
            $rows
        );
    }

    public function income(Request $request)
    {
        [$from, $to] = $this->dateRange($request);
        $method = $this->paymentMethod($request);

        $payments = $this->incomeQuery($from, $to, $method)
            ->orderByDesc('p.payment_date')
            ->orderByDesc('p.payment_id')
            ->paginate(15)
            ->withQueryString();

        $summary = $this->incomeSummary($from, $to, $method);

        return view('admin.laporan-pemasukan', [
            'payments' => $payments,
            'summary' => $summary,
            'method' => $method,
            'from' => $from,
            'to' => $to,
            'periodLabel' => $this->periodLabel($from, $to),
            'routePrefix' => $this->routePrefix(),
        ]);
    }

    public function incomePdf(Request $request): Response
    {
        [$from, $to] = $this->dateRange($request);
        $method = $this->paymentMethod($request);

        $payments = $this->incomeQuery($from, $to, $method)
            ->orderBy('p.payment_date')
            ->orderBy('p.payment_id')
            ->get();

        return $this->pdf(
            'admin.exports.laporan-pemasukan-pdf',
            [
                'payments' => $payments,
                'summary' => $this->incomeSummary($from, $to, $method),
                'method' => $method,
                'periodLabel' => $this->periodLabel($from, $to),
                'printedAt' => Carbon::now(),
            ],
            'laporan-pemasukan-' . $this->fileSlug($from, $to) . '.pdf',
            $request
        );
    }

    public function incomeExcel(Request $request): Response
    {
        [$from, $to] = $this->dateRange($request);
        $method = $this->paymentMethod($request);
        $summary = $this->incomeSummary($from, $to, $method);

        $payments = $this->incomeQuery($from, $to, $method)
            ->orderBy('p.payment_date')
            ->orderBy('p.payment_id')
            ->get();

        $rows = $payments->map(fn ($payment) => [
            $payment->payment_date ? Carbon::parse($payment->payment_date)->format('d/m/Y H:i') : '-',
            $payment->service_id_code,
            $payment->customer_name ?? '-',
            trim(($payment->device_type ?? '') . ' ' . ($payment->brand ?? '')) ?: '-',
            $payment->payment_method,
            (float) $payment->amount,
            $payment->payment_status === 'PAID' ? 'Lunas' : $payment->payment_status,
            $payment->processed_by_email ?? 'Sistem',
        ])->all();

        $rows[] = ['', '', '', '', 'TOTAL PEMASUKAN', (float) $summary->total_pemasukan, '', ''];
        $rows[] = ['', '', '', '', 'TUNAI (CASH)', (float) $summary->total_cash, '', ''];
        $rows[] = ['', '', '', '', 'QRIS', (float) $summary->total_qris, '', ''];
        $rows[] = ['', '', '', '', 'JUMLAH TRANSAKSI', (int) $summary->total_transaksi, '', ''];

        return SimpleXlsx::download(
            'laporan-pemasukan-' . $this->fileSlug($from, $to) . '.xlsx',
            'Laporan Pemasukan',
            ['Tanggal Bayar', 'Service ID', 'Pelanggan', 'Perangkat', 'Metode', 'Jumlah (Rp)', 'Status', 'Diproses Oleh'],
            $rows
        );
    }

    private function serviceHistoryQuery(?string $from, ?string $to, string $search): Builder
    {
        return DB::table('service_records as sr')
            ->join('bookings as b', 'b.booking_id', '=', 'sr.booking_id')
            ->leftJoin('customers as c', 'c.customer_id', '=', 'b.customer_id')
            ->leftJoin('devices as d', 'd.device_id', '=', 'b.device_id')
            ->leftJoin('services as s', 's.service_id', '=', 'b.service_id')
            ->leftJoin('payments as p', 'p.booking_id', '=', 'b.booking_id')
            ->where('sr.service_status', 'COMPLETED')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('b.service_id_code', 'like', '%' . $search . '%')
                        ->orWhere('c.name', 'like', '%' . $search . '%')
                        ->orWhere('d.brand', 'like', '%' . $search . '%');
                });
            })
            ->when($from !== null, fn ($query) => $query->whereDate('sr.completed_at', '>=', $from))
            ->when($to !== null, fn ($query) => $query->whereDate('sr.completed_at', '<=', $to))
            ->select([
                'sr.service_record_id',
                'sr.diagnosis',
                'sr.estimated_cost',
                'sr.final_cost',
                'sr.service_notes',
                'sr.started_at',
                'sr.completed_at',
                'b.service_id_code',
                'b.booking_date',
                'd.complaint',
                'c.name as customer_name',
                'c.phone_number',
                'd.device_type',
                'd.brand',
                'd.model',
                's.name as service_name',
                'p.payment_method',
                'p.payment_status',
            ]);
    }

    private function incomeQuery(?string $from, ?string $to, ?string $method): Builder
    {
        return DB::table('payments as p')
            ->join('bookings as b', 'b.booking_id', '=', 'p.booking_id')
            ->leftJoin('customers as c', 'c.customer_id', '=', 'b.customer_id')
            ->leftJoin('devices as d', 'd.device_id', '=', 'b.device_id')
            ->leftJoin('users as u', 'u.user_id', '=', 'p.processed_by')
            ->where('p.payment_status', 'PAID')
            ->when($from !== null, fn ($query) => $query->whereDate('p.payment_date', '>=', $from))
            ->when($to !== null, fn ($query) => $query->whereDate('p.payment_date', '<=', $to))
            ->when($method !== null, fn ($query) => $query->where('p.payment_method', $method))
            ->select([
                'p.payment_id',
                'p.amount',
                'p.payment_method',
                'p.payment_status',
                'p.payment_date',
                'p.cash_received',
                'b.service_id_code',
                'c.name as customer_name',
                'd.device_type',
                'd.brand',
                'u.email as processed_by_email',
            ]);
    }

    private function incomeSummary(?string $from, ?string $to, ?string $method): object
    {
        return DB::table('payments as p')
            ->where('p.payment_status', 'PAID')
            ->when($from !== null, fn ($query) => $query->whereDate('p.payment_date', '>=', $from))
            ->when($to !== null, fn ($query) => $query->whereDate('p.payment_date', '<=', $to))
            ->when($method !== null, fn ($query) => $query->where('p.payment_method', $method))
            ->selectRaw('COUNT(*) as total_transaksi')
            ->selectRaw('COALESCE(SUM(p.amount), 0) as total_pemasukan')
            ->selectRaw("COALESCE(SUM(CASE WHEN p.payment_method = 'CASH' THEN p.amount ELSE 0 END), 0) as total_cash")
            ->selectRaw("COALESCE(SUM(CASE WHEN p.payment_method = 'QRIS' THEN p.amount ELSE 0 END), 0) as total_qris")
            ->selectRaw("COALESCE(SUM(CASE WHEN p.payment_method = 'CASH' THEN 1 ELSE 0 END), 0) as count_cash")
            ->selectRaw("COALESCE(SUM(CASE WHEN p.payment_method = 'QRIS' THEN 1 ELSE 0 END), 0) as count_qris")
            ->first();
    }

    /**
     * PDF diunduh secara default; dengan ?print=1 berkas ditampilkan inline
     * agar bisa langsung dicetak dari browser.
     */
    private function pdf(string $view, array $data, string $filename, Request $request): Response
    {
        $pdf = Pdf::loadView($view, $data)->setPaper('a4', 'landscape');

        return $request->boolean('print')
            ? $pdf->stream($filename)
            : $pdf->download($filename);
    }

    /**
     * Admin dan Pemilik Usaha memakai halaman yang sama, hanya prefix route
     * yang berbeda agar tautan tetap mengarah ke route yang boleh diakses.
     */
    private function routePrefix(): string
    {
        return strtoupper((string) (auth()->user()->role ?? '')) === 'PEMILIK_USAHA'
            ? 'pemilik'
            : 'admin';
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function dateRange(Request $request): array
    {
        $from = $this->normalizeDate($request->query('from'));
        $to = $this->normalizeDate($request->query('to'));

        if ($from !== null && $to !== null && $from > $to) {
            [$from, $to] = [$to, $from];
        }

        return [$from, $to];
    }

    private function normalizeDate(mixed $value): ?string
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Exception) {
            return null;
        }
    }

    private function paymentMethod(Request $request): ?string
    {
        $method = strtoupper((string) $request->query('method', ''));

        return in_array($method, ['CASH', 'QRIS'], true) ? $method : null;
    }

    private function periodLabel(?string $from, ?string $to): string
    {
        $format = fn (string $date) => Carbon::parse($date)->locale('id')->translatedFormat('d F Y');

        if ($from !== null && $to !== null) {
            return $format($from) . ' s.d. ' . $format($to);
        }

        if ($from !== null) {
            return 'Sejak ' . $format($from);
        }

        if ($to !== null) {
            return 'Sampai ' . $format($to);
        }

        return 'Semua data';
    }

    private function fileSlug(?string $from, ?string $to): string
    {
        $format = fn (?string $date) => $date === null
            ? 'semua'
            : strtolower(Carbon::parse($date)->locale('id')->translatedFormat('d-F-Y'));

        return $format($from) . '-' . $format($to);
    }
}
