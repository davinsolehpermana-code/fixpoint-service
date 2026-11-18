@extends('layouts.admin')

@section('title', 'Edit Jadwal Servis')

@section('admin-content')
<style>
    .fp-breadcrumb{display:flex;align-items:center;gap:7px;flex-wrap:wrap;font-size:11px;color:#94a3b8;margin-bottom:12px}
    .fp-breadcrumb a{color:#2563eb;font-weight:700;text-decoration:none}
    .fp-breadcrumb a:hover{text-decoration:underline}
    .fp-breadcrumb .current{color:#475569;font-weight:750}
    .fp-spinner{width:12px;height:12px;border:2px solid currentColor;border-top-color:transparent;border-radius:50%;display:inline-block;animation:fp-spin .6s linear infinite}
    @keyframes fp-spin{to{transform:rotate(360deg)}}
    [data-loading-scope]{position:relative}
    [data-loading-scope] .fp-form-overlay{position:absolute;inset:0;display:none;align-items:center;justify-content:center;gap:9px;background:rgba(255,255,255,.8);border-radius:14px;font-size:12px;font-weight:750;color:#1d4ed8;z-index:6}
    [data-loading-scope].is-loading .fp-form-overlay{display:flex}
    .admin-btn.is-loading{opacity:.85;cursor:progress}
    .fp-form-actions{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-top:4px}
    @media(max-width:520px){.fp-form-actions{flex-direction:column-reverse}.fp-form-actions .admin-btn{width:100%}}
</style>

<nav class="fp-breadcrumb" aria-label="Breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Dashboard</a>
    <span aria-hidden="true">/</span>
    <span>Operasional</span>
    <span aria-hidden="true">/</span>
    <a href="{{ route('admin.schedules.index') }}">Jadwal Servis</a>
    <span aria-hidden="true">/</span>
    <span class="current" aria-current="page">Edit</span>
</nav>

<div class="admin-page-header">
    <div>
        <div class="admin-eyebrow">Operasional</div>
        <h1 class="admin-page-title">Edit Jadwal Servis</h1>
        <p class="admin-page-subtitle">
            {{ \Carbon\Carbon::parse($schedule->schedule_date)->locale('id')->translatedFormat('l, d F Y') }}
            pukul {{ substr($schedule->start_time, 0, 5) }}–{{ substr($schedule->end_time, 0, 5) }}.
        </p>
    </div>
    <a href="{{ route('admin.schedules.index') }}" class="admin-btn admin-btn-outline">← Kembali ke Daftar</a>
</div>

<div class="admin-grid-form">
    <section class="admin-card" data-loading-scope>
        <div class="admin-card-header">
            <div>
                <h2 class="admin-card-title">Form Edit Jadwal</h2>
                <p class="admin-card-description">Nilai lama sudah terisi otomatis. Periksa kembali sebelum menyimpan.</p>
            </div>
        </div>

        @if($quota->has_bookings)
            <div class="admin-callout" style="background:#fffbeb;color:#92400e;margin-bottom:14px;">
                <strong>!</strong>
                <span>Jadwal ini sudah memiliki {{ $quota->total_bookings }} booking ({{ $quota->active_bookings }} aktif). Tanggal dan jam dikunci agar tidak merugikan pelanggan. Kuota masih boleh diubah selama tidak lebih kecil dari {{ $quota->used_slots }} slot terpakai.</span>
            </div>
        @endif

        <form id="scheduleEditForm" action="{{ route('admin.schedules.update', $schedule->schedule_id) }}" method="POST" data-guard-submit>
            @csrf
            @method('PUT')
            <div class="admin-form-grid">
                <div class="admin-form-group full">
                    <label class="admin-label" for="schedule_date">Tanggal Servis</label>
                    <input class="admin-input" type="date" id="schedule_date" name="schedule_date"
                        value="{{ old('schedule_date', substr($schedule->schedule_date, 0, 10)) }}"
                        min="{{ now()->toDateString() }}" @if($quota->has_bookings) readonly @endif required>
                    @if($quota->has_bookings)
                        <span class="admin-help">Dikunci karena jadwal sudah terikat booking.</span>
                    @endif
                </div>
                <div class="admin-form-group">
                    <label class="admin-label" for="start_time">Jam Mulai</label>
                    <input class="admin-input" type="time" id="start_time" name="start_time"
                        value="{{ old('start_time', substr($schedule->start_time, 0, 5)) }}"
                        @if($quota->has_bookings) readonly @endif required>
                </div>
                <div class="admin-form-group">
                    <label class="admin-label" for="end_time">Jam Selesai</label>
                    <input class="admin-input" type="time" id="end_time" name="end_time"
                        value="{{ old('end_time', substr($schedule->end_time, 0, 5)) }}"
                        @if($quota->has_bookings) readonly @endif required>
                    <span class="admin-help">Jam selesai harus lebih besar daripada jam mulai.</span>
                </div>
                <div class="admin-form-group">
                    <label class="admin-label" for="max_booking">Jumlah Slot</label>
                    <input class="admin-input" type="number" id="max_booking" name="max_booking"
                        min="{{ max(1, $quota->used_slots) }}" max="100"
                        value="{{ old('max_booking', (int) $schedule->max_booking) }}" required>
                    <span class="admin-help">Minimal {{ max(1, $quota->used_slots) }} slot (jumlah slot terpakai saat ini).</span>
                </div>
                <div class="admin-form-group">
                    <label class="admin-label" for="status">Status Ketersediaan</label>
                    <select class="admin-select" id="status" name="status">
                        <option value="AVAILABLE" @selected(old('status', $schedule->status === 'CLOSED' ? 'CLOSED' : 'AVAILABLE') === 'AVAILABLE')>Tersedia</option>
                        <option value="CLOSED" @selected(old('status', $schedule->status === 'CLOSED' ? 'CLOSED' : 'AVAILABLE') === 'CLOSED')>Ditutup</option>
                    </select>
                    <span class="admin-help">Status Penuh ditetapkan otomatis saat kuota habis.</span>
                </div>
                <div class="admin-form-group full">
                    <div class="fp-form-actions">
                        <a href="{{ route('admin.schedules.index') }}" class="admin-btn admin-btn-outline">Batal</a>
                        <button type="submit" class="admin-btn admin-btn-primary" id="scheduleEditSubmit">Simpan Perubahan</button>
                    </div>
                </div>
            </div>
        </form>
        <div class="fp-form-overlay"><span class="fp-spinner"></span> Menyimpan perubahan...</div>
    </section>

    <aside class="admin-card">
        <div class="admin-card-header"><h2 class="admin-card-title">Ringkasan Slot</h2></div>
        <div class="admin-detail-list" style="grid-template-columns:1fr;">
            <div class="admin-detail-item">
                <div class="admin-detail-label">Kapasitas Saat Ini</div>
                <div class="admin-detail-value">{{ (int) $schedule->max_booking }} slot</div>
            </div>
            <div class="admin-detail-item">
                <div class="admin-detail-label">Slot Terpakai</div>
                <div class="admin-detail-value">{{ $quota->used_slots }} slot</div>
            </div>
            <div class="admin-detail-item">
                <div class="admin-detail-label">Sisa Slot</div>
                <div class="admin-detail-value">{{ $quota->remaining_slots }} slot</div>
            </div>
            <div class="admin-detail-item">
                <div class="admin-detail-label">Total Booking Terkait</div>
                <div class="admin-detail-value">{{ $quota->total_bookings }} booking</div>
            </div>
        </div>

        <div class="admin-callout" style="margin-top:14px;"><strong>i</strong><span>Menambah kuota tidak mengubah booking yang sudah ada. Sisa slot dihitung ulang dari kapasitas baru dikurangi slot terpakai.</span></div>
        <div class="admin-callout" style="margin-top:10px;background:#f0fdf4;color:#166534;"><strong>✓</strong><span>Jadwal dengan booking tidak dapat dihapus. Gunakan status <strong>Ditutup</strong> bila jadwal tidak dipakai lagi.</span></div>
    </aside>
</div>

<script>
    (() => {
        document.querySelectorAll('form[data-guard-submit]').forEach((guardedForm) => {
            guardedForm.addEventListener('submit', () => {
                if (guardedForm.dataset.submitting === '1') return;
                guardedForm.dataset.submitting = '1';

                guardedForm.querySelectorAll('button[type="submit"]').forEach((button) => {
                    button.dataset.originalLabel = button.innerHTML;
                    button.disabled = true;
                    button.classList.add('is-loading');
                    button.innerHTML = '<span class="fp-spinner" aria-hidden="true"></span> Memproses...';
                });

                const scope = guardedForm.closest('[data-loading-scope]');
                if (scope) scope.classList.add('is-loading');
            });
        });

        // Tombol kembali normal saat halaman dipulihkan dari bfcache.
        window.addEventListener('pageshow', () => {
            document.querySelectorAll('form[data-guard-submit]').forEach((submittedForm) => {
                submittedForm.dataset.submitting = '0';
                submittedForm.querySelectorAll('button[type="submit"]').forEach((button) => {
                    if (button.dataset.originalLabel) button.innerHTML = button.dataset.originalLabel;
                    button.disabled = false;
                    button.classList.remove('is-loading');
                });
                const scope = submittedForm.closest('[data-loading-scope]');
                if (scope) scope.classList.remove('is-loading');
            });
        });
    })();
</script>
@endsection
