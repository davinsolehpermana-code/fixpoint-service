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

    <div style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px; margin-bottom: 24px;">
        <h2 style="font-size: 18px; color: #111827; margin-bottom: 18px;">
            Pilih Jadwal
        </h2>

        @if($schedules->isEmpty())
            <p style="color: #6b7280; padding: 16px; background: #f9fafb; border-radius: 8px; text-align: center;">
                Saat ini tidak ada jadwal yang tersedia. Silakan hubungi admin untuk informasi lebih lanjut.
            </p>
        @else
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
                @foreach($schedules as $schedule)
                    <div style="border: 1px solid #2563eb; border-radius: 10px; padding: 18px; background: #eff6ff;">
                        <p style="font-size: 13px; color: #6b7280; margin-bottom: 4px;">
                            {{ \Carbon\Carbon::parse($schedule->schedule_date)->translatedFormat('l') }}
                        </p>

                        <h3 style="font-size: 18px; margin-bottom: 8px;">
                            {{ \Carbon\Carbon::parse($schedule->schedule_date)->translatedFormat('d F Y') }}
                        </h3>

                        <p style="font-size: 13px; color: #4b5563; margin-bottom: 8px;">
                            {{ substr($schedule->start_time, 0, 5) }} - {{ substr($schedule->end_time, 0, 5) }}
                        </p>

                        <p style="color: #16a34a; font-size: 13px; font-weight: 700; margin-bottom: 14px;">
                            Tersedia ({{ $schedule->max_booking - $schedule->current_booking }} slot tersisa)
                        </p>

                        <a href="{{ route('booking') }}?schedule_id={{ $schedule->schedule_id }}" style="display: inline-block; background: #2563eb; color: #ffffff; padding: 9px 14px; border-radius: 7px; font-size: 13px; font-weight: 600; text-decoration: none;">
                            Pilih Jadwal
                        </a>
                    </div>
                @endforeach
            </div>
        @endif

    </div>

</div>

@endsection