@extends('layouts.admin')

@section('title', 'Tambah Jadwal Servis')

@section('admin-content')
<div class="admin-page-header">
    <div>
        <div class="admin-eyebrow">Operasional</div>
        <h1 class="admin-page-title">Tambah Jadwal Servis</h1>
        <p class="admin-page-subtitle">Buat jadwal dan tentukan jumlah booking yang dapat diterima pada setiap sesi.</p>
    </div>
</div>

<div class="admin-grid-form">
    <section class="admin-card">
        <div class="admin-card-header">
            <div>
                <h2 class="admin-card-title">Form Jadwal Baru</h2>
                <p class="admin-card-description">Jadwal yang disimpan akan tersedia pada halaman booking pelanggan.</p>
            </div>
        </div>
        <form action="{{ route('admin.schedules.store') }}" method="POST">
            @csrf
            <div class="admin-form-grid">
                <div class="admin-form-group full">
                    <label class="admin-label" for="schedule_date">Tanggal Servis</label>
                    <input class="admin-input" type="date" id="schedule_date" name="schedule_date" value="{{ old('schedule_date') }}" min="{{ now()->toDateString() }}" required>
                </div>
                <div class="admin-form-group">
                    <label class="admin-label" for="start_time">Jam Mulai</label>
                    <input class="admin-input" type="time" id="start_time" name="start_time" value="{{ old('start_time') }}" required>
                </div>
                <div class="admin-form-group">
                    <label class="admin-label" for="end_time">Jam Selesai</label>
                    <input class="admin-input" type="time" id="end_time" name="end_time" value="{{ old('end_time') }}" required>
                </div>
                <div class="admin-form-group full">
                    <label class="admin-label" for="max_booking">Kuota Booking</label>
                    <input class="admin-input" type="number" id="max_booking" name="max_booking" min="1" max="100" value="{{ old('max_booking', 3) }}" required>
                    <span class="admin-help">Jumlah maksimal booking untuk sesi jadwal ini.</span>
                </div>
                <div class="admin-form-group full">
                    <button type="submit" class="admin-btn admin-btn-primary" style="width:100%;">＋ Simpan Jadwal Servis</button>
                </div>
            </div>
        </form>
    </section>

    <aside class="admin-card">
        <div class="admin-card-header"><h2 class="admin-card-title">Ketentuan Jadwal</h2></div>
        <div class="admin-callout"><strong>i</strong><span>Jadwal hanya dapat dibuat untuk hari ini atau tanggal setelahnya.</span></div>
        <div class="admin-callout" style="margin-top:10px;background:#fffbeb;color:#92400e;"><strong>!</strong><span>Jam jadwal tidak boleh bertabrakan dengan sesi lain pada tanggal yang sama.</span></div>
        <div class="admin-callout" style="margin-top:10px;background:#f0fdf4;color:#166534;"><strong>✓</strong><span>Kuota terisi otomatis setiap pelanggan berhasil membuat booking pada jadwal tersebut.</span></div>
    </aside>
</div>

<section class="admin-card">
    <div class="admin-card-header">
        <div>
            <h2 class="admin-card-title">Daftar Jadwal Servis</h2>
            <p class="admin-card-description">Jadwal yang sudah dibuat beserta kapasitas booking.</p>
        </div>
        <span class="admin-badge badge-blue">{{ $schedules->total() }} jadwal</span>
    </div>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr><th>Tanggal</th><th>Waktu</th><th>Kuota Terisi</th><th>Status</th></tr>
            </thead>
            <tbody>
                @forelse($schedules as $schedule)
                    @php
                        $filled = (int) $schedule->current_booking;
                        $capacity = (int) $schedule->max_booking;
                        $isPast = \Carbon\Carbon::parse($schedule->schedule_date)->isPast() && !\Carbon\Carbon::parse($schedule->schedule_date)->isToday();
                        $isFull = $filled >= $capacity || $schedule->status !== 'AVAILABLE' || $isPast;
                    @endphp
                    <tr>
                        <td><span class="admin-primary-text">{{ \Carbon\Carbon::parse($schedule->schedule_date)->locale('id')->translatedFormat('l, d F Y') }}</span></td>
                        <td>{{ substr($schedule->start_time, 0, 5) }}–{{ substr($schedule->end_time, 0, 5) }}</td>
                        <td>
                            <strong>{{ $filled }} / {{ $capacity }}</strong>
                            <div style="height:5px;width:110px;background:#f1f5f9;border-radius:99px;margin-top:6px;overflow:hidden;">
                                <div style="height:100%;width:{{ $capacity > 0 ? min(100, ($filled / $capacity) * 100) : 0 }}%;background:#2563eb;border-radius:99px;"></div>
                            </div>
                        </td>
                        <td><span class="admin-badge {{ $isFull ? 'badge-yellow' : 'badge-green' }}">{{ $isPast ? 'Lewat' : ($isFull ? 'Penuh / Tidak Aktif' : 'Tersedia') }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="4"><div class="admin-empty"><strong>Belum ada jadwal</strong>Gunakan form di atas untuk menambahkan jadwal servis.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="admin-pagination">{{ $schedules->links() }}</div>
</section>
@endsection
