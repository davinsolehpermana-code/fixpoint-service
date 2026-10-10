@extends('layouts.app')

@section('title', 'Dashboard Admin')

@section('content')

<style>
    .admin-dashboard {
        display: grid;
        grid-template-columns: 220px minmax(0, 1fr);
        gap: 20px;
        align-items: start;
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

    .sidebar-link {
        padding: 10px 12px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        color: #4b5563;
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
        }

        .admin-sidebar {
            position: static;
        }

        .sidebar-menu {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 560px) {
        .dashboard-header {
            flex-direction: column;
        }

        .stats-grid {
            grid-template-columns: 1fr;
        }

        .service-table {
            min-width: 650px;
        }

        .dashboard-card {
            overflow-x: auto;
        }
    }
</style>

<div class="admin-dashboard">

    {{-- SIDEBAR --}}
    <aside class="admin-sidebar">

        <div class="sidebar-title">
            Panel Admin
        </div>

        <div class="sidebar-menu">
            <a href="/dashboard-admin" class="sidebar-link active">
                Dashboard
            </a>

            <a href="/ketersediaan-jadwal" class="sidebar-link">
                Ketersediaan Jadwal
            </a>

            <a href="/booking" class="sidebar-link">
                Booking Service
            </a>

            <a href="#" class="sidebar-link">
                Data Customer
            </a>

            <a href="#" class="sidebar-link">
                Data Perangkat
            </a>

            <a href="#" class="sidebar-link">
                Data Service
            </a>

            <a href="#" class="sidebar-link">
                Pembayaran
            </a>

            <a href="#" class="sidebar-link">
                Riwayat Service
            </a>
        </div>

    </aside>

    {{-- MAIN DASHBOARD --}}
    <section class="dashboard-main">

        <div class="dashboard-header">
            <div>
                <div class="dashboard-label">
                    FixPoint Service
                </div>

                <h1 class="dashboard-title">
                    Dashboard Manajemen Servis
                </h1>

                <p class="dashboard-subtitle">
                    Pantau booking, proses servis, jadwal, dan aktivitas pelanggan.
                </p>
            </div>

            <a href="/ketersediaan-jadwal" class="dashboard-action">
                Kelola Jadwal
            </a>
        </div>

        {{-- STATISTIK --}}
        <div class="stats-grid">

            <div class="stat-card">
                <div class="stat-label">
                    Servis Aktif
                </div>

                <div class="stat-value">
                    18
                </div>

                <div class="stat-note">
                    Sedang diproses
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-label">
                    Booking Menunggu
                </div>

                <div class="stat-value">
                    7
                </div>

                <div class="stat-note">
                    Perlu ditinjau
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-label">
                    Selesai
                </div>

                <div class="stat-value">
                    4
                </div>

                <div class="stat-note">
                    Hari ini
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-label">
                    Jadwal Tersedia
                </div>

                <div class="stat-value">
                    9
                </div>

                <div class="stat-note">
                    Slot servis
                </div>
            </div>

        </div>

        <div class="dashboard-grid">

            <div>

                {{-- DAFTAR ANTREAN --}}
                <div class="dashboard-card">

                    <div class="card-header">
                        <h2 class="card-title">
                            Daftar Antrean Tiket Aktif
                        </h2>

                        <a href="#" class="card-link">
                            Lihat semua
                        </a>
                    </div>

                    <table class="service-table">
                        <thead>
                            <tr>
                                <th>Service ID</th>
                                <th>Pelanggan</th>
                                <th>Perangkat</th>
                                <th>Status</th>
                            </tr>
                        </thead>

                        <tbody>

                            <tr>
                                <td class="service-id">FP-001</td>
                                <td>Dimas Pratama</td>
                                <td>ASUS VivoBook</td>
                                <td>
                                    <span class="status status-waiting">
                                        Menunggu
                                    </span>
                                </td>
                            </tr>

                            <tr>
                                <td class="service-id">FP-002</td>
                                <td>Sandi Maulana</td>
                                <td>HP Notebook</td>
                                <td>
                                    <span class="status status-process">
                                        Dikerjakan
                                    </span>
                                </td>
                            </tr>

                            <tr>
                                <td class="service-id">FP-003</td>
                                <td>Budi Santoso</td>
                                <td>PC Desktop</td>
                                <td>
                                    <span class="status status-process">
                                        Dikerjakan
                                    </span>
                                </td>
                            </tr>

                            <tr>
                                <td class="service-id">FP-004</td>
                                <td>Fikri Ramadhan</td>
                                <td>ThinkPad</td>
                                <td>
                                    <span class="status status-waiting">
                                        Menunggu
                                    </span>
                                </td>
                            </tr>

                            <tr>
                                <td class="service-id">FP-005</td>
                                <td>Rian Kurniawan</td>
                                <td>Acer Predator</td>
                                <td>
                                    <span class="status status-done">
                                        Selesai
                                    </span>
                                </td>
                            </tr>

                        </tbody>
                    </table>

                </div>

                {{-- LOG / AKTIVITAS --}}
                <div class="dashboard-card">

                    <div class="card-header">
                        <h2 class="card-title">
                            Log Aktivitas
                        </h2>

                        <a href="#" class="card-link">
                            Riwayat
                        </a>
                    </div>

                    <div class="activity-item">
                        <div class="activity-dot"></div>
                        <div class="activity-text">
                            Booking baru diterima dan masuk ke antrean servis.
                        </div>
                    </div>

                    <div class="activity-item">
                        <div class="activity-dot"></div>
                        <div class="activity-text">
                            Status service FP-002 diperbarui menjadi sedang dikerjakan.
                        </div>
                    </div>

                    <div class="activity-item">
                        <div class="activity-dot"></div>
                        <div class="activity-text">
                            Jadwal servis diperbarui oleh Admin.
                        </div>
                    </div>

                    <div class="activity-item">
                        <div class="activity-dot"></div>
                        <div class="activity-text">
                            Service FP-005 ditandai selesai.
                        </div>
                    </div>

                </div>

            </div>

            <div>

                {{-- UPDATE STATUS --}}
                <div class="dashboard-card">

                    <div class="card-header">
                        <h2 class="card-title">
                            Update Service
                        </h2>
                    </div>

                    <form class="status-form">

                        <div>
                            <label class="form-label-small">
                                Service ID
                            </label>

                            <select class="form-select">
                                <option>FP-001</option>
                                <option>FP-002</option>
                                <option>FP-003</option>
                            </select>
                        </div>

                        <div>
                            <label class="form-label-small">
                                Status Pengerjaan
                            </label>

                            <select class="form-select">
                                <option>Waiting</option>
                                <option>In Progress</option>
                                <option>Completed</option>
                                <option>Canceled</option>
                            </select>
                        </div>

                        <div>
                            <label class="form-label-small">
                                Catatan
                            </label>

                            <textarea
                                class="form-textarea"
                                rows="4"
                                placeholder="Masukkan catatan..."
                            ></textarea>
                        </div>

                        <button type="button" class="save-button">
                            Simpan Perubahan
                        </button>

                    </form>

                </div>

                {{-- AKTIVITAS TERBARU --}}
                <div class="dashboard-card">

                    <div class="card-header">
                        <h2 class="card-title">
                            Aktivitas Terbaru
                        </h2>
                    </div>

                    <div class="update-list">

                        <div class="update-item">
                            <div class="update-top">
                                <div class="update-name">
                                    Dimas Pratama
                                </div>

                                <div class="update-time">
                                    10:24
                                </div>
                            </div>

                            <div class="update-text">
                                Booking service baru.
                            </div>
                        </div>

                        <div class="update-item">
                            <div class="update-top">
                                <div class="update-name">
                                    Sandi Maulana
                                </div>

                                <div class="update-time">
                                    09:45
                                </div>
                            </div>

                            <div class="update-text">
                                Status servis diperbarui.
                            </div>
                        </div>

                        <div class="update-item">
                            <div class="update-top">
                                <div class="update-name">
                                    Budi Santoso
                                </div>

                                <div class="update-time">
                                    09:12
                                </div>
                            </div>

                            <div class="update-text">
                                Data perangkat diperbarui.
                            </div>
                        </div>

                    </div>

                </div>

            </div>

        </div>

    </section>

</div>

@endsection