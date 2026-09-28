@extends('layouts.app')

@section('title', 'Cek Status Service')

@section('content')

<style>
    .status-page {
        max-width: 900px;
        margin: 0 auto;
    }

    .status-header {
        margin-bottom: 28px;
    }

    .status-label {
        color: #2563eb;
        font-size: 13px;
        font-weight: 700;
        margin-bottom: 6px;
    }

    .status-title {
        font-size: 32px;
        color: #111827;
        margin-bottom: 8px;
    }

    .status-description {
        color: #6b7280;
        max-width: 650px;
    }

    .status-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 20px;
    }

    .card-title {
        font-size: 18px;
        color: #111827;
        margin-bottom: 8px;
    }

    .card-description {
        color: #6b7280;
        font-size: 13px;
        margin-bottom: 18px;
    }

    .search-form {
        display: flex;
        gap: 12px;
    }

    .search-input {
        flex: 1;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        padding: 12px 14px;
        font-size: 14px;
        outline: none;
    }

    .search-input:focus {
        border-color: #2563eb;
    }

    .search-button {
        border: none;
        background: #2563eb;
        color: #ffffff;
        padding: 12px 20px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 700;
        cursor: pointer;
    }

    .search-button:hover {
        background: #1d4ed8;
    }

    .service-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        margin-bottom: 20px;
    }

    .service-id {
        color: #2563eb;
        font-size: 20px;
        font-weight: 800;
    }

    .status-badge {
        display: inline-block;
        padding: 7px 11px;
        border-radius: 999px;
        background: #dbeafe;
        color: #1d4ed8;
        font-size: 11px;
        font-weight: 800;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 14px;
    }

    .info-item {
        border: 1px solid #eef0f4;
        border-radius: 9px;
        padding: 14px;
    }

    .info-label {
        color: #6b7280;
        font-size: 11px;
        margin-bottom: 5px;
    }

    .info-value {
        color: #111827;
        font-size: 14px;
        font-weight: 700;
    }

    .progress {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 8px;
        margin-top: 24px;
    }

    .progress-item {
        text-align: center;
    }

    .progress-dot {
        width: 14px;
        height: 14px;
        border-radius: 50%;
        margin: 0 auto 8px;
        background: #d1d5db;
    }

    .progress-item.active .progress-dot {
        background: #2563eb;
    }

    .progress-text {
        color: #6b7280;
        font-size: 11px;
    }

    .progress-item.active .progress-text {
        color: #2563eb;
        font-weight: 700;
    }

    .detail-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 14px;
    }

    .detail-box {
        border: 1px solid #eef0f4;
        border-radius: 9px;
        padding: 16px;
    }

    .detail-box.full {
        grid-column: 1 / -1;
    }

    .detail-label {
        color: #6b7280;
        font-size: 11px;
        margin-bottom: 6px;
    }

    .detail-value {
        color: #111827;
        font-size: 14px;
        font-weight: 700;
    }

    .detail-text {
        color: #4b5563;
        font-size: 13px;
        line-height: 1.6;
    }

    .payment-badge {
        display: inline-block;
        padding: 6px 9px;
        border-radius: 999px;
        background: #fef3c7;
        color: #92400e;
        font-size: 10px;
        font-weight: 800;
    }

    @media (max-width: 700px) {
        .search-form {
            flex-direction: column;
        }

        .info-grid,
        .detail-grid {
            grid-template-columns: 1fr;
        }

        .detail-box.full {
            grid-column: auto;
        }

        .service-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .progress {
            grid-template-columns: repeat(2, 1fr);
            row-gap: 18px;
        }
    }
</style>

<div class="status-page">

    <div class="status-header">
        <div class="status-label">
            FIXPOINT SERVICE
        </div>

        <h1 class="status-title">
            Cek Status Service
        </h1>

        <p class="status-description">
            Masukkan Service ID untuk melihat perkembangan servis
            perangkat Anda.
        </p>
    </div>

    {{-- FORM CEK STATUS --}}
    <div class="status-card">

        <h2 class="card-title">
            Masukkan Service ID
        </h2>

        <p class="card-description">
            Service ID diberikan setelah proses booking selesai.
        </p>

        <form class="search-form">
            <input
                type="text"
                class="search-input"
                placeholder="Contoh: FP-001"
            >

            <button type="button" class="search-button">
                Cek Status
            </button>
        </form>

    </div>

    {{-- HASIL STATUS --}}
    <div class="status-card">

        <div class="service-header">

            <div>
                <div class="info-label">
                    Service ID
                </div>

                <div class="service-id">
                    FP-001
                </div>
            </div>

            <span class="status-badge">
                Sedang Dikerjakan
            </span>

        </div>

        <div class="info-grid">

            <div class="info-item">
                <div class="info-label">
                    Nama Pelanggan
                </div>

                <div class="info-value">
                    Dimas Pratama
                </div>
            </div>

            <div class="info-item">
                <div class="info-label">
                    Perangkat
                </div>

                <div class="info-value">
                    ASUS VivoBook
                </div>
            </div>

            <div class="info-item">
                <div class="info-label">
                    Jadwal Servis
                </div>

                <div class="info-value">
                    29 September 2026
                </div>
            </div>

            <div class="info-item">
                <div class="info-label">
                    Status Pembayaran
                </div>

                <div class="info-value">
                    Belum Bayar
                </div>
            </div>

        </div>

        {{-- PROGRESS STATUS --}}
        <div class="progress">

            <div class="progress-item active">
                <div class="progress-dot"></div>
                <div class="progress-text">
                    Menunggu
                </div>
            </div>

            <div class="progress-item active">
                <div class="progress-dot"></div>
                <div class="progress-text">
                    Pemeriksaan
                </div>
            </div>

            <div class="progress-item active">
                <div class="progress-dot"></div>
                <div class="progress-text">
                    Dikerjakan
                </div>
            </div>

            <div class="progress-item">
                <div class="progress-dot"></div>
                <div class="progress-text">
                    Selesai
                </div>
            </div>

        </div>

    </div>

    {{-- DETAIL SERVICE --}}
    <div class="status-card">

        <h2 class="card-title">
            Detail Service
        </h2>

        <div class="detail-grid">

            <div class="detail-box">
                <div class="detail-label">
                    Diagnosis
                </div>

                <div class="detail-text">
                    Hasil diagnosis akan ditampilkan setelah proses pemeriksaan perangkat.
                </div>
            </div>

            <div class="detail-box">
                <div class="detail-label">
                    Estimasi Biaya
                </div>

                <div class="detail-value">
                    Menunggu pemeriksaan
                </div>
            </div>

            <div class="detail-box full">
                <div class="detail-label">
                    Keluhan
                </div>

                <div class="detail-text">
                    Perangkat mengalami kendala dan membutuhkan pemeriksaan lebih lanjut.
                </div>
            </div>

            <div class="detail-box">
                <div class="detail-label">
                    Status Pembayaran
                </div>

                <div>
                    <span class="payment-badge">
                        Belum Bayar
                    </span>
                </div>
            </div>

            <div class="detail-box">
                <div class="detail-label">
                    Riwayat Service
                </div>

                <div class="detail-value">
                    Tersedia setelah service selesai
                </div>
            </div>

        </div>

    </div>

</div>

@endsection