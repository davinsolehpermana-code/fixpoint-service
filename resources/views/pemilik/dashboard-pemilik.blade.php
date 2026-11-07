@extends('layouts.app')

@section('title', 'Dashboard Pemilik Usaha')

@section('content')

<style>
.owner-dashboard {
    max-width: 1400px;
    margin: 0 auto;
    padding: 24px 20px;
}

.owner-header {
    margin-bottom: 24px;
}

.owner-label {
    color: #2563eb;
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
    margin-bottom: 6px;
}

.owner-title {
    margin: 0 0 6px;
    font-size: 28px;
    color: #111827;
}

.owner-subtitle {
    margin: 0;
    color: #6b7280;
    font-size: 14px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 16px;
    margin-bottom: 20px;
}

.stat-card,
.card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
}

.stat-card {
    padding: 18px;
}

.stat-label {
    font-size: 12px;
    color: #6b7280;
    margin-bottom: 8px;
}

.stat-value {
    font-size: 26px;
    font-weight: 800;
    color: #111827;
}

.stat-note {
    margin-top: 5px;
    font-size: 11px;
    color: #6b7280;
}

.content-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.7fr) minmax(280px, 1fr);
    gap: 20px;
    align-items: start;
}

.card {
    padding: 18px;
    margin-bottom: 20px;
}

.card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    margin-bottom: 16px;
}

.card-title {
    margin: 0;
    font-size: 16px;
    font-weight: 800;
    color: #111827;
}

.card-description {
    margin: 5px 0 0;
    font-size: 12px;
    color: #6b7280;
}

.date-filter {
    display: flex;
    align-items: center;
    gap: 8px;
}

.date-filter input {
    padding: 8px 10px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 12px;
}

.service-table {
    width: 100%;
    border-collapse: collapse;
}

.service-table th {
    padding: 10px 8px;
    text-align: left;
    font-size: 11px;
    color: #6b7280;
    border-bottom: 1px solid #e5e7eb;
}

.service-table td {
    padding: 12px 8px;
    font-size: 12px;
    color: #374151;
    border-bottom: 1px solid #f3f4f6;
}

.service-table tr:last-child td {
    border-bottom: none;
}

.service-id {
    color: #2563eb;
    font-weight: 800;
}

.status {
    display: inline-block;
    padding: 5px 9px;
    border-radius: 999px;
    font-size: 10px;
    font-weight: 800;
}

.status-process {
    background: #dbeafe;
    color: #1d4ed8;
}

.status-waiting {
    background: #fef3c7;
    color: #92400e;
}

.status-done,
.status-paid {
    background: #dcfce7;
    color: #166534;
}

.status-unpaid {
    background: #fee2e2;
    color: #991b1b;
}

.info-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.info-item {
    padding: 12px;
    border: 1px solid #eef0f4;
    border-radius: 9px;
}

.info-label {
    margin-bottom: 4px;
    font-size: 11px;
    color: #6b7280;
}

.info-value {
    font-size: 13px;
    font-weight: 700;
    color: #111827;
}

.activity-item {
    padding: 12px 0;
    border-bottom: 1px solid #f3f4f6;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-id {
    color: #2563eb;
    font-size: 11px;
    font-weight: 800;
}

.activity-text {
    margin-top: 4px;
    color: #4b5563;
    font-size: 12px;
    line-height: 1.5;
}

.empty-state {
    padding: 20px;
    text-align: center;
    color: #6b7280;
    font-size: 13px;
}

@media (max-width: 1000px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .content-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 600px) {
    .owner-dashboard {
        padding: 18px 14px;
    }

    .owner-title {
        font-size: 23px;
    }

    .stats-grid {
        grid-template-columns: 1fr;
    }

    .card-header {
        align-items: flex-start;
        flex-direction: column;
    }

    .service-table-wrap {
        overflow-x: auto;
    }

    .service-table {
        min-width: 650px;
    }
}
</style>

<div class="owner-dashboard">

    {{-- HEADER --}}
    <div class="owner-header">
        <div class="owner-label">FixPoint Service</div>
        <h1 class="owner-title">Dashboard Pemilik Usaha</h1>
        <p class="owner-subtitle">
            Pantau proses servis, status pembayaran, dan riwayat layanan berdasarkan tanggal.
        </p>
    </div>

    {{-- STATISTIK --}}
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Servis Aktif</div>
            <div class="stat-value">{{ $stats['active'] }}</div>
            <div class="stat-note">Sedang dalam proses pengerjaan</div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Menunggu</div>
            <div class="stat-value">{{ $stats['waiting'] }}</div>
            <div class="stat-note">Booking yang masih perlu dipantau</div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Servis Selesai</div>
            <div class="stat-value">{{ $stats['completed'] }}</div>
            <div class="stat-note">Total booking yang telah selesai</div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Belum Lunas</div>
            <div class="stat-value">{{ $stats['payment_pending'] }}</div>
            <div class="stat-note">Pembayaran yang perlu dipantau</div>
        </div>
    </div>

    @include('pemilik.partials.bukti-pembayaran', ['paymentProofs' => $paymentProofs ?? collect()])

    <div class="content-grid">

        {{-- BAGIAN KIRI --}}
        <div>

            {{-- MONITORING SERVIS --}}
            <section class="card">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Monitoring Servis</h2>
                        <p class="card-description">
                            Pantau status booking dan pembayaran terbaru.
                        </p>
                    </div>
                </div>

                <div class="service-table-wrap">
                    <table class="service-table">
                        <thead>
                            <tr>
                                <th>Service ID</th>
                                <th>Pelanggan</th>
                                <th>Perangkat</th>
                                <th>Status Servis</th>
                                <th>Pembayaran</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($bookings->take(5) as $booking)

                                @php
                                    $statusClass = 'status-waiting';
                                    $statusText = 'Menunggu';

                                    if ($booking->booking_status === 'PENDING') {
                                        $statusText = 'Menunggu';
                                    } elseif ($booking->booking_status === 'WAITING_VERIFICATION') {
                                        $statusText = 'Perlu Verifikasi';
                                    } elseif ($booking->booking_status === 'CONFIRMED') {

                                        if ($booking->service_status === 'PROCESS') {
                                            $statusClass = 'status-process';
                                            $statusText = 'Dikerjakan';
                                        } elseif ($booking->service_status === 'COMPLETED') {
                                            $statusClass = 'status-done';
                                            $statusText = 'Selesai';
                                        } else {
                                            $statusText = 'Diagnosis';
                                        }

                                    } elseif ($booking->booking_status === 'COMPLETED') {
                                        $statusClass = 'status-done';
                                        $statusText = 'Selesai';
                                    } elseif ($booking->booking_status === 'CANCELLED') {
                                        $statusText = 'Dibatalkan';
                                    } elseif ($booking->booking_status === 'REJECTED') {
                                        $statusText = 'Ditolak';
                                    }

                                    $paymentStatus = $booking->payment_status ?? null;
                                    $payStatusClass = $paymentStatus === 'PAID'
                                        ? 'status-paid'
                                        : 'status-unpaid';

                                    $payStatusText = $paymentStatus === 'PAID'
                                        ? 'Lunas'
                                        : 'Belum Bayar';

                                    $deviceName = trim(
                                        ($booking->device_type ?? '') . ' ' .
                                        ($booking->brand ?? '') . ' ' .
                                        ($booking->model ?? '')
                                    );
                                @endphp

                                <tr>
                                    <td class="service-id">
                                        {{ $booking->service_id_code }}
                                    </td>

                                    <td>
                                        {{ $booking->customer_name ?? '-' }}
                                    </td>

                                    <td>
                                        {{ $deviceName ?: '-' }}
                                    </td>

                                    <td>
                                        <span class="status {{ $statusClass }}">
                                            {{ $statusText }}
                                        </span>
                                    </td>

                                    <td>
                                        <span class="status {{ $payStatusClass }}">
                                            {{ $payStatusText }}
                                        </span>
                                    </td>
                                </tr>

                            @empty
                                <tr>
                                    <td colspan="5" class="empty-state">
                                        Belum ada data booking.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            {{-- RIWAYAT SERVIS BERDASARKAN TANGGAL --}}
            <section class="card">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Riwayat Servis</h2>
                        <p class="card-description">
                            Pilih tanggal untuk melihat servis yang selesai pada tanggal tersebut.
                        </p>
                    </div>

                    <form method="GET"
                          action="{{ route('pemilik.dashboard') }}"
                          class="date-filter">

                        <input
                            type="date"
                            name="date"
                            value="{{ $selectedDate ?? '' }}"
                            onchange="this.form.submit()"
                        >

                        @if($selectedDate)
                            <a href="{{ route('pemilik.dashboard') }}"
                               style="font-size:12px;color:#2563eb;text-decoration:none;">
                                Reset
                            </a>
                        @endif
                    </form>
                </div>

                <div class="service-table-wrap">
                    <table class="service-table">
                        <thead>
                            <tr>
                                <th>Service ID</th>
                                <th>Pelanggan</th>
                                <th>Perangkat</th>
                                <th>Biaya</th>
                                <th>Pembayaran</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($serviceHistory as $history)

                                @php
                                    $historyDevice = trim(
                                        ($history->device_type ?? '') . ' ' .
                                        ($history->brand ?? '') . ' ' .
                                        ($history->model ?? '')
                                    );

                                    $isPaid = ($history->payment_status ?? null) === 'PAID';
                                @endphp

                                <tr>
                                    <td class="service-id">
                                        {{ $history->service_id_code }}
                                    </td>

                                    <td>
                                        {{ $history->customer_name ?? '-' }}
                                    </td>

                                    <td>
                                        {{ $historyDevice ?: '-' }}
                                    </td>

                                    <td>
                                        @if($history->final_cost !== null)
                                            Rp {{ number_format((float) $history->final_cost, 0, ',', '.') }}
                                        @else
                                            -
                                        @endif
                                    </td>

                                    <td>
                                        <span class="status {{ $isPaid ? 'status-paid' : 'status-unpaid' }}">
                                            {{ $isPaid ? 'Lunas' : 'Belum Bayar' }}
                                        </span>
                                    </td>
                                </tr>

                            @empty
                                <tr>
                                    <td colspan="5" class="empty-state">
                                        @if($selectedDate)
                                            Tidak ada servis yang selesai pada tanggal yang dipilih.
                                        @else
                                            Belum ada riwayat servis.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

        </div>

        {{-- BAGIAN KANAN --}}
        <div>

            {{-- DETAIL SERVIS TERBARU --}}
            @php
                $latestBooking = $bookings->first();
            @endphp

            <section class="card">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Detail Servis Terbaru</h2>
                        <p class="card-description">
                            Informasi booking terbaru yang masuk.
                        </p>
                    </div>
                </div>

                @if($latestBooking)

                    @php
                        $latestDevice = trim(
                            ($latestBooking->device_type ?? '') . ' ' .
                            ($latestBooking->brand ?? '') . ' ' .
                            ($latestBooking->model ?? '')
                        );

                        $latestServiceStatus = $latestBooking->service_status ?? null;

                        if ($latestBooking->booking_status === 'COMPLETED') {
                            $latestStatusText = 'Selesai';
                            $latestStatusClass = 'status-done';
                        } elseif ($latestServiceStatus === 'PROCESS') {
                            $latestStatusText = 'Sedang Dikerjakan';
                            $latestStatusClass = 'status-process';
                        } elseif ($latestBooking->booking_status === 'WAITING_VERIFICATION') {
                            $latestStatusText = 'Perlu Verifikasi';
                            $latestStatusClass = 'status-waiting';
                        } else {
                            $latestStatusText = 'Menunggu';
                            $latestStatusClass = 'status-waiting';
                        }

                        $latestIsPaid =
                            ($latestBooking->payment_status ?? null) === 'PAID';
                    @endphp

                    <div class="info-list">

                        <div class="info-item">
                            <div class="info-label">Service ID</div>
                            <div class="info-value">
                                {{ $latestBooking->service_id_code }}
                            </div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">Pelanggan</div>
                            <div class="info-value">
                                {{ $latestBooking->customer_name ?? '-' }}
                            </div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">Perangkat</div>
                            <div class="info-value">
                                {{ $latestDevice ?: '-' }}
                            </div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">Status Pengerjaan</div>
                            <div class="info-value">
                                <span class="status {{ $latestStatusClass }}">
                                    {{ $latestStatusText }}
                                </span>
                            </div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">Status Pembayaran</div>
                            <div class="info-value">
                                <span class="status {{ $latestIsPaid ? 'status-paid' : 'status-unpaid' }}">
                                    {{ $latestIsPaid ? 'Lunas' : 'Belum Bayar' }}
                                </span>
                            </div>
                        </div>

                    </div>

                @else

                    <div class="empty-state">
                        Belum ada booking yang dapat ditampilkan.
                    </div>

                @endif
            </section>

            {{-- LAPORAN (panel dipindahkan ke dashboard pemilik) --}}
            <section class="card">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Laporan</h2>
                        <p class="card-description">Akses laporan, cetak, dan unduh PDF/Excel.</p>
                    </div>
                </div>

                <div class="info-list">
                    <div class="info-item">
                        <div class="info-label">Laporan Pemasukan</div>
                        <div class="info-value" style="margin-bottom:10px;">Pembayaran lunas (PAID) berdasarkan tanggal &amp; metode</div>
                        <div style="display:flex;gap:8px;flex-wrap:wrap;">
                            <a href="{{ route('pemilik.income') }}" style="padding:8px 12px;border-radius:8px;background:#2563eb;color:#fff;text-decoration:none;font-size:12px;font-weight:700;">Buka</a>
                            <a href="{{ route('pemilik.income.pdf', ['print' => 1]) }}" target="_blank" rel="noopener" style="padding:8px 12px;border-radius:8px;background:#f3f4f6;color:#374151;text-decoration:none;font-size:12px;font-weight:700;">Cetak</a>
                            <a href="{{ route('pemilik.income.pdf') }}" style="padding:8px 12px;border-radius:8px;background:#dbeafe;color:#1d4ed8;text-decoration:none;font-size:12px;font-weight:700;">PDF</a>
                            <a href="{{ route('pemilik.income.excel') }}" style="padding:8px 12px;border-radius:8px;background:#dcfce7;color:#166534;text-decoration:none;font-size:12px;font-weight:700;">Excel</a>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Riwayat Servis</div>
                        <div class="info-value" style="margin-bottom:10px;">Servis selesai dengan filter rentang tanggal</div>
                        <div style="display:flex;gap:8px;flex-wrap:wrap;">
                            <a href="{{ route('pemilik.service-history') }}" style="padding:8px 12px;border-radius:8px;background:#2563eb;color:#fff;text-decoration:none;font-size:12px;font-weight:700;">Buka</a>
                            <a href="{{ route('pemilik.service-history.pdf', ['print' => 1]) }}" target="_blank" rel="noopener" style="padding:8px 12px;border-radius:8px;background:#f3f4f6;color:#374151;text-decoration:none;font-size:12px;font-weight:700;">Cetak</a>
                            <a href="{{ route('pemilik.service-history.pdf') }}" style="padding:8px 12px;border-radius:8px;background:#dbeafe;color:#1d4ed8;text-decoration:none;font-size:12px;font-weight:700;">PDF</a>
                            <a href="{{ route('pemilik.service-history.excel') }}" style="padding:8px 12px;border-radius:8px;background:#dcfce7;color:#166534;text-decoration:none;font-size:12px;font-weight:700;">Excel</a>
                        </div>
                    </div>
                </div>
            </section>

            {{-- AKTIVITAS TERBARU --}}
            <section class="card">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Aktivitas Terbaru</h2>
                    </div>
                </div>

                @forelse($bookings->take(5) as $booking)

                    <div class="activity-item">
                        <div class="activity-id">
                            {{ $booking->service_id_code }}
                        </div>

                        <div class="activity-text">
                            @if($booking->booking_status === 'PENDING')
                                Booking baru masuk ke antrean.
                            @elseif($booking->booking_status === 'WAITING_VERIFICATION')
                                Booking menunggu verifikasi Admin.
                            @elseif($booking->service_status === 'PROCESS')
                                Servis sedang dalam proses pengerjaan.
                            @elseif($booking->booking_status === 'COMPLETED')
                                Servis telah selesai.
                            @elseif($booking->booking_status === 'CANCELLED')
                                Booking telah dibatalkan.
                            @else
                                Status servis sedang diperbarui.
                            @endif
                        </div>
                    </div>

                @empty

                    <div class="empty-state">
                        Belum ada aktivitas terbaru.
                    </div>

                @endforelse
            </section>

        </div>

    </div>
</div>

@endsection