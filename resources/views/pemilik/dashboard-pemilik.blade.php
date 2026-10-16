@extends('layouts.app')
@section('title', 'Dashboard Pemilik Usaha')
@section('content')
<style>
.owner-dashboard {
    display: grid;
    grid-template-columns: 220px minmax(0, 1fr);
    gap: 20px;
    align-items: start;
    padding: 20px;
    max-width: 1400px;
    margin: 0 auto;
}

.owner-sidebar {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 16px;
    position: sticky;
    top: 92px;
}

.sidebar-title {
    font-size: 12px;
    font-weight: 800;
    color: #2563eb;
    margin-bottom: 14px;
    text-transform: uppercase;
}

.sidebar-menu {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.sidebar-toggle {
    display: none;
    width: 100%;
    align-items: center;
    justify-content: space-between;
    padding: 10px 12px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: #ffffff;
    color: #374151;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
}

.sidebar-toggle-icon {
    display: flex;
    flex-direction: column;
    gap: 4px;
    width: 18px;
}

.sidebar-toggle-icon span {
    height: 2px;
    width: 100%;
    background: currentColor;
    border-radius: 2px;
}

.sidebar-link {
    padding: 10px 12px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    color: #4b5563;
    text-decoration: none;
    transition: all 0.2s;
}

.sidebar-link:hover {
    background: #eff6ff;
    color: #2563eb;
}

.sidebar-link.active {
    background: #2563eb;
    color: #ffffff;
}

.owner-main {
    min-width: 0;
}

.owner-header {
    margin-bottom: 20px;
}

.owner-label {
    color: #2563eb;
    font-size: 12px;
    font-weight: 800;
    margin-bottom: 5px;
    text-transform: uppercase;
}

.owner-title {
    font-size: 28px;
    color: #111827;
    margin-bottom: 5px;
}

.owner-subtitle {
    color: #6b7280;
    font-size: 13px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
    margin-bottom: 18px;
}

.stat-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 16px;
}

.stat-label {
    font-size: 12px;
    color: #6b7280;
    margin-bottom: 8px;
}

.stat-value {
    font-size: 25px;
    font-weight: 800;
    color: #111827;
}

.stat-note {
    margin-top: 4px;
    color: #16a34a;
    font-size: 11px;
    font-weight: 700;
}

.content-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.7fr) minmax(260px, 1fr);
    gap: 18px;
}

.card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 18px;
    margin-bottom: 18px;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    margin-bottom: 16px;
}

.card-title {
    font-size: 16px;
    font-weight: 800;
    color: #111827;
}

.card-link {
    color: #2563eb;
    font-size: 12px;
    font-weight: 700;
    text-decoration: none;
}

.service-table {
    width: 100%;
    border-collapse: collapse;
}

.service-table th {
    padding: 9px 8px;
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
    padding: 5px 8px;
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

.status-done {
    background: #dcfce7;
    color: #166534;
}

.status-unpaid {
    background: #fee2e2;
    color: #991b1b;
}

.status-paid {
    background: #dcfce7;
    color: #166534;
}

.info-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.info-item {
    border: 1px solid #eef0f4;
    border-radius: 9px;
    padding: 12px;
}

.info-label {
    font-size: 11px;
    color: #6b7280;
    margin-bottom: 4px;
}

.info-value {
    font-size: 13px;
    font-weight: 700;
    color: #111827;
}

.activity-item {
    padding: 11px 0;
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
    margin-top: 3px;
    color: #4b5563;
    font-size: 11px;
}

/* Mobile Responsive */
@media (max-width: 1100px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .content-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 800px) {
    .owner-dashboard {
        grid-template-columns: 1fr;
        padding: 16px;
    }
    .owner-sidebar {
        position: static;
        margin-bottom: 16px;
    }

    .sidebar-toggle {
        display: flex;
    }

    .sidebar-menu {
        display: none;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        margin-top: 10px;
    }

    .sidebar-menu.active {
        display: grid;
    }
}

@media (max-width: 560px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    .card {
        padding: 14px;
    }
    .service-table {
        display: block;
        overflow-x: auto;
        white-space: nowrap;
        min-width: 600px;
    }
    .owner-title {
        font-size: 22px;
    }
}
</style>

<div class="owner-dashboard">
    {{-- SIDEBAR --}}
    <aside class="owner-sidebar">
        <div class="sidebar-title">Panel Pemilik Usaha</div>
        <button class="sidebar-toggle" id="ownerSidebarToggle" type="button" aria-controls="ownerSidebarMenu" aria-expanded="false">
            <span>Menu Pemilik</span>
            <span class="sidebar-toggle-icon" aria-hidden="true"><span></span><span></span><span></span></span>
        </button>
        <div class="sidebar-menu" id="ownerSidebarMenu">
            <a href="/dashboard-pemilik" class="sidebar-link active">Dashboard</a>
            <a href="/ketersediaan-jadwal" class="sidebar-link">Ketersediaan Jadwal</a>
            <a href="/dashboard-admin" class="sidebar-link">Data Service</a>
            <a href="#" class="sidebar-link">Riwayat Service</a>
        </div>
    </aside>

    {{-- MAIN --}}
    <section class="owner-main">
        <div class="owner-header">
            <div class="owner-label">FixPoint Service</div>
            <h1 class="owner-title">Dashboard Pemilik Usaha</h1>
            <p class="owner-subtitle">Pantau proses servis, diagnosis, biaya, pembayaran, dan riwayat layanan.</p>
        </div>

                {{-- RINGKASAN --}}
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Servis Aktif</div>
                <div class="stat-value">{{ $stats['active'] }}</div>
                <div class="stat-note">Sedang dikerjakan</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Menunggu</div>
                <div class="stat-value">{{ $stats['waiting'] }}</div>
                <div class="stat-note">Perlu dipantau</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Selesai</div>
                <div class="stat-value">{{ $stats['completed'] }}</div>
                <div class="stat-note">Total service</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Pembayaran</div>
                <div class="stat-value">{{ $stats['payment_pending'] }}</div>
                <div class="stat-note">Perlu dipantau</div>
            </div>
        </div>

        <div class="content-grid">
            <div>
                {{-- DATA SERVICE --}}
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Monitoring Service</h2>
                        <a href="#" class="card-link">Lihat riwayat</a>
                    </div>
                    <table class="service-table">
                        <thead>
                            <tr>
                                <th>Service ID</th>
                                <th>Pelanggan</th>
                                <th>Perangkat</th>
                                <th>Status</th>
                                <th>Pembayaran</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($bookings->take(5) as $booking)
                            @php
                                $payment = \App\Models\Payment::where('customer_id', $booking->customer_id)->first();
                                
                                $statusClass = 'status-waiting'; $statusText = 'Menunggu';
                                if($booking->status === 'WAITING_VERIFICATION') { $statusClass = 'status-waiting'; $statusText = 'Tunggu Verifikasi'; }
                                elseif($booking->status === 'CONFIRMED') { $statusClass = 'status-process'; $statusText = 'Dikerjakan'; }
                                elseif($booking->status === 'COMPLETED') { $statusClass = 'status-done'; $statusText = 'Selesai'; }
                                elseif($booking->status === 'REJECTED') { $statusClass = 'status-cancel'; $statusText = 'Ditolak'; }
                                
                                $payStatusClass = 'status-unpaid'; $payStatusText = 'Belum Bayar';
                                if($payment && $payment->payment_status === 'PAID') {
                                    $payStatusClass = 'status-paid'; $payStatusText = 'Sudah Bayar';
                                }
                            @endphp
                            <tr>
                                <td class="service-id">{{ $booking->service_id_code }}</td>
                                <td>{{ $booking->customer->name }}</td>
                                <td>{{ $booking->device->brand }} {{ $booking->device->model }}</td>
                                <td><span class="status {{ $statusClass }}">{{ $statusText }}</span></td>
                                <td><span class="status {{ $payStatusClass }}">{{ $payStatusText }}</span></td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 20px; color: #6b7280;">Belum ada data booking.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- AKTIVITAS --}}
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Aktivitas Terbaru</h2>
                    </div>
                    @forelse($bookings->take(3) as $booking)
                    <div class="activity-item">
                        <div class="activity-id">{{ $booking->service_id_code }}</div>
                        <div class="activity-text">
                            @if($booking->status === 'PENDING') Booking baru masuk ke antrean.
                            @elseif($booking->status === 'WAITING_VERIFICATION') Menunggu verifikasi admin.
                            @elseif($booking->status === 'CONFIRMED') Service sedang dikerjakan.
                            @elseif($booking->status === 'COMPLETED') Service selesai.
                            @else Status diperbarui menjadi {{ $booking->status }}.
                            @endif
                        </div>
                    </div>
                    @empty
                    <div class="activity-item">
                        <div class="activity-text">Belum ada aktivitas terbaru.</div>
                    </div>
                    @endforelse
                </div>
            </div>

            <div>
                {{-- DETAIL MONITORING (Menampilkan Booking Terbaru) --}}
                @php
                    $latestBooking = $bookings->first();
                @endphp
                @if($latestBooking)
                    @php
                        $latestPayment = \App\Models\Payment::where('customer_id', $latestBooking->customer_id)->first();
                        
                        $latestStatusClass = 'status-waiting'; $latestStatusText = 'Menunggu';
                        if($latestBooking->status === 'WAITING_VERIFICATION') { $latestStatusClass = 'status-waiting'; $latestStatusText = 'Tunggu Verifikasi'; }
                        elseif($latestBooking->status === 'CONFIRMED') { $latestStatusClass = 'status-process'; $latestStatusText = 'Dikerjakan'; }
                        elseif($latestBooking->status === 'COMPLETED') { $latestStatusClass = 'status-done'; $latestStatusText = 'Selesai'; }
                        elseif($latestBooking->status === 'REJECTED') { $latestStatusClass = 'status-cancel'; $latestStatusText = 'Ditolak'; }
                        
                        $latestPayStatusClass = 'status-unpaid'; $latestPayStatusText = 'Belum Bayar';
                        if($latestPayment && $latestPayment->payment_status === 'PAID') {
                            $latestPayStatusClass = 'status-paid'; $latestPayStatusText = 'Sudah Bayar';
                        }
                    @endphp
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Detail Service Terbaru</h2>
                        </div>
                        <div class="info-list">
                            <div class="info-item">
                                <div class="info-label">Service ID</div>
                                <div class="info-value">{{ $latestBooking->service_id_code }}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Pelanggan</div>
                                <div class="info-value">{{ $latestBooking->customer->name }}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Perangkat</div>
                                <div class="info-value">{{ $latestBooking->device->brand }} {{ $latestBooking->device->model }}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Status Pengerjaan</div>
                                <div class="info-value"><span class="status {{ $latestStatusClass }}">{{ $latestStatusText }}</span></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Status Pembayaran</div>
                                <div class="info-value"><span class="status {{ $latestPayStatusClass }}">{{ $latestPayStatusText }}</span></div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>
</div>
<script>
    const ownerSidebarToggle = document.getElementById('ownerSidebarToggle');
    const ownerSidebarMenu = document.getElementById('ownerSidebarMenu');

    if (ownerSidebarToggle && ownerSidebarMenu) {
        ownerSidebarToggle.addEventListener('click', () => {
            const isExpanded = ownerSidebarToggle.getAttribute('aria-expanded') === 'true';
            ownerSidebarToggle.setAttribute('aria-expanded', String(!isExpanded));
            ownerSidebarMenu.classList.toggle('active', !isExpanded);
        });
    }
</script>
@endsection