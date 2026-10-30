@extends('layouts.admin')

@section('title', 'Dashboard Admin')

@section('admin-content')
<div class="admin-page-header">
    <div>
        <div class="admin-eyebrow">FixPoint Service</div>
        <h1 class="admin-page-title">Dashboard Admin</h1>
        <p class="admin-page-subtitle">Pantau kondisi operasional, antrean servis, jadwal mendatang, dan pembayaran yang perlu dikonfirmasi.</p>
    </div>
    <div class="admin-inline-actions">
        <a href="{{ route('admin.schedules.index') }}" class="admin-btn admin-btn-outline">＋ Tambah Jadwal</a>
        <a href="{{ route('admin.service-process') }}" class="admin-btn admin-btn-primary">Kelola Servis</a>
    </div>
</div>

<div class="admin-stats">
    <div class="admin-stat">
        <div class="admin-stat-top"><span>Perlu Konfirmasi</span><span class="admin-stat-icon">◷</span></div>
        <div class="admin-stat-value">{{ $stats['need_action'] }}</div>
        <div class="admin-stat-note">Booking baru yang perlu ditinjau</div>
    </div>
    <div class="admin-stat">
        <div class="admin-stat-top"><span>Menunggu Diagnosis</span><span class="admin-stat-icon">⌕</span></div>
        <div class="admin-stat-value">{{ $stats['diagnosis'] }}</div>
        <div class="admin-stat-note">Booking diterima, menunggu pemeriksaan</div>
    </div>
    <div class="admin-stat">
        <div class="admin-stat-top"><span>Sedang Dikerjakan</span><span class="admin-stat-icon">⚙</span></div>
        <div class="admin-stat-value">{{ $stats['processing'] }}</div>
        <div class="admin-stat-note">Servis dalam proses pengerjaan</div>
    </div>
    <div class="admin-stat">
        <div class="admin-stat-top"><span>Menunggu Pembayaran</span><span class="admin-stat-icon">Rp</span></div>
        <div class="admin-stat-value">{{ $stats['unpaid'] }}</div>
        <div class="admin-stat-note">Servis selesai, tagihan belum lunas</div>
    </div>
</div>

<div class="admin-grid-2">
    <section class="admin-card">
        <div class="admin-card-header">
            <div>
                <h2 class="admin-card-title">Booking yang Perlu Dipantau</h2>
                <p class="admin-card-description">Data booking terbaru dan status penanganannya.</p>
            </div>
            <a href="{{ route('admin.service-process') }}" class="admin-btn admin-btn-soft">Lihat semua</a>
        </div>

        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Service ID</th>
                        <th>Pelanggan</th>
                        <th>Perangkat</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentBookings as $booking)
                        @php
                            $statusLabel = match ($booking->booking_status) {
                                'PENDING' => 'Menunggu',
                                'WAITING_VERIFICATION' => 'Perlu Verifikasi',
                                'CONFIRMED' => match ($booking->service_status) {
                                    'DIAGNOSIS' => 'Diagnosis',
                                    'PROCESS' => 'Dikerjakan',
                                    default => 'Diterima',
                                },
                                'COMPLETED' => 'Selesai',
                                'REJECTED' => 'Ditolak',
                                'CANCELLED' => 'Dibatalkan',
                                default => $booking->booking_status,
                            };
                            $statusClass = in_array($booking->booking_status, ['PENDING', 'WAITING_VERIFICATION'], true)
                                ? 'badge-yellow'
                                : (in_array($booking->booking_status, ['REJECTED', 'CANCELLED'], true)
                                    ? 'badge-red'
                                    : ($booking->booking_status === 'COMPLETED' ? 'badge-green' : 'badge-blue'));
                        @endphp
                        <tr>
                            <td><span class="admin-id">{{ $booking->service_id_code }}</span></td>
                            <td><span class="admin-primary-text">{{ $booking->customer_name ?? '-' }}</span></td>
                            <td>{{ trim(($booking->device_type ?? '') . ' ' . ($booking->brand ?? '')) ?: '-' }}</td>
                            <td><span class="admin-badge {{ $statusClass }}">{{ $statusLabel }}</span></td>
                            <td><a href="{{ route('admin.service-process', ['q' => $booking->service_id_code]) }}" class="admin-btn admin-btn-soft">Lihat</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><div class="admin-empty"><strong>Belum ada booking</strong>Booking yang masuk akan muncul di sini.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="admin-card">
        <div class="admin-card-header">
            <div>
                <h2 class="admin-card-title">Jadwal Servis Mendatang</h2>
                <p class="admin-card-description">Pantau kuota jadwal yang sudah dibuat.</p>
            </div>
            <a href="{{ route('admin.schedules.index') }}" class="admin-btn admin-btn-soft">Kelola</a>
        </div>

        @forelse($upcomingSchedules as $schedule)
            @php
                $filled = (int) $schedule->current_booking;
                $capacity = (int) $schedule->max_booking;
                $isFull = $filled >= $capacity || $schedule->status !== 'AVAILABLE';
            @endphp
            <div style="padding:12px 0;border-bottom:1px solid #f1f5f9;">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px;">
                    <div>
                        <div class="admin-primary-text">{{ \Carbon\Carbon::parse($schedule->schedule_date)->locale('id')->translatedFormat('l, d F Y') }}</div>
                        <div class="admin-muted" style="margin-top:4px;">{{ substr($schedule->start_time, 0, 5) }}–{{ substr($schedule->end_time, 0, 5) }}</div>
                    </div>
                    <span class="admin-badge {{ $isFull ? 'badge-yellow' : 'badge-green' }}">{{ $isFull ? 'Penuh' : 'Tersedia' }}</span>
                </div>
                <div class="admin-muted" style="margin-top:8px;">{{ $filled }} dari {{ $capacity }} slot terisi</div>
                <div style="height:5px;background:#f1f5f9;border-radius:99px;margin-top:7px;overflow:hidden;">
                    <div style="height:100%;width:{{ $capacity > 0 ? min(100, ($filled / $capacity) * 100) : 0 }}%;background:#2563eb;border-radius:99px;"></div>
                </div>
            </div>
        @empty
            <div class="admin-empty"><strong>Belum ada jadwal mendatang</strong>Tambahkan jadwal servis agar pelanggan dapat melakukan booking.</div>
        @endforelse
    </section>
</div>

<section class="admin-card">
    <div class="admin-card-header">
        <div>
            <h2 class="admin-card-title">Akses Cepat</h2>
            <p class="admin-card-description">Buka halaman sesuai aktivitas Admin.</p>
        </div>
    </div>
    <div class="admin-grid-2">
        <a href="{{ route('admin.service-process') }}" class="admin-callout" style="text-decoration:none;">
            <strong style="font-size:18px;">▤</strong>
            <span><strong>Kelola Proses Servis</strong><br>Terima booking, isi diagnosis, estimasi, dan perbarui status pengerjaan.</span>
        </a>
        <a href="{{ route('admin.payments') }}" class="admin-callout" style="text-decoration:none;background:#f0fdf4;color:#166534;">
            <strong style="font-size:18px;">Rp</strong>
            <span><strong>Pembayaran</strong><br>Periksa tagihan dan konfirmasi pembayaran pelanggan.</span>
        </a>
    </div>
</section>
@endsection
