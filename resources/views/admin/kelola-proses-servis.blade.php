@extends('layouts.admin')

@section('title', 'Kelola Proses Servis')

@section('admin-content')
<div class="admin-page-header">
    <div>
        <div class="admin-eyebrow">Operasional</div>
        <h1 class="admin-page-title">Kelola Proses Servis</h1>
        <p class="admin-page-subtitle">Kelola booking masuk, pemeriksaan, diagnosis, estimasi, pengerjaan, sampai servis selesai.</p>
    </div>
</div>

<div class="admin-toolbar">
    <form action="{{ route('admin.service-process') }}" method="GET" class="admin-search">
        <input type="text" name="q" class="admin-input" value="{{ $search }}" placeholder="Cari Service ID, pelanggan, atau perangkat...">
        @if($statusFilter !== 'active')<input type="hidden" name="status" value="{{ $statusFilter }}">@endif
        <button type="submit" class="admin-btn admin-btn-primary">Cari</button>
    </form>
    <div class="admin-filter">
        <a class="admin-filter-link {{ $statusFilter === 'active' ? 'active' : '' }}" href="{{ route('admin.service-process', ['status' => 'active']) }}">Aktif</a>
        <a class="admin-filter-link {{ $statusFilter === 'waiting' ? 'active' : '' }}" href="{{ route('admin.service-process', ['status' => 'waiting']) }}">Menunggu</a>
        <a class="admin-filter-link {{ $statusFilter === 'completed' ? 'active' : '' }}" href="{{ route('admin.service-process', ['status' => 'completed']) }}">Selesai</a>
        <a class="admin-filter-link {{ $statusFilter === 'all' ? 'active' : '' }}" href="{{ route('admin.service-process', ['status' => 'all']) }}">Semua</a>
    </div>
</div>

<section class="admin-card">
    <div class="admin-card-header">
        <div>
            <h2 class="admin-card-title">Daftar Booking dan Proses Servis</h2>
            <p class="admin-card-description">Terima booking, pilih data untuk diperbarui, atau lanjutkan ke pembayaran.</p>
        </div>
        <span class="admin-badge badge-blue">{{ $bookings->total() }} data</span>
    </div>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr><th>Service ID</th><th>Pelanggan</th><th>Perangkat</th><th>Layanan</th><th>Status Servis</th><th>Pembayaran</th><th>Aksi</th></tr>
            </thead>
            <tbody>
                @forelse($bookings as $booking)
                    @php
                        $isPaid = $booking->payment_status === 'PAID';
                        $stateLabel = match ($booking->booking_status) {
                            'PENDING' => 'Menunggu',
                            'WAITING_VERIFICATION' => 'Perlu Verifikasi',
                            'CONFIRMED' => match ($booking->service_status) {
                                'DIAGNOSIS' => 'Diagnosis',
                                'PROCESS' => 'Dikerjakan',
                                default => 'Diterima',
                            },
                            'COMPLETED' => 'Selesai',
                            'REJECTED' => 'Ditolak',
                            'CANCELLED' => 'Dibatalkan',
                            default => $booking->booking_status,
                        };
                        $stateClass = in_array($booking->booking_status, ['PENDING', 'WAITING_VERIFICATION'], true)
                            ? 'badge-yellow'
                            : (in_array($booking->booking_status, ['REJECTED', 'CANCELLED'], true)
                                ? 'badge-red'
                                : ($booking->booking_status === 'COMPLETED' ? 'badge-green' : 'badge-blue'));
                    @endphp
                    <tr>
                        <td><span class="admin-id">{{ $booking->service_id_code }}</span></td>
                        <td><span class="admin-primary-text">{{ $booking->customer_name ?? '-' }}</span><div class="admin-muted">{{ $booking->phone_number ?? '' }}</div></td>
                        <td>{{ trim(($booking->device_type ?? '') . ' ' . ($booking->brand ?? '')) ?: '-' }}</td>
                        <td>{{ $booking->service_name ?? '-' }}</td>
                        <td><span class="admin-badge {{ $stateClass }}">{{ $stateLabel }}</span></td>
                        <td>
                            @if($isPaid)
                                <span class="admin-badge badge-green">Lunas</span>
                            @elseif($booking->booking_status === 'COMPLETED')
                                <span class="admin-badge badge-yellow">Belum Bayar</span>
                                @if((float) $booking->payment_amount > 0)
                                    <div class="admin-muted" style="margin-top:5px;">Rp {{ number_format((float) $booking->payment_amount, 0, ',', '.') }}</div>
                                @endif
                            @else
                                <span class="admin-badge badge-gray">{{ $booking->payment_method ?? 'Belum tercatat' }}</span>
                            @endif
                        </td>
                        <td>
                            @if(in_array($booking->booking_status, ['PENDING', 'WAITING_VERIFICATION'], true))
                                <form action="{{ route('admin.booking.verify', $booking->booking_id) }}" method="POST" class="js-admin-confirm">
                                    @csrf @method('PUT')
                                    <input type="hidden" name="_confirm_title" value="Terima Booking">
                                    <input type="hidden" name="_confirm_message" value="Booking ini akan diterima dan catatan servis akan disiapkan. Lanjutkan?">
                                    <input type="hidden" name="_confirm_button" value="Ya, Terima Booking">
                                    <input type="hidden" name="_confirm_tone" value="primary">
                                    <input type="hidden" name="_confirm_details" value="{{ $booking->service_id_code }}|{{ $booking->customer_name ?? '-' }}">
                                    <button type="submit" class="admin-btn admin-btn-success">Terima</button>
                                </form>
                            @elseif($booking->booking_status === 'CONFIRMED')
                                <button type="button" class="admin-btn admin-btn-soft js-select-service" data-service-code="{{ $booking->service_id_code }}">Kelola</button>
                            @elseif($booking->booking_status === 'COMPLETED' && !$isPaid)
                                <a href="{{ route('admin.payments', ['q' => $booking->service_id_code, 'status' => 'unpaid']) }}" class="admin-btn admin-btn-soft">Pembayaran</a>
                            @else
                                <span class="admin-muted">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7"><div class="admin-empty"><strong>Tidak ada data booking</strong>Data booking sesuai filter akan muncul di sini.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="admin-pagination">{{ $bookings->links() }}</div>
</section>

<section class="admin-card" id="serviceUpdateCard">
    <div class="admin-card-header">
        <div>
            <h2 class="admin-card-title">Update Diagnosis dan Proses Servis</h2>
            <p class="admin-card-description">Pilih booking yang sudah diterima. Data pelanggan dan metode pembayaran akan tampil otomatis.</p>
        </div>
    </div>

    @if($manageableBookings->isEmpty())
        <div class="admin-callout"><strong>i</strong><span>Belum ada booking yang siap dikelola. Terima booking terlebih dahulu dari daftar di atas.</span></div>
    @else
        <form action="{{ route('admin.booking.update') }}" method="POST" id="serviceUpdateForm">
            @csrf
            <div class="admin-form-grid">
                <div class="admin-form-group full">
                    <label class="admin-label" for="serviceIdSelect">Service ID</label>
                    <select name="service_id_code" id="serviceIdSelect" class="admin-select" required>
                        <option value="">Pilih booking yang akan dikelola</option>
                        @foreach($manageableBookings as $item)
                            @php
                                $storedNotes = (string) ($item->service_notes ?? '');
                                preg_match('/Estimasi pengerjaan:\s*(.+)/u', $storedNotes, $durationMatch);
                                $noteText = preg_replace('/Estimasi pengerjaan:\s*.+(?:\R|$)/u', '', $storedNotes);
                                $noteText = preg_replace('/^Catatan:\s*/u', '', trim((string) $noteText));
                            @endphp
                            <option value="{{ $item->service_id_code }}"
                                data-customer="{{ $item->customer_name }}"
                                data-device="{{ trim(($item->device_type ?? '') . ' ' . ($item->brand ?? '')) }}"
                                data-method="{{ $item->payment_method ?? '-' }}"
                                data-status="{{ $item->service_status ?? 'PENDING' }}"
                                data-diagnosis="{{ $item->diagnosis ?? '' }}"
                                data-duration="{{ $durationMatch[1] ?? '' }}"
                                data-estimated-cost="{{ $item->estimated_cost ?? '' }}"
                                data-final-cost="{{ $item->final_cost ?? '' }}"
                                data-notes="{{ $noteText }}"
                                {{ old('service_id_code') === $item->service_id_code ? 'selected' : '' }}>
                                {{ $item->service_id_code }} — {{ $item->customer_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('service_id_code')<span class="admin-help" style="color:#dc2626;">{{ $message }}</span>@enderror
                </div>

                <div class="admin-form-group">
                    <label class="admin-label">Pelanggan</label>
                    <div class="admin-input" id="selectedCustomer">—</div>
                </div>
                <div class="admin-form-group">
                    <label class="admin-label">Perangkat</label>
                    <div class="admin-input" id="selectedDevice">—</div>
                </div>
                <div class="admin-form-group full">
                    <label class="admin-label">Metode Pembayaran (otomatis)</label>
                    <div class="admin-input" id="selectedPaymentMethod">—</div>
                    <span class="admin-help">Metode pembayaran diambil dari pilihan pelanggan saat booking dan tidak perlu diinput ulang.</span>
                </div>
                <div class="admin-form-group full">
                    <label class="admin-label" for="serviceStatusSelect">Tahap Servis</label>
                    <select name="service_status" id="serviceStatusSelect" class="admin-select" required>
                        <option value="DIAGNOSIS" {{ old('service_status') === 'DIAGNOSIS' ? 'selected' : '' }}>Diagnosis</option>
                        <option value="PROCESS" {{ old('service_status') === 'PROCESS' ? 'selected' : '' }}>Sedang Dikerjakan</option>
                        <option value="COMPLETED" {{ old('service_status') === 'COMPLETED' ? 'selected' : '' }}>Selesai</option>
                    </select>
                </div>
                <div class="admin-form-group full">
                    <label class="admin-label" for="diagnosisInput">Hasil Diagnosis</label>
                    <textarea name="diagnosis" id="diagnosisInput" class="admin-textarea" rows="3" placeholder="Tuliskan hasil pemeriksaan perangkat...">{{ old('diagnosis') }}</textarea>
                </div>
                <div class="admin-form-group">
                    <label class="admin-label" for="estimatedDurationInput">Estimasi Pengerjaan</label>
                    <input type="text" name="estimated_duration" id="estimatedDurationInput" class="admin-input" value="{{ old('estimated_duration') }}" placeholder="Contoh: 2–3 hari kerja">
                </div>
                <div class="admin-form-group">
                    <label class="admin-label" for="estimatedCostInput">Estimasi Biaya (Rp)</label>
                    <input type="number" name="estimated_cost" id="estimatedCostInput" class="admin-input" min="0" max="9999999999.99" step="0.01" value="{{ old('estimated_cost') }}" placeholder="Contoh: 150000">
                </div>
                <div class="admin-form-group full">
                    <label class="admin-label" for="finalCostInput">Biaya Akhir (Rp)</label>
                    <input type="number" name="final_cost" id="finalCostInput" class="admin-input" min="1" max="9999999999.99" step="0.01" value="{{ old('final_cost') }}" placeholder="Wajib diisi saat servis selesai">
                    <span class="admin-help">Biaya akhir menjadi nominal tagihan pada halaman Pembayaran.</span>
                </div>
                <div class="admin-form-group full">
                    <label class="admin-label" for="serviceNotesInput">Catatan Servis</label>
                    <textarea name="service_notes" id="serviceNotesInput" class="admin-textarea" rows="3" placeholder="Catatan tambahan atau kendala pengerjaan...">{{ old('service_notes') }}</textarea>
                </div>
                <div class="admin-form-group full">
                    <button type="submit" id="serviceUpdateSubmitBtn" class="admin-btn admin-btn-primary" style="width:100%;">Simpan Perubahan Servis</button>
                </div>
            </div>
        </form>
    @endif
</section>

<div class="admin-confirm-overlay" id="adminConfirmModal" hidden aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="adminConfirmTitle" style="position:fixed;inset:0;z-index:9999;background:rgba(15,23,42,.56);display:none;align-items:center;justify-content:center;padding:18px;">
    <div style="width:min(100%,440px);background:#fff;border-radius:17px;padding:23px;box-shadow:0 25px 70px rgba(15,23,42,.25);">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:16px;">
            <h2 id="adminConfirmTitle" style="font-size:18px;font-weight:800;margin:0;color:#0f172a;">Konfirmasi</h2>
            <button type="button" id="adminConfirmClose" aria-label="Tutup" style="border:0;background:#f1f5f9;border-radius:8px;width:32px;height:32px;font-size:19px;cursor:pointer;">×</button>
        </div>
        <p id="adminConfirmMessage" style="font-size:13px;line-height:1.6;color:#64748b;margin:0 0 15px;"></p>
        <div id="adminConfirmDetails" style="background:#f8fafc;border:1px solid #eef2f7;border-radius:11px;padding:12px;margin-bottom:18px;font-size:12px;color:#334155;line-height:1.8;"></div>
        <div style="display:flex;justify-content:flex-end;gap:9px;">
            <button type="button" id="adminConfirmCancel" class="admin-btn admin-btn-outline">Batal</button>
            <button type="button" id="adminConfirmSubmit" class="admin-btn admin-btn-primary">Ya, Lanjutkan</button>
        </div>
    </div>
</div>

<style>
    .admin-confirm-overlay:not([hidden]){display:flex!important}
    body.admin-confirm-open{overflow:hidden}
</style>
<script>
(() => {
    const select = document.getElementById('serviceIdSelect');
    const status = document.getElementById('serviceStatusSelect');
    const finalCost = document.getElementById('finalCostInput');
    const submitButton = document.getElementById('serviceUpdateSubmitBtn');
    const form = document.getElementById('serviceUpdateForm');

    function updateFormFromSelection(populate = true) {
        if (!select) return;
        const option = select.options[select.selectedIndex];
        const setText = (id, value) => {
            const element = document.getElementById(id);
            if (element) element.textContent = value || '—';
        };
        if (!option || !option.value) {
            setText('selectedCustomer', '—');
            setText('selectedDevice', '—');
            setText('selectedPaymentMethod', '—');
            return;
        }
        setText('selectedCustomer', option.dataset.customer);
        setText('selectedDevice', option.dataset.device);
        setText('selectedPaymentMethod', option.dataset.method);
        if (populate) {
            if (status) status.value = ['DIAGNOSIS', 'PROCESS', 'COMPLETED'].includes(option.dataset.status) ? option.dataset.status : 'DIAGNOSIS';
            document.getElementById('diagnosisInput').value = option.dataset.diagnosis || '';
            document.getElementById('estimatedDurationInput').value = option.dataset.duration || '';
            document.getElementById('estimatedCostInput').value = option.dataset.estimatedCost || '';
            if (finalCost) finalCost.value = option.dataset.finalCost || '';
            document.getElementById('serviceNotesInput').value = option.dataset.notes || '';
        }
        updateStatusRequirement();
    }

    function updateStatusRequirement() {
        if (!status || !finalCost) return;
        const completed = status.value === 'COMPLETED';
        finalCost.required = completed;
        finalCost.placeholder = completed ? 'Masukkan biaya akhir' : 'Diisi saat status Selesai';
        if (submitButton) submitButton.textContent = completed ? 'Selesaikan Servis' : 'Simpan Perubahan Servis';
    }

    if (select) {
        select.addEventListener('change', () => updateFormFromSelection(true));
        if (select.value) updateFormFromSelection({{ old('service_id_code') ? 'false' : 'true' }});
    }
    if (status) status.addEventListener('change', updateStatusRequirement);
    updateStatusRequirement();

    document.querySelectorAll('.js-select-service').forEach((button) => {
        button.addEventListener('click', () => {
            if (!select) return;
            select.value = button.dataset.serviceCode;
            updateFormFromSelection(true);
            document.getElementById('serviceUpdateCard')?.scrollIntoView({behavior:'smooth', block:'start'});
        });
    });

    const modal = document.getElementById('adminConfirmModal');
    const modalTitle = document.getElementById('adminConfirmTitle');
    const modalMessage = document.getElementById('adminConfirmMessage');
    const modalDetails = document.getElementById('adminConfirmDetails');
    const modalSubmit = document.getElementById('adminConfirmSubmit');
    let pendingForm = null;
    let approvedForm = null;
    let previousFocus = null;

    function openModal(targetForm, title, message, buttonText, details) {
        pendingForm = targetForm;
        previousFocus = document.activeElement;
        modalTitle.textContent = title;
        modalMessage.textContent = message;
        modalSubmit.textContent = buttonText;
        modalDetails.replaceChildren();
        (details || []).forEach(([label, value]) => {
            if (!value) return;
            const row = document.createElement('div');
            row.style.display = 'flex';
            row.style.justifyContent = 'space-between';
            row.style.gap = '15px';
            const left = document.createElement('span');
            left.style.color = '#64748b';
            left.textContent = label;
            const right = document.createElement('strong');
            right.style.textAlign = 'right';
            right.textContent = value;
            row.append(left, right);
            modalDetails.appendChild(row);
        });
        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('admin-confirm-open');
        modalSubmit.focus();
    }

    function closeModal() {
        if (!modal || modal.hidden) return;
        modal.hidden = true;
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('admin-confirm-open');
        pendingForm = null;
        previousFocus?.focus?.();
    }

    document.querySelectorAll('form.js-admin-confirm').forEach((targetForm) => {
        targetForm.addEventListener('submit', (event) => {
            if (approvedForm === targetForm) { approvedForm = null; return; }
            event.preventDefault();
            const values = (targetForm.querySelector('[name="_confirm_details"]')?.value || '').split('|');
            openModal(
                targetForm,
                targetForm.querySelector('[name="_confirm_title"]')?.value || 'Konfirmasi Tindakan',
                targetForm.querySelector('[name="_confirm_message"]')?.value || 'Lanjutkan tindakan ini?',
                targetForm.querySelector('[name="_confirm_button"]')?.value || 'Ya, Lanjutkan',
                [['Service ID', values[0]], ['Pelanggan', values[1]]]
            );
        });
    });

    if (form) {
        form.addEventListener('submit', (event) => {
            if (approvedForm === form) { approvedForm = null; return; }
            if (!status || status.value !== 'COMPLETED') return;
            event.preventDefault();
            const option = select?.options[select.selectedIndex];
            const amount = finalCost?.value ? 'Rp ' + new Intl.NumberFormat('id-ID').format(Number(finalCost.value)) : '—';
            openModal(form, 'Selesaikan Servis', 'Pastikan pengerjaan sudah selesai dan biaya akhir sudah benar.', 'Ya, Selesaikan Servis', [
                ['Service ID', option?.value || '—'],
                ['Pelanggan', option?.dataset.customer || '—'],
                ['Biaya Akhir', amount],
            ]);
        });
    }

    modalSubmit?.addEventListener('click', () => {
        if (!pendingForm) return;
        const targetForm = pendingForm;
        pendingForm = null;
        approvedForm = targetForm;
        closeModal();
        if (targetForm.requestSubmit) targetForm.requestSubmit();
        else HTMLFormElement.prototype.submit.call(targetForm);
    });
    document.getElementById('adminConfirmCancel')?.addEventListener('click', closeModal);
    document.getElementById('adminConfirmClose')?.addEventListener('click', closeModal);
    modal?.addEventListener('click', (event) => { if (event.target === modal) closeModal(); });
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape') closeModal(); });
})();
</script>
@endsection
