@extends('layouts.app')
@section('title', 'Ketersediaan Jadwal')
@section('content')
    <style>
        .schedule-page {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .page-header {
            margin-bottom: 28px;
        }

        .page-label {
            font-size: 13px;
            color: #2563eb;
            font-weight: 700;
            margin-bottom: 6px;
            text-transform: uppercase;
        }

        .page-title {
            font-size: 32px;
            color: #111827;
            margin-bottom: 8px;
        }

        .page-desc {
            color: #6b7280;
            max-width: 650px;
            font-size: 15px;
        }

        .schedule-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
        }

        .card-title {
            font-size: 18px;
            color: #111827;
            margin-bottom: 18px;
            font-weight: 700;
        }

        .schedule-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 16px;
        }

        .schedule-item {
            border: 1px solid #2563eb;
            border-radius: 10px;
            padding: 18px;
            background: #eff6ff;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .schedule-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(37, 99, 235, 0.15);
        }

        .schedule-day {
            font-size: 13px;
            color: #6b7280;
            margin-bottom: 4px;
            text-transform: capitalize;
        }

        .schedule-date {
            font-size: 18px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 8px;
        }

        .schedule-time {
            font-size: 13px;
            color: #4b5563;
            margin-bottom: 8px;
        }

        .schedule-slots {
            color: #16a34a;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 14px;
        }

        .btn-book {
            display: inline-block;
            background: #2563eb;
            color: #ffffff;
            padding: 9px 14px;
            border-radius: 7px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: background 0.2s;
        }

        .btn-book:hover {
            background: #1d4ed8;
        }

        .empty-state {
            color: #6b7280;
            padding: 40px 20px;
            background: #f9fafb;
            border-radius: 8px;
            text-align: center;
            grid-column: 1 / -1;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .schedule-page {
                padding: 20px 16px;
            }

            .page-title {
                font-size: 26px;
            }

            .schedule-grid {
                grid-template-columns: 1fr;
            }

            .schedule-item {
                padding: 16px;
            }
        }

        @media (max-width: 480px) {
            .page-title {
                font-size: 22px;
            }

            .schedule-date {
                font-size: 16px;
            }

            .btn-book {
                width: 100%;
                text-align: center;
                box-sizing: border-box;
            }
        }
    </style>

    <div class="schedule-page">
        <div class="page-header">
            <div class="page-label">FixPoint Service</div>
            <h1 class="page-title">Ketersediaan Jadwal Servis</h1>
            <p class="page-desc">Pilih jadwal servis yang tersedia untuk melakukan booking perbaikan laptop atau PC Anda.</p>
        </div>

        <div class="schedule-card">
            <h2 class="card-title">Pilih Jadwal</h2>

            @if ($schedules->isEmpty())
                <div class="empty-state">
                    <p>Saat ini tidak ada jadwal yang tersedia. Silakan hubungi admin untuk informasi lebih lanjut.</p>
                </div>
            @else
                <div class="schedule-grid">
                    
                    @foreach ($schedules as $schedule)
                        <div class="schedule-item">
                            <div class="schedule-day">
                                {{ \Carbon\Carbon::parse($schedule->schedule_date)->locale('id')->translatedFormat('l') }}
                            </div>

                            <div class="schedule-date">
                                {{ \Carbon\Carbon::parse($schedule->schedule_date)->locale('id')->translatedFormat('d F Y') }}
                            </div>

                            <div class="schedule-time">
                                {{ substr($schedule->start_time, 0, 5) }} - {{ substr($schedule->end_time, 0, 5) }}
                            </div>

                            <div class="schedule-slots">
                                Tersedia ({{ $schedule->max_booking - $schedule->current_booking }} slot tersisa)
                            </div>

                            <a href="{{ route('booking') }}?schedule_id={{ $schedule->schedule_id }}" class="btn-book">
                                Pilih Jadwal
                            </a>
                        </div>
                    @endforeach

                </div>
            @endif
        </div>
    </div>
@endsection
