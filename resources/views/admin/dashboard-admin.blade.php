@extends('layouts.app')
@section('title', 'Dashboard Admin')
@section('content')
<style>
.admin-dashboard {
    display: grid;
    grid-template-columns: 220px minmax(0, 1fr);
    gap: 20px;
    align-items: start;
    padding: 20px;
    max-width: 1400px;
    margin: 0 auto;
}

.admin-sidebar {
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

.dashboard-main {
    min-width: 0;
}

.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 20px;
    margin-bottom: 20px;
}

.dashboard-label {
    color: #2563eb;
    font-size: 12px;
    font-weight: 800;
    margin-bottom: 5px;
    text-transform: uppercase;
}

.dashboard-title {
    font-size: 28px;
    color: #111827;
    margin-bottom: 5px;
}

.dashboard-subtitle {
    color: #6b7280;
    font-size: 13px;
}

.dashboard-action {
    background: #2563eb;
    color: #ffffff;
    border-radius: 8px;
    padding: 10px 15px;
    font-size: 13px;
    font-weight: 700;
    white-space: nowrap;
    text-decoration: none;
}

.dashboard-action:hover {
    background: #1d4ed8;
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
    color: #6b7280;
    font-size: 12px;
    margin-bottom: 8px;
}

.stat-value {
    font-size: 25px;
    font-weight: 800;
    color: #111827;
    margin-bottom: 4px;
}

.stat-note {
    color: #16a34a;
    font-size: 11px;
    font-weight: 700;
}

.dashboard-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.7fr) minmax(260px, 1fr);
    gap: 18px;
}

.dashboard-card {
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
    text-align: left;
    color: #6b7280;
    font-size: 11px;
    font-weight: 700;
    padding: 10px 8px;
    border-bottom: 1px solid #e5e7eb;
}

.service-table td {
    padding: 13px 8px;
    border-bottom: 1px solid #f3f4f6;
    font-size: 12px;
    color: #374151;
    vertical-align: middle;
}

.service-table tr:last-child td {
    border-bottom: none;
}

.service-id {
    font-weight: 800;
    color: #2563eb;
}

.status {
    display: inline-block;
    padding: 5px 8px;
    border-radius: 999px;
    font-size: 10px;
    font-weight: 800;
}

.status-waiting {
    color: #92400e;
    background: #fef3c7;
}

.status-process {
    color: #1d4ed8;
    background: #dbeafe;
}

.status-done {
    color: #166534;
    background: #dcfce7;
}

.status-cancel {
    color: #991b1b;
    background: #fee2e2;
}

.update-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.update-item {
    border: 1px solid #eef0f4;
    border-radius: 9px;
    padding: 12px;
}

.update-top {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 6px;
}

.update-name {
    font-size: 12px;
    font-weight: 800;
    color: #111827;
}

.update-time {
    font-size: 10px;
    color: #9ca3af;
}

.update-text {
    font-size: 11px;
    color: #6b7280;
}

.status-form {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.form-label-small {
    color: #374151;
    font-size: 11px;
    font-weight: 700;
    display: block;
    margin-bottom: 4px;
}

.form-select,
.form-textarea {
    width: 100%;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    padding: 9px 10px;
    font-size: 12px;
    background: #ffffff;
    outline: none;
    box-sizing: border-box;
}

.form-select:focus,
.form-textarea:focus {
    border-color: #2563eb;
}

.save-button {
    width: 100%;
    border: none;
    background: #2563eb;
    color: #ffffff;
    padding: 10px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 800;
    cursor: pointer;
}

.save-button:hover {
    background: #1d4ed8;
}

.activity-item {
    display: flex;
    gap: 10px;
    padding: 10px 0;
    border-bottom: 1px solid #f3f4f6;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #2563eb;
    margin-top: 5px;
    flex-shrink: 0;
}

.activity-text {
    font-size: 11px;
    color: #4b5563;
}
/* TOMBOL AKSI YANG BAGUS */
.btn-aksi {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 7px 14px;
    border: none;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
    color: white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.btn-aksi:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}
.btn-terima { background: linear-gradient(135deg, #16a34a, #15803d); }
.btn-terima:hover { background: linear-gradient(135deg, #15803d, #166534); }
.btn-selesai { background: linear-gradient(135deg, #2563eb, #1d4ed8); }
.btn-selesai:hover { background: linear-gradient(135deg, #1d4ed8, #1e40af); }
.btn-lunas { background: linear-gradient(135deg, #16a34a, #059669); }
.btn-lunas:hover { background: linear-gradient(135deg, #059669, #047857); }
.btn-aksi svg { width: 14px; height: 14px; }

/* Mobile Responsive */
@media (max-width: 1100px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .dashboard-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 800px) {
    .admin-dashboard {
        grid-template-columns: 1fr;
        padding: 16px;
    }
    .admin-sidebar {
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

    .dashboard-header {
        flex-direction: column;
        align-items: flex-start;
    }
}

@media (max-width: 560px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    .dashboard-title {
        font-size: 22px;
    }
    .service-table {
        display: block;
        overflow-x: auto;
        white-space: nowrap;
        min-width: 600px;
    }
    .dashboard-card {
        padding: 14px;
    }
    .form-select,
    .form-textarea {
        font-size: 14px; /* Prevents zoom on iOS */
    }
}
</style>

<div class="admin-dashboard">
    {{-- SIDEBAR --}}
    <aside class="admin-sidebar">
        <div class="sidebar-title">Panel Admin</div>
        <button class="sidebar-toggle" id="adminSidebarToggle" type="button" aria-controls="adminSidebarMenu" aria-expanded="false">
            <span>Menu Admin</span>
            <span class="sidebar-toggle-icon" aria-hidden="true"><span></span><span></span><span></span></span>
        </button>
        <div class="sidebar-menu" id="adminSidebarMenu">
            <a href="/dashboard-admin" class="sidebar-link active">Dashboard</a>
            <a href="/ketersediaan-jadwal" class="sidebar-link">Ketersediaan Jadwal</a>
            <a href="/booking" class="sidebar-link">Booking Service</a>
            <a href="#" class="sidebar-link">Data Customer</a>
            <a href="#" class="sidebar-link">Data Perangkat</a>
            <a href="#" class="sidebar-link">Data Service</a>
            <a href="#" class="sidebar-link">Pembayaran</a>
            <a href="#" class="sidebar-link">Riwayat Service</a>
        </div>
    </aside>

    {{-- MAIN DASHBOARD --}}
    <section class="dashboard-main">
        <div class="dashboard-header">
            <div>
                <div class="dashboard-label">FixPoint Service</div>
                <h1 class="dashboard-title">Dashboard Manajemen Servis</h1>
                <p class="dashboard-subtitle">Pantau booking, proses servis, jadwal, dan aktivitas pelanggan.</p>
            </div>
            <a href="/ketersediaan-jadwal" class="dashboard-action">Kelola Jadwal</a>
        </div>

        {{-- STATISTIK DINAMIS --}}
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Servis Aktif</div>
                <div class="stat-value">{{ $stats['active'] }}</div>
                <div class="stat-note">Sedang diproses</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Booking Menunggu</div>
                <div class="stat-value">{{ $stats['waiting'] }}</div>
                <div class="stat-note">Perlu ditinjau</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Selesai</div>
                <div class="stat-value">{{ $stats['completed'] }}</div>
                <div class="stat-note">Total service</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Jadwal Tersedia</div>
                <div class="stat-value">{{ $stats['schedules'] }}</div>
                <div class="stat-note">Slot servis</div>
            </div>
        </div>

        <div class="dashboard-grid">
            <div>
                {{-- DAFTAR ANTREAN DINAMIS --}}
                <div class="dashboard-card">
                    <div class="card-header">
                        <h2 class="card-title">Daftar Antrean Tiket Aktif</h2>
                        <a href="#" class="card-link">Lihat semua</a>
                    </div>
                    <table class="service-table">
                        <thead>
                            <tr>
                                <th>Service ID</th>
                                <th>Pelanggan</th>
                                <th>Perangkat</th>
                                <th>Status</th>
                                <th>Pembayaran</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
<tbody>
   @forelse($bookings as $booking)
    @php
        $payment = $booking->payment;
        $isPaid = $payment
            && $payment->payment_status === 'PAID'
            && in_array($payment->payment_method, ['CASH', 'QRIS'], true)
            && is_numeric($payment->amount)
            && (float) $payment->amount > 0;

        $statusClass = 'status-waiting'; 
        $statusText = 'Menunggu';

        if ($booking->status === 'WAITING_VERIFICATION') {
            $statusClass = 'status-waiting'; 
            $statusText = 'Tunggu Verifikasi';
        } elseif ($booking->status === 'CONFIRMED') {
            $statusClass = 'status-process'; 
            $statusText = 'Dikerjakan';
        } elseif ($booking->status === 'COMPLETED') {
            $statusClass = 'status-done'; 
            $statusText = 'Selesai';
        } elseif ($booking->status === 'REJECTED') {
            $statusClass = 'status-cancel'; 
            $statusText = 'Ditolak';
        } elseif ($booking->status === 'CANCELLED') {
            $statusClass = 'status-cancel'; 
            $statusText = 'Dibatalkan';
        }
    @endphp

    <tr>
        {{-- Kolom 1: Service ID --}}
        <td class="service-id">{{ $booking->service_id_code }}</td>

        {{-- Kolom 2: Nama Pelanggan --}}
        <td>{{ $booking->customer->name }}</td>

        {{-- Kolom 3: Perangkat --}}
        <td>{{ $booking->device->brand }}</td>

        {{-- Kolom 4: Status (Sudah bersih, ada badge & alasan pembatalan jika dibatalkan) --}}
        <td>
            <span class="status {{ $statusClass }}">{{ $statusText }}</span>
            @if($booking->status === 'CANCELLED' && $booking->cancellation_reason)
                <div style="font-size: 11px; color: #991b1b; margin-top: 4px; font-style: italic;">
                    Alasan: {{ $booking->cancellation_reason }}
                </div>
            @endif
        </td>

        {{-- Kolom 5: Pembayaran --}}
        <td>
            @if($isPaid)
                <span class="payment-badge payment-paid">Lunas</span>
            @else
                <span class="payment-badge payment-unpaid">Belum Bayar</span>
            @endif
            @if($payment && $payment->amount > 0)
                <div style="font-size: 11px; font-weight: 700; color: #111827; margin-top: 4px;">Rp {{ number_format($payment->amount, 0, ',', '.') }}</div>
            @endif
            @if($payment)
                @php
                    $methodLabel = $payment->payment_method ?: '-';
                    $methodBg = $payment->payment_method === 'CASH' ? '#fef3c7' : ($payment->payment_method === 'QRIS' ? '#dbeafe' : '#e5e7eb');
                    $methodColor = $payment->payment_method === 'CASH' ? '#92400e' : ($payment->payment_method === 'QRIS' ? '#1d4ed8' : '#374151');
                @endphp
                <span class="method-badge" style="background: {{ $methodBg }}; color: {{ $methodColor }}; margin-top: 4px; display: inline-block;">{{ $methodLabel }}</span>
            @endif
        </td>

        {{-- Kolom 6: Aksi --}}
        <td>
            @if(in_array($booking->status, ['PENDING', 'WAITING_VERIFICATION']))
                <form action="{{ route('admin.booking.verify', $booking->booking_id) }}" method="POST" style="display:inline;">
                    @csrf @method('PUT')
                    <button type="submit" class="btn-aksi btn-terima" onclick="return confirm('Terima booking ini?')">Terima</button>
                </form>
            @elseif($booking->status === 'CONFIRMED')
                <form action="{{ route('admin.booking.complete', $booking->booking_id) }}" method="POST" style="display:inline;">
                    @csrf @method('PUT')
                    <button type="submit" class="btn-aksi btn-selesai" onclick="return confirm('Tandai servis ini SELESAI?')">Selesai</button>
                </form>
            @elseif(
                $booking->status === 'COMPLETED'
                && $payment
                && in_array($payment->payment_method, ['CASH', 'QRIS'], true)
                && is_numeric($payment->amount)
                && (float) $payment->amount > 0
                && !$isPaid
            )
                <form action="{{ route('admin.booking.markPaid', $booking->booking_id) }}" method="POST" style="display:inline;">
                    @csrf @method('PUT')
                    <button type="submit" class="btn-aksi btn-lunas" onclick="return confirm('Tandai pembayaran LUNAS?')">Tandai Lunas</button>
                </form>
            @else
                <span style="color: #9ca3af; font-size: 11px;">-</span>
            @endif
        </td>
    </tr>
@empty
    <tr><td colspan="6" style="text-align: center; padding: 20px; color: #6b7280;">Belum ada data booking.</td></tr>
@endforelse
</tbody>
                    </table>
                </div>

                {{-- LOG / AKTIVITAS --}}
                <div class="dashboard-card">
                    <div class="card-header">
                        <h2 class="card-title">Log Aktivitas</h2>
                        <a href="#" class="card-link">Riwayat</a>
                    </div>
                    <div class="activity-item">
                        <div class="activity-dot"></div>
                        <div class="activity-text">Booking baru diterima dan masuk ke antrean servis.</div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-dot"></div>
                        <div class="activity-text">Status service diperbarui menjadi sedang dikerjakan.</div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-dot"></div>
                        <div class="activity-text">Jadwal servis diperbarui oleh Admin.</div>
                    </div>
                </div>
            </div>

            <div>
                {{-- FORM TAMBAH JADWAL --}}
                <div class="dashboard-card">
                    <div class="card-header">
                        <h2 class="card-title">Tambah Jadwal Servis</h2>
                    </div>
                    @if(session('success_schedule'))
                        <div style="background: #dcfce7; color: #166534; padding: 10px; border-radius: 8px; margin-bottom: 12px; font-size: 12px; font-weight: 600;">
                            ✅ {{ session('success_schedule') }}
                        </div>
                    @endif
                    <form action="{{ route('admin.schedules.store') }}" method="POST" class="status-form">
                        @csrf
                        <div>
                            <label class="form-label-small">Tanggal Servis</label>
                            <input type="date" name="schedule_date" class="form-select" required min="{{ date('Y-m-d') }}">
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <div>
                                <label class="form-label-small">Jam Mulai</label>
                                <input type="time" name="start_time" class="form-select" required>
                            </div>
                            <div>
                                <label class="form-label-small">Jam Selesai</label>
                                <input type="time" name="end_time" class="form-select" required>
                            </div>
                        </div>
                        <div>
                            <label class="form-label-small">Kuota Slot (Maks Booking)</label>
                            <input type="number" name="max_booking" class="form-select" value="3" min="1" required>
                        </div>
                        <button type="submit" class="save-button">+ Tambah Jadwal</button>
                    </form>
                </div>

                                {{-- UPDATE STATUS --}}
                <div class="dashboard-card">
                    <div class="card-header">
                        <h2 class="card-title">Update Status & Input Biaya</h2>
                    </div>
                    @if(session('success'))
                        <div style="background: #dcfce7; color: #166534; padding: 10px; border-radius: 8px; margin-bottom: 12px; font-size: 12px; font-weight: 600;">
                            ✅ {{ session('success') }}
                        </div>
                    @endif
                    <form action="{{ route('admin.booking.update') }}" method="POST" class="status-form">
                        @csrf
                        <div>
                            <label class="form-label-small">Service ID</label>
                            <select name="service_id_code" class="form-select" required>
                                @foreach($bookings as $booking)
                                    <option value="{{ $booking->service_id_code }}">{{ $booking->service_id_code }} - {{ $booking->customer->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label-small">Status Pengerjaan</label>
                            <select name="status" class="form-select" required>
                                <option value="PENDING">Menunggu (Pending)</option>
                                <option value="WAITING_VERIFICATION">Tunggu Verifikasi</option>
                                <option value="CONFIRMED">Sedang Dikerjakan (Confirmed)</option>
                                <option value="COMPLETED">Selesai (Completed)</option>
                                <option value="REJECTED">Ditolak (Rejected)</option>
                                <option value="CANCELLED">Dibatalkan (Cancelled)</option> <!-- 👈 Tambahkan opsi ini -->
                            </select>
                        </div>
                        
                        {{-- INI TAMBAHAN BARU: Input Harga & Metode --}}
                        <div>
                            <label class="form-label-small">Total Biaya (Rp)</label>
                            <input type="number" name="amount" class="form-select" placeholder="Contoh: 150000" min="0" max="9999999999.99" step="0.01" value="{{ old('amount') }}">
                            @error('amount')
                                <div role="alert" style="margin-top: 6px; color: #b91c1c; font-size: 12px;">{{ $message }}</div>
                            @enderror
                        </div>
                        <div>
                            <label class="form-label-small">Metode Pembayaran</label>
                            <select name="payment_method" class="form-select">
                                <option value="">-</option>
                                <option value="CASH">Cash / Tunai</option>
                                <option value="QRIS">QRIS</option>
                            </select>
                        </div>
                        {{-- AKHIR TAMBAHAN BARU --}}

                        <div>
                            <label class="form-label-small">Catatan</label>
                            <textarea name="notes" class="form-textarea" rows="4" placeholder="Masukkan catatan..."></textarea>
                        </div>
                        <button type="submit" class="save-button">Simpan Perubahan</button>
                    </form>
                </div>

                {{-- AKTIVITAS TERBARU --}}
                <div class="dashboard-card">
                    <div class="card-header">
                        <h2 class="card-title">Aktivitas Terbaru</h2>
                    </div>
                    <div class="update-list">
                        <div class="update-item">
                            <div class="update-top">
                                <div class="update-name">Sistem</div>
                                <div class="update-time">Baru saja</div>
                            </div>
                            <div class="update-text">Booking baru masuk ke antrean.</div>
                        </div>
                        <div class="update-item">
                            <div class="update-top">
                                <div class="update-name">Admin</div>
                                <div class="update-time">Hari ini</div>
                            </div>
                            <div class="update-text">Status servis diperbarui.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
<script>
    const adminSidebarToggle = document.getElementById('adminSidebarToggle');
    const adminSidebarMenu = document.getElementById('adminSidebarMenu');

    adminSidebarToggle.addEventListener('click', () => {
        const isExpanded = adminSidebarToggle.getAttribute('aria-expanded') === 'true';
        adminSidebarToggle.setAttribute('aria-expanded', String(!isExpanded));
        adminSidebarMenu.classList.toggle('active', !isExpanded);
    });
</script>
@endsection