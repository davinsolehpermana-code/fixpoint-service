@extends('layouts.admin')

@section('title', 'Riwayat Servis')

@section('admin-content')
<div class="admin-page-header">
    <div>
        <div class="admin-eyebrow">Laporan</div>
        <h1 class="admin-page-title">Riwayat Servis</h1>
        <p class="admin-page-subtitle">Lihat catatan servis yang telah selesai, mulai dari diagnosis hingga biaya akhir.</p>
    </div>
</div>

<section class="admin-card">
    <div class="admin-toolbar">
        <form action="{{ route('admin.service-history') }}" method="GET" class="admin-search">
            <input type="text" name="q" class="admin-input" value="{{ $search }}" placeholder="Cari Service ID, pelanggan, atau perangkat...">
            <button type="submit" class="admin-btn admin-btn-primary">Cari</button>
        </form>
        <span class="admin-badge badge-green">{{ $records->total() }} servis selesai</span>
    </div>

    <div style="display:grid;gap:11px;">
        @forelse($records as $record)
            <details style="border:1px solid #e5e7eb;border-radius:11px;padding:14px 15px;background:#fff;">
                <summary style="cursor:pointer;display:flex;align-items:center;justify-content:space-between;gap:14px;list-style:none;">
                    <div style="min-width:0;">
                        <div class="admin-id">{{ $record->service_id_code }}</div>
                        <div class="admin-primary-text" style="margin-top:5px;">{{ $record->customer_name ?? '-' }} · {{ trim(($record->device_type ?? '') . ' ' . ($record->brand ?? '')) ?: '-' }}</div>
                        <div class="admin-muted" style="margin-top:4px;">{{ $record->service_name ?? 'Layanan' }} · Selesai {{ $record->completed_at ? \Carbon\Carbon::parse($record->completed_at)->locale('id')->translatedFormat('d F Y, H:i') : '—' }}</div>
                    </div>
                    <div style="text-align:right;flex-shrink:0;">
                        <span class="admin-badge badge-green">Selesai</span>
                        <div class="admin-primary-text" style="margin-top:6px;">Rp {{ number_format((float) ($record->final_cost ?? 0), 0, ',', '.') }}</div>
                    </div>
                </summary>
                <div style="border-top:1px solid #eef2f7;margin-top:13px;padding-top:13px;">
                    <div class="admin-detail-list">
                        <div class="admin-detail-item"><div class="admin-detail-label">Diagnosis</div><div class="admin-detail-value">{{ $record->diagnosis ?: 'Tidak ada catatan diagnosis' }}</div></div>
                        <div class="admin-detail-item"><div class="admin-detail-label">Estimasi Biaya</div><div class="admin-detail-value">{{ $record->estimated_cost !== null ? 'Rp ' . number_format((float) $record->estimated_cost, 0, ',', '.') : '—' }}</div></div>
                        <div class="admin-detail-item"><div class="admin-detail-label">Biaya Akhir</div><div class="admin-detail-value">Rp {{ number_format((float) ($record->final_cost ?? 0), 0, ',', '.') }}</div></div>
                        <div class="admin-detail-item"><div class="admin-detail-label">Pembayaran</div><div class="admin-detail-value">{{ $record->payment_method ?? '—' }} · {{ $record->payment_status === 'PAID' ? 'Lunas' : 'Belum Bayar' }}</div></div>
                    </div>
                    @if($record->service_notes)
                        <div style="margin-top:12px;"><div class="admin-detail-label">Estimasi dan Catatan Pengerjaan</div><div style="font-size:12px;color:#475569;white-space:pre-line;margin-top:5px;">{{ $record->service_notes }}</div></div>
                    @endif
                    <div class="admin-muted" style="margin-top:10px;">Mulai pengerjaan: {{ $record->started_at ? \Carbon\Carbon::parse($record->started_at)->locale('id')->translatedFormat('d F Y, H:i') : '—' }}</div>
                </div>
            </details>
        @empty
            <div class="admin-empty"><strong>Belum ada riwayat servis</strong>Servis yang berstatus selesai akan tampil di halaman ini.</div>
        @endforelse
    </div>
    <div class="admin-pagination">{{ $records->links() }}</div>
</section>
@endsection
