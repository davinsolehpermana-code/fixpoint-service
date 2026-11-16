@extends('layouts.admin')

@section('title', 'Kelola Jadwal Servis')

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
    .fp-modal-backdrop{position:fixed;inset:0;background:rgba(15,23,42,.48);display:flex;align-items:center;justify-content:center;padding:16px;z-index:60}
    .fp-modal-backdrop[hidden]{display:none}
    body.fp-modal-open{overflow:hidden}
    .fp-modal{width:100%;max-width:460px;background:#fff;border-radius:16px;box-shadow:0 24px 60px rgba(15,23,42,.24);padding:20px;animation:fp-modal-in .16s ease-out}
    @keyframes fp-modal-in{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:none}}
    .fp-modal-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px}
    .fp-modal-title{font-size:16px;font-weight:800;color:#0f172a;margin:0}
    .fp-modal-close{border:0;background:#f1f5f9;color:#475569;width:28px;height:28px;border-radius:8px;font-size:16px;line-height:1;cursor:pointer}
    .fp-modal-close:hover{background:#e2e8f0}
    .fp-modal-body{font-size:12px;color:#475569;line-height:1.6;margin-top:10px}
    .fp-modal-body strong{color:#0f172a}
    .fp-modal-note{margin-top:11px;padding:11px 12px;border-radius:9px;background:#fffbeb;color:#92400e;font-size:11px;line-height:1.55}
    .fp-modal-foot{display:flex;justify-content:flex-end;gap:8px;margin-top:18px;flex-wrap:wrap}
    .fp-modal-foot form{margin:0}
    @media(max-width:520px){.fp-modal-foot{flex-direction:column-reverse}.fp-modal-foot .admin-btn,.fp-modal-foot form,.fp-modal-foot form .admin-btn{width:100%}}
    .fp-schedule-table{min-width:1040px}
    .fp-quick-edit{display:inline-block}
    .fp-quick-edit summary{cursor:pointer;color:#2563eb;font-weight:800;font-size:11px;list-style:none;display:inline-flex;align-items:center;gap:4px}
    .fp-quick-edit summary::-webkit-details-marker{display:none}
    .fp-quick-edit summary::after{content:"▾";font-size:10px}
    .fp-quick-edit[open] summary::after{content:"▴"}
    .fp-quick-form{display:flex;flex-direction:column;gap:6px;margin-top:8px;min-width:186px}
    .fp-quick-form label{display:flex;align-items:center;justify-content:space-between;gap:8px;font-size:10.5px;font-weight:750;color:#475569}
    .fp-quick-form input[type=time],.fp-quick-form input[type=number]{width:98px;padding:4px 6px;border:1px solid #d8dee8;border-radius:6px;font-size:11px;color:#0f172a;background:#fff}
    .fp-quick-form input[readonly]{background:#f8fafc;color:#64748b}
    .fp-quick-save{padding:5px 9px;font-size:10.5px;border-radius:7px}
    .fp-quick-note{font-size:10px;color:#92400e;line-height:1.4}
</style>

<nav class="fp-breadcrumb" aria-label="Breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Dashboard</a>
    <span aria-hidden="true">/</span>
    <span>Operasional</span>
    <span aria-hidden="true">/</span>
    <span class="current" aria-current="page">Jadwal Servis</span>
</nav>

<div class="admin-page-header">
    <div>pat
        <div class="admin-eyebrow">Operasional</div>
        <h1 class="admin-page-title">Kelola Jadwal Servis</h1>
        <p class="admin-page-subtitle">Buat jadwal baru, ubah tanggal, jam, dan jumlah slot, atau tutup jadwal yang tidak lagi menerima booking.</p>
    </div>
</div>

<div class="admin-grid-form">
    <section class="admin-card" data-loading-scope>
        <div class="admin-card-header">
            <div>
                <h2 class="admin-card-title">Form Jadwal Baru</h2>
                <p class="admin-card-description">Jadwal yang disimpan akan tersedia pada halaman booking pelanggan.</p>
            </div>
        </div>
        <form id="scheduleStoreForm" action="{{ route('admin.schedules.store') }}" method="POST" data-guard-submit>
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
                    <label class="admin-label" for="max_booking">Jumlah Slot</label>
                    <input class="admin-input" type="number" id="max_booking" name="max_booking" min="1" max="100" value="{{ old('max_booking', 3) }}" required>
                    <span class="admin-help">Jumlah maksimal booking untuk sesi jadwal ini (1–100 slot).</span>
                </div>
                <div class="admin-form-group full">
                    <button type="submit" class="admin-btn admin-btn-primary" style="width:100%;" id="scheduleStoreSubmit">＋ Simpan Jadwal Servis</button>
                </div>
            </div>
        </form>
        <div class="fp-form-overlay"><span class="fp-spinner"></span> Menyimpan jadwal...</div>
    </section>

    <aside class="admin-card">
        <div class="admin-card-header"><h2 class="admin-card-title">Ketentuan Jadwal</h2></div>
        <div class="admin-callout"><strong>i</strong><span>Jadwal hanya dapat dibuat untuk hari ini atau tanggal setelahnya.</span></div>
        <div class="admin-callout" style="margin-top:10px;background:#fffbeb;color:#92400e;"><strong>!</strong><span>Jam jadwal tidak boleh bertabrakan dengan sesi lain pada tanggal yang sama.</span></div>
        <div class="admin-callout" style="margin-top:10px;background:#f0fdf4;color:#166534;"><strong>✓</strong><span>Kuota terisi otomatis setiap pelanggan berhasil membuat booking pada jadwal tersebut.</span></div>
        <div class="admin-callout" style="margin-top:10px;background:#fef2f2;color:#991b1b;"><strong>×</strong><span>Jadwal yang sudah memiliki booking tidak dapat dihapus. Tutup jadwal tersebut agar riwayat pelanggan tetap aman.</span></div>
    </aside>
</div>

<section class="admin-card">
    <div class="admin-card-header">
        <div>
            <h2 class="admin-card-title">Daftar Jadwal Servis</h2>
            <p class="admin-card-description">Jadwal yang sudah dibuat beserta kapasitas booking dan aksi pengelolaannya.</p>
        </div>
        <span class="admin-badge badge-blue">{{ $schedules->total() }} jadwal</span>
    </div>
    <div class="admin-table-wrap">
        <table class="admin-table fp-schedule-table">
            <thead>
                <tr><th>Tanggal</th><th>Waktu</th><th>Kuota Terisi</th><th>Status</th><th>Edit Cepat</th><th>Aksi</th></tr>
            </thead>
            <tbody>
                @forelse($schedules as $schedule)
                    @php
                        $filled = (int) $schedule->current_booking;
                        $capacity = (int) $schedule->max_booking;
                        $relatedBookings = (int) ($schedule->bookings_total ?? 0);
                        $isPast = \Carbon\Carbon::parse($schedule->schedule_date)->isPast() && !\Carbon\Carbon::parse($schedule->schedule_date)->isToday();
                        $isClosed = $schedule->status === 'CLOSED';
                        $isFull = !$isClosed && ($filled >= $capacity || $schedule->status === 'FULL');
                        $statusLabel = $isPast ? 'Lewat' : ($isClosed ? 'Ditutup' : ($isFull ? 'Penuh' : 'Tersedia'));
                        $badgeClass = ($isPast || $isClosed) ? 'badge-gray' : ($isFull ? 'badge-yellow' : 'badge-green');
                        $dateLabel = \Carbon\Carbon::parse($schedule->schedule_date)->locale('id')->translatedFormat('l, d F Y');
                        $timeLabel = substr($schedule->start_time, 0, 5) . '–' . substr($schedule->end_time, 0, 5);
                    @endphp
                    <tr>
                        <td><span class="admin-primary-text">{{ $dateLabel }}</span></td>
                        <td>{{ $timeLabel }}</td>
                        <td>
                            <strong>{{ $filled }} / {{ $capacity }}</strong>
                            <div style="height:5px;width:110px;background:#f1f5f9;border-radius:99px;margin-top:6px;overflow:hidden;">
                                <div style="height:100%;width:{{ $capacity > 0 ? min(100, ($filled / $capacity) * 100) : 0 }}%;background:#2563eb;border-radius:99px;"></div>
                            </div>
                            <div class="admin-muted" style="margin-top:5px;">{{ $relatedBookings }} booking terkait</div>
                        </td>
                        <td><span class="admin-badge {{ $badgeClass }}">{{ $statusLabel }}</span></td>
                        <td>
                            @unless($isPast)
                                <details class="fp-quick-edit" data-quick-edit>
                                    <summary>Edit cepat</summary>
                                    <form action="{{ route('admin.schedules.update', $schedule->schedule_id) }}" method="POST" class="fp-quick-form" data-guard-submit>
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="schedule_date" value="{{ substr((string) $schedule->schedule_date, 0, 10) }}">
                                        <label>Mulai
                                            <input type="time" name="start_time" value="{{ substr((string) $schedule->start_time, 0, 5) }}"
                                                @if($relatedBookings > 0) readonly title="Jam dikunci karena jadwal sudah memiliki booking" @endif required>
                                        </label>
                                        <label>Selesai
                                            <input type="time" name="end_time" value="{{ substr((string) $schedule->end_time, 0, 5) }}"
                                                @if($relatedBookings > 0) readonly @endif required>
                                        </label>
                                        <label>Slot
                                            <input type="number" name="max_booking" min="{{ max(1, $filled) }}" max="100" value="{{ $capacity }}" required>
                                        </label>
                                        <button type="submit" class="admin-btn admin-btn-primary fp-quick-save">Simpan</button>
                                        @if($relatedBookings > 0)
                                            <span class="fp-quick-note">Jam terkunci karena sudah ada {{ $relatedBookings }} booking.</span>
                                        @endif
                                    </form>
                                </details>
                            @else
                                <span class="admin-muted">—</span>
                            @endunless
                        </td>
                        <td>
                            <div class="admin-inline-actions">
                                @unless($isPast)
                                    <a href="{{ route('admin.schedules.edit', $schedule->schedule_id) }}" class="admin-btn admin-btn-outline" style="padding:6px 10px;font-size:11px;">Edit</a>
                                    <form action="{{ route('admin.schedules.status', $schedule->schedule_id) }}" method="POST" style="margin:0;" data-guard-submit>
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="admin-btn admin-btn-outline" style="padding:6px 10px;font-size:11px;">
                                            {{ $isClosed ? 'Buka Jadwal' : 'Tutup Jadwal' }}
                                        </button>
                                    </form>
                                @endunless
                                <button
                                    type="button"
                                    class="admin-btn admin-btn-danger"
                                    style="padding:6px 10px;font-size:11px;"
                                    data-delete-url="{{ route('admin.schedules.destroy', $schedule->schedule_id) }}"
                                    data-delete-label="{{ $dateLabel }} ({{ $timeLabel }})"
                                >Hapus</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="admin-empty"><strong>Belum ada jadwal</strong>Gunakan form di atas untuk menambahkan jadwal servis.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="admin-pagination">{{ $schedules->links() }}</div>
</section>

<div class="fp-modal-backdrop" id="scheduleDeleteModal" hidden>
    <div class="fp-modal" role="dialog" aria-modal="true" aria-labelledby="scheduleDeleteTitle">
        <div class="fp-modal-head">
            <h2 class="fp-modal-title" id="scheduleDeleteTitle">Hapus Jadwal Servis?</h2>
            <button type="button" class="fp-modal-close" data-modal-close aria-label="Tutup dialog">×</button>
        </div>
        <div class="fp-modal-body">
            <p style="margin:0;">Jadwal <strong id="scheduleDeleteTarget">jadwal ini</strong> akan dihapus permanen dari daftar.</p>
            <div class="fp-modal-note">
                Jadwal yang sudah memiliki booking tidak dapat dihapus dan sistem akan menolaknya. Untuk jadwal dengan riwayat pelanggan, gunakan tombol <strong>Tutup Jadwal</strong> agar jadwal tidak dapat dipesan lagi tanpa menghapus data booking.
            </div>
        </div>
        <div class="fp-modal-foot">
            <button type="button" class="admin-btn admin-btn-outline" data-modal-close>Batal</button>
            <form id="scheduleDeleteForm" method="POST" data-guard-submit>
                @csrf
                @method('DELETE')
                <button type="submit" class="admin-btn admin-btn-danger" id="scheduleDeleteConfirm">Ya, Hapus Jadwal</button>
            </form>
        </div>
    </div>
</div>

<script>
    (() => {
        const modal = document.getElementById('scheduleDeleteModal');
        const form = document.getElementById('scheduleDeleteForm');
        const target = document.getElementById('scheduleDeleteTarget');
        let lastFocused = null;

        const closeModal = () => {
            if (!modal || modal.hidden) return;
            modal.hidden = true;
            document.body.classList.remove('fp-modal-open');
            if (lastFocused) lastFocused.focus();
        };

        const openModal = (button) => {
            if (!modal || !form) return;
            lastFocused = button;
            form.setAttribute('action', button.dataset.deleteUrl);
            target.textContent = button.dataset.deleteLabel || 'jadwal ini';
            modal.hidden = false;
            document.body.classList.add('fp-modal-open');
            document.getElementById('scheduleDeleteConfirm').focus();
        };

        document.querySelectorAll('[data-delete-url]').forEach((button) => {
            button.addEventListener('click', () => openModal(button));
        });

        if (modal) {
            modal.querySelectorAll('[data-modal-close]').forEach((button) => {
                button.addEventListener('click', closeModal);
            });

            modal.addEventListener('click', (event) => {
                if (event.target === modal) closeModal();
            });
        }

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') closeModal();
        });

        const resetSubmittingState = () => {
            document.querySelectorAll('form[data-guard-submit]').forEach((submittedForm) => {
                submittedForm.dataset.submitting = '0';

                submittedForm.querySelectorAll('button[type="submit"]').forEach((button) => {
                    if (button.dataset.originalLabel) {
                        button.innerHTML = button.dataset.originalLabel;
                    }
                    button.disabled = false;
                    button.classList.remove('is-loading');
                });

                const scope = submittedForm.closest('[data-loading-scope]');
                if (scope) scope.classList.remove('is-loading');
            });
            closeModal();
        };

        // Anti double-click: form hanya boleh dikirim sekali sampai halaman berganti.
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

        // Kembalikan tombol saat pengguna memakai tombol Back (bfcache).
        window.addEventListener('pageshow', resetSubmittingState);
    })();
</script>
@endsection
