@extends('layouts.app')

@section('title', 'Ketersediaan Jadwal')

@section('content')

<div class="schedule-page">

    <div style="margin-bottom: 28px;">
        <p style="font-size: 13px; color: #2563eb; font-weight: 700; margin-bottom: 6px;">
            FIXPOINT SERVICE
        </p>

        <h1 style="font-size: 32px; color: #111827; margin-bottom: 8px;">
            Ketersediaan Jadwal Servis
        </h1>

        <p style="color: #6b7280; max-width: 650px;">
            Pilih jadwal servis yang tersedia untuk melakukan booking
            perbaikan laptop atau PC Anda.
        </p>
    </div>

    <div style="
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 24px;
    ">

        <h2 style="
            font-size: 18px;
            color: #111827;
            margin-bottom: 18px;
        ">
            Pilih Jadwal
        </h2>

        <div style="
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
        ">

            <div style="
                border: 1px solid #2563eb;
                border-radius: 10px;
                padding: 18px;
                background: #eff6ff;
            ">
                <p style="font-size: 13px; color: #6b7280; margin-bottom: 4px;">
                    Senin
                </p>

                <h3 style="font-size: 18px; margin-bottom: 8px;">
                    29 September 2026
                </h3>

                <p style="
                    color: #16a34a;
                    font-size: 13px;
                    font-weight: 700;
                    margin-bottom: 14px;
                ">
                    Tersedia
                </p>

                <a href="/booking" style="
                    display: inline-block;
                    background: #2563eb;
                    color: #ffffff;
                    padding: 9px 14px;
                    border-radius: 7px;
                    font-size: 13px;
                    font-weight: 600;
                ">
                    Pilih Jadwal
                </a>
            </div>

            <div style="
                border: 1px solid #e5e7eb;
                border-radius: 10px;
                padding: 18px;
                background: #ffffff;
            ">
                <p style="font-size: 13px; color: #6b7280; margin-bottom: 4px;">
                    Selasa
                </p>

                <h3 style="font-size: 18px; margin-bottom: 8px;">
                    30 September 2026
                </h3>

                <p style="
                    color: #dc2626;
                    font-size: 13px;
                    font-weight: 700;
                ">
                    Tidak Tersedia
                </p>
            </div>

            <div style="
                border: 1px solid #2563eb;
                border-radius: 10px;
                padding: 18px;
                background: #eff6ff;
            ">
                <p style="font-size: 13px; color: #6b7280; margin-bottom: 4px;">
                    Rabu
                </p>

                <h3 style="font-size: 18px; margin-bottom: 8px;">
                    1 Oktober 2026
                </h3>

                <p style="
                    color: #16a34a;
                    font-size: 13px;
                    font-weight: 700;
                    margin-bottom: 14px;
                ">
                    Tersedia
                </p>

                <a href="/booking" style="
                    display: inline-block;
                    background: #2563eb;
                    color: #ffffff;
                    padding: 9px 14px;
                    border-radius: 7px;
                    font-size: 13px;
                    font-weight: 600;
                ">
                    Pilih Jadwal
                </a>
            </div>

        </div>

    </div>

</div>

@endsection