@extends('layouts.admin')

@section('title', 'Laporan Pemasukan')

@section('admin-content')
@php
    $exportQuery = array_filter([
        'from' => $from,
        'to' => $to,
        'method' => $method,
    ], fn ($value) => $value !== null && $value !== '');
    $methodLabel = $method === 'CASH' ? 'Tunai (Cash)' : ($method === 'QRIS' ? 'QRIS' : 'Semua metode');
@endphp
<div class="admin-page-header">
    <div>
        <div class="admin-eyebrow">Laporan</div>
        <h1 class="admin-page-title">Laporan Pemasukan</h1>
        <p class="admin-page-subtitle">Pemasukan dihitung dari pembayaran berstatus <strong>Lunas (PAID)</strong> pada tabel pembayaran.</p>
    </div>
    <div class="admin-inline-actions">
        <a href="{{ route($routePrefix . '.income.pdf', $exportQuery + ['print' => 1]) }}" target="_blank" rel="noopener" class="admin-btn admin-btn-soft">Cetak</a>
        <a href="{{ route($routePrefix . '.income.pdf', $exportQuery) }}" class="admin-btn admin-btn-primary">Download PDF</a>
        <a href="{{ route($routePrefix . '.income.excel', $exportQuery) }}" class="admin-btn admin-btn-success">Export Excel</a>
    </div>
</div>

<section class="admin-card">
    <form action="{{ route($routePrefix . '.income') }}" method="GET" class="admin-filter" style="display:grid;gap:12px;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));align-items:end;">
        <div class="admin-form-group" style="margin:0;">
            <label class="admin-label" for="from">Tanggal Mulai</label>
            <input type="date" id="from" name="from" class="admin-input" value="{{ $from }}">
        </div>
        <div class="admin-form-group" style="margin:0;">
            <label class="admin-label" for="to">Tanggal Akhir</label>
            <input type="date" id="to" name="to" class="admin-input" value="{{ $to }}">
        </div>
        <div class="admin-form-group" style="margin:0;">
            <label class="admin-label" for="method">Metode Pembayaran</label>
            <select id="method" name="method" class="admin-select">
                <option value="" @selected($method === null)>Semua Metode</option>
                <option value="CASH" @selected($method === 'CASH')>Cash</option>
                <option value="QRIS" @selected($method === 'QRIS')>QRIS</option>
            </select>
        </div>
        <button type="submit" class="admin-btn admin-btn-primary">Terapkan Filter</button>
        <a href="{{ route($routePrefix . '.income') }}" class="admin-btn admin-btn-outline">Reset Filter</a>
    </form>

    <div class="admin-muted" style="margin-top:14px;">Periode: <strong>{{ $periodLabel }}</strong> · {{ $methodLabel }}</div>
</section>

<section class="admin-stats" style="margin-top:16px;">
    <div class="admin-stat">
        <div class="admin-stat-top"><span class="admin-stat-icon">Rp</span></div>
        <div class="admin-stat-value">Rp {{ number_format((float) $summary->total_pemasukan, 0, ',', '.') }}</div>
        <div class="admin-stat-note">Total pemasukan</div>
    </div>
    <div class="admin-stat">
        <div class="admin-stat-top"><span class="admin-stat-icon">QR</span></div>
        <div class="admin-stat-value">Rp {{ number_format((float) $summary->total_qris, 0, ',', '.') }}</div>
        <div class="admin-stat-note">QRIS · {{ (int) $summary->count_qris }} transaksi</div>
    </div>
    <div class="admin-stat">
        <div class="admin-stat-top"><span class="admin-stat-icon">CS</span></div>
        <div class="admin-stat-value">Rp {{ number_format((float) $summary->total_cash, 0, ',', '.') }}</div>
        <div class="admin-stat-note">Cash · {{ (int) $summary->count_cash }} transaksi</div>
    </div>
    <div class="admin-stat">
        <div class="admin-stat-top"><span class="admin-stat-icon">✓</span></div>
        <div class="admin-stat-value">{{ (int) $summary->total_transaksi }}</div>
        <div class="admin-stat-note">Transaksi berhasil</div>
    </div>
</section>

<section class="admin-card" style="margin-top:16px;">
    <div class="admin-card-header">
        <div>
            <h2 class="admin-card-title">Rincian Transaksi</h2>
            <p class="admin-card-description">Hanya pembayaran lunas yang dihitung sebagai pemasukan.</p>
        </div>
    </div>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tanggal Pembayaran</th>
                    <th>Service ID</th>
                    <th>Pelanggan</th>
                    <th>Perangkat</th>
                    <th>Metode</th>
                    <th>Jumlah</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                    <tr>
                        <td>{{ $payments->firstItem() + $loop->index }}</td>
                        <td>{{ $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->locale('id')->translatedFormat('d F Y, H:i') : '—' }}</td>
                        <td><span class="admin-id">{{ $payment->service_id_code }}</span></td>
                        <td>{{ $payment->customer_name ?? '-' }}</td>
                        <td>{{ trim(($payment->device_type ?? '') . ' ' . ($payment->brand ?? '')) ?: '-' }}</td>
                        <td><span class="admin-badge {{ $payment->payment_method === 'QRIS' ? 'badge-blue' : 'badge-gray' }}">{{ $payment->payment_method }}</span></td>
                        <td>Rp {{ number_format((float) $payment->amount, 0, ',', '.') }}</td>
                        <td><span class="admin-badge badge-green">Lunas</span></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">
                            <div class="admin-empty"><strong>Tidak ada pemasukan pada periode ini</strong>Ubah rentang tanggal atau metode pembayaran, lalu terapkan filter kembali.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="admin-pagination">{{ $payments->links() }}</div>
</section>
@endsection
