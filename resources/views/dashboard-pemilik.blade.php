@extends('layouts.app')

@section('title', 'Dashboard Pemilik Usaha')

@section('content')

<style>
    .owner-dashboard {
        display: grid;
        grid-template-columns: 220px minmax(0, 1fr);
        gap: 20px;
        align-items: start;
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
        }

        .owner-sidebar {
            position: static;
        }

        .sidebar-menu {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 560px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }

        .card {
            overflow-x: auto;
        }

        .service-table {
            min-width: 700px;
        }
    }
</style>

<div class="owner-dashboard">

    {{-- SIDEBAR --}}
    <aside class="owner-sidebar">

        <div class="sidebar-title">
            Panel Pemilik Usaha
        </div>

        <div class="sidebar-menu">

            <a href="/dashboard-pemilik" class="sidebar-link active">
                Dashboard
            </a>

            <a href="/ketersediaan-jadwal" class="sidebar-link">
                Ketersediaan Jadwal
            </a>

            <a href="/dashboard-admin" class="sidebar-link">
                Data Service
            </a>

            <a href="#" class="sidebar-link">
                Riwayat Service
            </a>

        </div>

    </aside>

    {{-- MAIN --}}
    <section class="owner-main">

        <div class="owner-header">

            <div class="owner-label">
                FixPoint Service
            </div>

            <h1 class="owner-title">
                Dashboard Pemilik Usaha
            </h1>

            <p class="owner-subtitle">
                Pantau proses servis, diagnosis, biaya, pembayaran,
                dan riwayat layanan.
            </p>

        </div>

        {{-- RINGKASAN --}}
        <div class="stats-grid">

            <div class="stat-card">
                <div class="stat-label">
                    Servis Aktif
                </div>

                <div class="stat-value">
                    12
                </div>

                <div class="stat-note">
                    Sedang dikerjakan
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-label">
                    Menunggu
                </div>

                <div class="stat-value">
                    5
                </div>

                <div class="stat-note">
                    Perlu dipantau
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-label">
                    Selesai
                </div>

                <div class="stat-value">
                    24
                </div>

                <div class="stat-note">
                    Total service
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-label">
                    Pembayaran
                </div>

                <div class="stat-value">
                    8
                </div>

                <div class="stat-note">
                    Perlu dipantau
                </div>
            </div>

        </div>

        <div class="content-grid">

            <div>

                {{-- DATA SERVICE --}}
                <div class="card">

                    <div class="card-header">
                        <h2 class="card-title">
                            Monitoring Service
                        </h2>

                        <a href="#" class="card-link">
                            Lihat riwayat
                        </a>
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

                            <tr>
                                <td class="service-id">FP-001</td>
                                <td>Dimas Pratama</td>
                                <td>ASUS VivoBook</td>
                                <td>
                                    <span class="status status-process">
                                        Dikerjakan
                                    </span>
                                </td>
                                <td>
                                    <span class="status status-unpaid">
                                        Belum Bayar
                                    </span>
                                </td>
                            </tr>

                            <tr>
                                <td class="service-id">FP-002</td>
                                <td>Sandi Maulana</td>
                                <td>HP Notebook</td>
                                <td>
                                    <span class="status status-waiting">
                                        Menunggu
                                    </span>
                                </td>
                                <td>
                                    <span class="status status-unpaid">
                                        Belum Bayar
                                    </span>
                                </td>
                            </tr>

                            <tr>
                                <td class="service-id">FP-003</td>
                                <td>Budi Santoso</td>
                                <td>PC Desktop</td>
                                <td>
                                    <span class="status status-done">
                                        Selesai
                                    </span>
                                </td>
                                <td>
                                    <span class="status status-paid">
                                        Sudah Bayar
                                    </span>
                                </td>
                            </tr>

                            <tr>
                                <td class="service-id">FP-004</td>
                                <td>Fikri Ramadhan</td>
                                <td>ThinkPad</td>
                                <td>
                                    <span class="status status-process">
                                        Dikerjakan
                                    </span>
                                </td>
                                <td>
                                    <span class="status status-paid">
                                        Sudah Bayar
                                    </span>
                                </td>
                            </tr>

                        </tbody>

                    </table>

                </div>

                {{-- AKTIVITAS --}}
                <div class="card">

                    <div class="card-header">
                        <h2 class="card-title">
                            Aktivitas Terbaru
                        </h2>
                    </div>

                    <div class="activity-item">
                        <div class="activity-id">
                            FP-001
                        </div>

                        <div class="activity-text">
                            Proses servis sedang dikerjakan.
                        </div>
                    </div>

                    <div class="activity-item">
                        <div class="activity-id">
                            FP-002
                        </div>

                        <div class="activity-text">
                            Service menunggu pemeriksaan.
                        </div>
                    </div>

                    <div class="activity-item">
                        <div class="activity-id">
                            FP-003
                        </div>

                        <div class="activity-text">
                            Service selesai dan pembayaran sudah diterima.
                        </div>
                    </div>

                </div>

            </div>

            <div>

                {{-- DETAIL MONITORING --}}
                <div class="card">

                    <div class="card-header">
                        <h2 class="card-title">
                            Detail Service
                        </h2>
                    </div>

                    <div class="info-list">

                        <div class="info-item">
                            <div class="info-label">
                                Service ID
                            </div>

                            <div class="info-value">
                                FP-001
                            </div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">
                                Diagnosis
                            </div>

                            <div class="info-value">
                                Menunggu hasil pemeriksaan
                            </div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">
                                Estimasi Biaya
                            </div>

                            <div class="info-value">
                                Menunggu pemeriksaan
                            </div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">
                                Status Pengerjaan
                            </div>

                            <div class="info-value">
                                <span class="status status-process">
                                    Sedang Dikerjakan
                                </span>
                            </div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">
                                Status Pembayaran
                            </div>

                            <div class="info-value">
                                <span class="status status-unpaid">
                                    Belum Bayar
                                </span>
                            </div>
                        </div>

                    </div>

                </div>

            </div>

        </div>

    </section>

</div>

@endsection