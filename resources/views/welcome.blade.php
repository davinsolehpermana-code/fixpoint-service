@extends('layouts.app')

@section('title', 'Beranda')

@section('content')

<style>
    .hero {
        padding: 55px 0 40px;
    }

    .hero-grid {
        display: grid;
        grid-template-columns: 1.15fr 0.85fr;
        gap: 32px;
        align-items: center;
    }

    .hero-label {
        color: #2563eb;
        font-size: 13px;
        font-weight: 800;
        margin-bottom: 10px;
        text-transform: uppercase;
    }

    .hero-title {
        font-size: 46px;
        line-height: 1.12;
        color: #111827;
        margin-bottom: 18px;
        max-width: 650px;
    }

    .hero-text {
        color: #6b7280;
        font-size: 16px;
        max-width: 590px;
        margin-bottom: 24px;
    }

    .hero-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
    }

    .btn-primary {
        background: #2563eb;
        color: white;
        padding: 12px 18px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 700;
    }

    .btn-secondary {
        background: white;
        color: #2563eb;
        padding: 12px 18px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 700;
        border: 1px solid #dbe2ea;
    }

    .hero-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        padding: 24px;
    }

    .hero-card-title {
        font-size: 18px;
        font-weight: 800;
        color: #111827;
        margin-bottom: 16px;
    }

    .schedule-item {
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 14px;
        margin-bottom: 12px;
    }

    .schedule-item:last-child {
        margin-bottom: 0;
    }

    .schedule-date {
        font-size: 13px;
        color: #111827;
        font-weight: 700;
        margin-bottom: 5px;
    }

    .schedule-status {
        font-size: 12px;
        font-weight: 700;
        color: #15803d;
    }

    .section {
        padding: 35px 0;
    }

    .section-title {
        font-size: 25px;
        color: #111827;
        margin-bottom: 8px;
    }

    .section-text {
        color: #6b7280;
        margin-bottom: 22px;
    }

    .service-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
    }

    .service-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 20px;
    }

    .service-card h3 {
        color: #111827;
        font-size: 16px;
        margin-bottom: 8px;
    }

    .service-card p {
        color: #6b7280;
        font-size: 13px;
    }

    @media (max-width: 900px) {
        .hero-grid {
            grid-template-columns: 1fr;
        }

        .service-grid {
            grid-template-columns: 1fr;
        }

        .hero-title {
            font-size: 36px;
        }
    }

    @media (max-width: 600px) {
        .hero {
            padding-top: 30px;
        }

        .hero-title {
            font-size: 30px;
        }

        .hero-actions {
            flex-direction: column;
        }

        .btn-primary,
        .btn-secondary {
            text-align: center;
        }
    }
</style>

<section class="hero">

    <div class="hero-grid">

        <div>
            <div class="hero-label">
                FixPoint Service
            </div>

            <h1 class="hero-title">
                Servis Laptop & PC Lebih Mudah
            </h1>

            <p class="hero-text">
                Lakukan booking servis, pilih jadwal yang tersedia,
                dan pantau perkembangan perbaikan perangkat Anda
                dengan mudah.
            </p>

            <div class="hero-actions">
                <a href="/ketersediaan-jadwal" class="btn-primary">
                    Lihat Ketersediaan Jadwal
                </a>

                <a href="/cek-status" class="btn-secondary">
                    Cek Status Service
                </a>
            </div>
        </div>

        <div class="hero-card">

            <div class="hero-card-title">
                Ketersediaan Jadwal Servis
            </div>

            <div class="schedule-item">
                <div class="schedule-date">
                    Senin, 29 September 2026
                </div>

                <div class="schedule-status">
                    Tersedia
                </div>
            </div>

            <div class="schedule-item">
                <div class="schedule-date">
                    Selasa, 30 September 2026
                </div>

                <div style="font-size: 12px; font-weight: 700; color: #dc2626;">
                    Tidak Tersedia
                </div>
            </div>

            <div class="schedule-item">
                <div class="schedule-date">
                    Rabu, 1 Oktober 2026
                </div>

                <div class="schedule-status">
                    Tersedia
                </div>
            </div>

        </div>

    </div>

</section>

<section class="section">

    <h2 class="section-title">
        Layanan FixPoint
    </h2>

    <p class="section-text">
        Informasi layanan servis laptop dan PC.
    </p>

    <div class="service-grid">

        <div class="service-card">
            <h3>
                Servis Laptop
            </h3>

            <p>
                Pemeriksaan dan perbaikan berbagai masalah pada laptop.
            </p>
        </div>

        <div class="service-card">
            <h3>
                Servis PC
            </h3>

            <p>
                Pemeriksaan dan perbaikan perangkat PC desktop.
            </p>
        </div>

        <div class="service-card">
            <h3>
                Booking Service
            </h3>

            <p>
                Pilih jadwal yang tersedia dan lakukan booking servis.
            </p>
        </div>

    </div>

</section>

@endsection