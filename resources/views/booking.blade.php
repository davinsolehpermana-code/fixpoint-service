@extends('layouts.app')

@section('title', 'Booking Service')

@section('content')

<style>
    .booking-header {
        margin-bottom: 28px;
    }

    .booking-label {
        color: #2563eb;
        font-size: 13px;
        font-weight: 700;
        margin-bottom: 6px;
    }

    .booking-title {
        font-size: 32px;
        color: #111827;
        margin-bottom: 8px;
    }

    .booking-description {
        color: #6b7280;
        max-width: 700px;
    }

    .booking-layout {
        display: grid;
        grid-template-columns: minmax(0, 2fr) minmax(280px, 1fr);
        gap: 24px;
    }

    .booking-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 20px;
    }

    .booking-card-title {
        font-size: 18px;
        color: #111827;
        margin-bottom: 18px;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 7px;
    }

    .form-group.full {
        grid-column: 1 / -1;
    }

    .form-label {
        font-size: 13px;
        font-weight: 700;
        color: #374151;
    }

    .form-control {
        width: 100%;
        padding: 11px 13px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 14px;
        background: #ffffff;
        color: #111827;
        outline: none;
    }

    .form-control:focus {
        border-color: #2563eb;
    }

    .service-options {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
    }

    .service-option {
        border: 1px solid #dbe2ea;
        border-radius: 10px;
        padding: 16px;
        cursor: pointer;
        transition: 0.2s;
    }

    .service-option:hover {
        border-color: #2563eb;
        background: #eff6ff;
    }

    .service-option input {
        margin-right: 8px;
    }

    .service-option-title {
        font-size: 15px;
        font-weight: 700;
        color: #111827;
    }

    .service-option-text {
        margin-top: 5px;
        font-size: 13px;
        color: #6b7280;
    }

    .schedule-box {
        border: 1px solid #2563eb;
        background: #eff6ff;
        border-radius: 10px;
        padding: 16px;
    }

    .schedule-box strong {
        color: #111827;
    }

    .schedule-status {
        display: inline-block;
        margin-top: 8px;
        color: #15803d;
        font-size: 13px;
        font-weight: 700;
    }

    .summary-card {
        position: sticky;
        top: 95px;
    }

    .summary-row {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        padding: 12px 0;
        border-bottom: 1px solid #f0f1f3;
        font-size: 14px;
    }

    .summary-row:last-child {
        border-bottom: none;
    }

    .summary-label {
        color: #6b7280;
    }

    .summary-value {
        color: #111827;
        font-weight: 600;
        text-align: right;
    }

    .summary-total {
        margin-top: 8px;
        padding-top: 16px;
        border-top: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        gap: 16px;
    }

    .summary-total strong {
        color: #2563eb;
        font-size: 18px;
    }

    .booking-button {
        display: block;
        width: 100%;
        border: none;
        background: #2563eb;
        color: #ffffff;
        padding: 13px 16px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 700;
        cursor: pointer;
        margin-top: 20px;
    }

    .booking-button:hover {
        background: #1d4ed8;
    }

    .back-link {
        display: inline-block;
        margin-top: 12px;
        color: #2563eb;
        font-size: 13px;
        font-weight: 600;
        text-align: center;
        width: 100%;
    }

    @media (max-width: 900px) {
        .booking-layout {
            grid-template-columns: 1fr;
        }

        .summary-card {
            position: static;
        }
    }

    @media (max-width: 640px) {
        .booking-title {
            font-size: 26px;
        }

        .form-grid,
        .service-options {
            grid-template-columns: 1fr;
        }

        .booking-card {
            padding: 18px;
        }
    }
</style>

<div class="booking-header">
    <div class="booking-label">FIXPOINT SERVICE</div>

    <h1 class="booking-title">
        Booking Perbaikan Perangkat
    </h1>

    <p class="booking-description">
        Isi data perangkat dan keluhan untuk melakukan booking
        servis laptop atau PC.
    </p>
</div>

<div class="booking-layout">

    <div>

        {{-- DATA PELANGGAN --}}
        <div class="booking-card">
            <h2 class="booking-card-title">
                Data Pelanggan
            </h2>

            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Nama Pelanggan</label>
                    <input
                        type="text"
                        class="form-control"
                        placeholder="Masukkan nama"
                    >
                </div>

                <div class="form-group">
                    <label class="form-label">Nomor Telepon</label>
                    <input
                        type="text"
                        class="form-control"
                        placeholder="Masukkan nomor telepon"
                    >
                </div>
            </div>
        </div>

        {{-- SPESIFIKASI PERANGKAT --}}
        <div class="booking-card">
            <h2 class="booking-card-title">
                Spesifikasi Perangkat
            </h2>

            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Jenis Perangkat</label>

                    <select class="form-control">
                        <option value="">Pilih perangkat</option>
                        <option>Laptop</option>
                        <option>PC Desktop</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Merek / Model</label>

                    <input
                        type="text"
                        class="form-control"
                        placeholder="Contoh: ASUS VivoBook"
                    >
                </div>
            </div>
        </div>

        {{-- KATEGORI LAYANAN --}}
        <div class="booking-card">
            <h2 class="booking-card-title">
                Kategori Layanan
            </h2>

            <div class="service-options">

                <label class="service-option">
                    <div>
                        <input type="radio" name="service" value="laptop">
                        <span class="service-option-title">
                            Servis Laptop
                        </span>
                    </div>

                    <p class="service-option-text">
                        Pemeriksaan dan perbaikan laptop.
                    </p>
                </label>

                <label class="service-option">
                    <div>
                        <input type="radio" name="service" value="pc">
                        <span class="service-option-title">
                            Servis PC Desktop
                        </span>
                    </div>

                    <p class="service-option-text">
                        Pemeriksaan dan perbaikan PC desktop.
                    </p>
                </label>

            </div>
        </div>

        {{-- DETAIL KELUHAN --}}
        <div class="booking-card">
            <h2 class="booking-card-title">
                Detail Keluhan
            </h2>

            <div class="form-group">
                <label class="form-label">
                    Keluhan Perangkat
                </label>

                <textarea
                    class="form-control"
                    rows="6"
                    placeholder="Jelaskan masalah pada perangkat..."
                ></textarea>
            </div>
        </div>

        {{-- JADWAL SERVIS --}}
        <div class="booking-card">
            <h2 class="booking-card-title">
                Jadwal Servis
            </h2>

            <div class="schedule-box">
                <strong>Senin, 29 September 2026</strong>

                <div style="margin-top: 4px; color: #6b7280; font-size: 13px;">
                    Jadwal servis yang dipilih
                </div>

                <span class="schedule-status">
                    Jadwal Tersedia
                </span>
            </div>
        </div>

    </div>

    {{-- RINGKASAN --}}
    <div>
        <div class="booking-card summary-card">

            <h2 class="booking-card-title">
                Ringkasan Booking
            </h2>

            <div class="summary-row">
                <span class="summary-label">
                    Perangkat
                </span>

                <span class="summary-value">
                    Laptop
                </span>
            </div>

            <div class="summary-row">
                <span class="summary-label">
                    Layanan
                </span>

                <span class="summary-value">
                    Servis Laptop
                </span>
            </div>

            <div class="summary-row">
                <span class="summary-label">
                    Jadwal
                </span>

                <span class="summary-value">
                    29 Sep 2026
                </span>
            </div>

            <div class="summary-row">
                <span class="summary-label">
                    Status Jadwal
                </span>

                <span class="summary-value" style="color: #15803d;">
                    Tersedia
                </span>
            </div>

            <div class="summary-total">
                <span>
                    Estimasi awal
                </span>

                <strong>
                    Menunggu pemeriksaan
                </strong>
            </div>

            <button type="button" class="booking-button">
                Booking Service
            </button>

            <a href="/ketersediaan-jadwal" class="back-link">
                Kembali ke Ketersediaan Jadwal
            </a>

        </div>
    </div>

</div>

@endsection