@extends('layouts.admin')

@section('title', 'Pembayaran')

@section('admin-content')

<style>
    .qris-proof-link { display:inline-flex; flex-direction:column; align-items:center; gap:5px; color:#2563eb; font-size:10px; font-weight:800; text-decoration:none; }
    .qris-proof-thumb { width:88px; height:62px; object-fit:cover; border:1px solid #dbeafe; border-radius:9px; background:#f1f5f9; display:block; }
    .qris-reupload-form { margin-top:6px; }
    .cash-field { margin-bottom:10px; padding:10px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; }
    .cash-field label { display:block; font-size:11px; font-weight:800; color:#475569; margin-bottom:6px; }
    .cash-input { display:flex; align-items:center; gap:6px; background:#fff; border:1px solid #cbd5e1; border-radius:8px; padding:7px 9px; }
    .cash-input span { font-size:12px; font-weight:800; color:#64748b; }
    .cash-input input { width:100%; border:0; outline:none; background:transparent; font:inherit; font-size:13px; font-weight:800; color:#111827; }
    .cash-change { margin-top:7px; font-size:11px; font-weight:700; color:#166534; }
    .cash-change.is-short { color:#b91c1c; }
    .payment-actions { display:flex; flex-direction:column; gap:8px; min-width:0; }
    .reject-form { display:flex; flex-direction:column; gap:5px; min-width:0; padding-top:8px; border-top:1px dashed #e2e8f0; }
    .reject-note { width:100%; min-width:0; border:1px solid #e2e8f0; border-radius:8px; padding:7px 9px; font:inherit; font-size:11px; color:#0f172a; background:#fff; outline:none; }
    .reject-note:focus { border-color:#fca5a5; box-shadow:0 0 0 3px rgba(248,113,113,.12); }
    .reject-form .admin-btn { width:100%; }
    .reject-hint { font-size:10px; line-height:1.45; color:#94a3b8; }
    .receipt-link { display:inline-flex; align-items:center; justify-content:center; gap:6px; padding:8px 12px; border:1px solid #cbd5e1; border-radius:8px; background:#fff; color:#0f172a; font-size:12px; font-weight:800; text-decoration:none; }
    .receipt-link:hover { border-color:#94a3b8; background:#f8fafc; }
    @media(max-width:700px) { .qris-proof-thumb { width:76px; height:54px; } }
</style>

<div class="admin-page-header">
    <div>
        <div class="admin-eyebrow">Transaksi</div>
        <h1 class="admin-page-title">Pembayaran</h1>
        <p class="admin-page-subtitle">Pantau nominal tagihan, metode pembayaran pilihan pelanggan, dan status pelunasan.</p>
    </div>
</div>

<section class="admin-card">
    <div class="admin-toolbar">
        <form action="{{ route('admin.payments') }}" method="GET" class="admin-search">
            <input type="text" name="q" class="admin-input" value="{{ $search }}" placeholder="Cari Service ID atau nama pelanggan...">
            @if($status !== 'all')
                <input type="hidden" name="status" value="{{ $status }}">
            @endif
            <button type="submit" class="admin-btn admin-btn-primary">Cari</button>
        </form>

        <div class="admin-filter">
            <a href="{{ route('admin.payments', ['status' => 'all']) }}" class="admin-filter-link {{ $status === 'all' ? 'active' : '' }}">Semua</a>
            <a href="{{ route('admin.payments', ['status' => 'unpaid']) }}" class="admin-filter-link {{ $status === 'unpaid' ? 'active' : '' }}">Belum Bayar</a>
            <a href="{{ route('admin.payments', ['status' => 'paid']) }}" class="admin-filter-link {{ $status === 'paid' ? 'active' : '' }}">Lunas</a>
        </div>
    </div>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Service ID</th>
                    <th>Pelanggan</th>
                    <th>Perangkat</th>
                    <th>Metode</th>
                    <th>Nominal</th>
                    <th>Bukti Pembayaran</th>
                    <th>Status Pembayaran</th>
                    <th>Aksi</th>
                </tr>
            </thead>

            <tbody>
                @forelse($payments as $payment)
                    @php
                        $hasValidPaymentMethod = in_array(
                            $payment->payment_method,
                            ['CASH', 'QRIS'],
                            true
                        );

                        // Cash dapat dikonfirmasi langsung oleh Admin.
                        // QRIS harus memiliki bukti pembayaran terlebih dahulu.
                        $hasPaymentProof = $payment->payment_method === 'CASH'
                            || filled($payment->payment_proof);

                        // QRIS hanya dapat dikonfirmasi setelah pelanggan mengunggah bukti
                        // dan statusnya sedang menunggu verifikasi Admin.
                        $qrisReadyForVerification = $payment->payment_method !== 'QRIS'
                            || ($payment->payment_status === 'WAITING_VERIFICATION' && filled($payment->payment_proof));

                        $canMarkPaid = $payment->booking_status === 'COMPLETED'
                            && $payment->payment_status !== 'PAID'
                            && $hasValidPaymentMethod
                            && $hasPaymentProof
                            && $qrisReadyForVerification
                            && (float) $payment->amount > 0;

                        $paymentStatusLabel = match ($payment->payment_status) {
                            'PAID' => 'Lunas',
                            'WAITING_VERIFICATION' => 'Menunggu Verifikasi',
                            default => ($payment->payment_method === 'QRIS' && filled($payment->payment_proof))
                                ? 'Perlu Upload Ulang'
                                : 'Belum Bayar',
                        };

                        $paymentStatusClass = match ($payment->payment_status) {
                            'PAID' => 'badge-green',
                            'WAITING_VERIFICATION' => 'badge-blue',
                            default => 'badge-yellow',
                        };

                        $canRequestReupload = $payment->payment_method === 'QRIS'
                            && $payment->payment_status === 'WAITING_VERIFICATION'
                            && filled($payment->payment_proof)
                            && $payment->booking_status === 'COMPLETED';

                        $hasAction = $canMarkPaid || $canRequestReupload;
                    @endphp

                    <tr>
                        <td>
                            <span class="admin-id">{{ $payment->service_id_code }}</span>
                        </td>

                        <td>
                            <span class="admin-primary-text">{{ $payment->customer_name ?? '-' }}</span>
                        </td>

                        <td>
                            {{ trim(($payment->device_type ?? '') . ' ' . ($payment->brand ?? '')) ?: '-' }}
                        </td>

                        <td>
                            <span class="admin-badge {{ $payment->payment_method === 'CASH' ? 'badge-yellow' : 'badge-blue' }}">
                                {{ $payment->payment_method ?: '—' }}
                            </span>
                        </td>

                        <td>
                            @if((float) $payment->amount > 0)
                                <strong>Rp {{ number_format((float) $payment->amount, 0, ',', '.') }}</strong>
                            @else
                                <span class="admin-muted">Tagihan belum ditetapkan</span>
                            @endif
                        </td>

                        {{-- Bukti pembayaran hanya diperlukan untuk QRIS --}}
                        <td>
                            @if ($payment->payment_method === 'QRIS')
                                @if ($payment->payment_proof)
                                    <a href="{{ route('admin.payments.proof', $payment->payment_id) }}"
                                       target="_blank"
                                       rel="noopener"
                                       class="qris-proof-link">
                                        <img src="{{ route('admin.payments.proof', $payment->payment_id) }}"
                                             alt="Bukti QRIS {{ $payment->service_id_code }}"
                                             class="qris-proof-thumb"
                                             loading="lazy">
                                        <span>Lihat bukti</span>
                                    </a>
                                @else
                                    <span class="admin-muted">Belum diunggah</span>
                                @endif
                            @else
                                <span class="admin-muted">—</span>
                            @endif
                        </td>

                        <td>
                            <span class="admin-badge {{ $paymentStatusClass }}">
                                {{ $paymentStatusLabel }}
                            </span>
                        </td>

                        <td>
                            <div class="payment-actions">
                            @if($canMarkPaid)
                                <form action="{{ route('admin.booking.markPaid', $payment->booking_id) }}"
                                      method="POST"
                                      class="js-payment-confirm">
                                    @csrf
                                    @method('PUT')

                                    <input type="hidden" name="service_id" value="{{ $payment->service_id_code }}">
                                    <input type="hidden" name="customer" value="{{ $payment->customer_name ?? '-' }}">
                                    <input type="hidden" name="method" value="{{ $payment->payment_method }}">
                                    <input type="hidden" name="amount" value="Rp {{ number_format((float) $payment->amount, 0, ',', '.') }}">

                                    @if($payment->payment_method === 'CASH')
                                        <div class="cash-field" data-cash-field data-total="{{ (float) $payment->amount }}">
                                            <label for="cashReceived{{ $payment->payment_id }}">Uang tunai diterima</label>
                                            <div class="cash-input">
                                                <span>Rp</span>
                                                <input type="number"
                                                       name="cash_received"
                                                       id="cashReceived{{ $payment->payment_id }}"
                                                       inputmode="numeric"
                                                       min="{{ (float) $payment->amount }}"
                                                       step="1000"
                                                       placeholder="0"
                                                       autocomplete="off"
                                                       required>
                                            </div>
                                            <div class="cash-change" data-cash-change>
                                                Kembalian: Rp 0
                                            </div>
                                        </div>
                                    @endif

                                    <button type="submit" class="admin-btn admin-btn-success">
                                        Konfirmasi Lunas
                                    </button>
                                </form>
                            @endif

                            @if($canRequestReupload)
                                <form action="{{ route('admin.payments.request-reupload', $payment->payment_id) }}"
                                      method="POST"
                                      class="qris-reupload-form reject-form">
                                    @csrf
                                    <label for="rejectNote{{ $payment->payment_id }}" class="reject-hint">
                                        Tolak bukti? Pelanggan diminta unggah ulang.
                                    </label>
                                    <input type="text"
                                           name="note"
                                           id="rejectNote{{ $payment->payment_id }}"
                                           class="reject-note"
                                           maxlength="200"
                                           placeholder="Catatan singkat (opsional)"
                                           autocomplete="off">
                                    <button type="submit" class="admin-btn admin-btn-danger">
                                        Tolak Bukti
                                    </button>
                                </form>
                            @endif

                            @unless($hasAction)
                                @if($payment->payment_status === 'PAID')
                                    <span class="admin-muted">Terverifikasi</span>
                                @elseif($payment->payment_method === 'QRIS' && empty($payment->payment_proof))
                                    <span class="admin-muted">Menunggu bukti pembayaran</span>
                                @elseif($payment->payment_method === 'QRIS' && $payment->payment_status === 'UNPAID' && filled($payment->payment_proof))
                                    <span class="admin-muted">Menunggu bukti pengganti</span>
                                @else
                                    <span class="admin-muted">Menunggu servis selesai</span>
                                @endif
                            @endunless

                            @if($payment->payment_status === 'PAID')
                                <a href="{{ route('admin.payments.receipt', $payment->payment_id) }}"
                                   target="_blank"
                                   rel="noopener"
                                   class="receipt-link">
                                    Cetak Struk
                                </a>
                            @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">
                            <div class="admin-empty">
                                <strong>Data pembayaran tidak ditemukan</strong>
                                Data transaksi akan muncul setelah pelanggan membuat booking.
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="admin-pagination">
        {{ $payments->links() }}
    </div>
</section>

<div class="admin-confirm-overlay"
     id="paymentConfirmModal"
     hidden
     aria-hidden="true"
     role="dialog"
     aria-modal="true"
     aria-labelledby="paymentConfirmTitle"
     style="position:fixed;inset:0;z-index:9999;background:rgba(15,23,42,.56);align-items:center;justify-content:center;padding:18px;">

    <div style="width:min(100%,430px);background:#fff;border-radius:17px;padding:23px;box-shadow:0 25px 70px rgba(15,23,42,.25);">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:13px;">
            <h2 id="paymentConfirmTitle" style="font-size:18px;font-weight:800;margin:0;">
                Konfirmasi Pembayaran
            </h2>

            <button type="button"
                    id="paymentConfirmClose"
                    aria-label="Tutup"
                    style="border:0;background:#f1f5f9;border-radius:8px;width:32px;height:32px;font-size:19px;cursor:pointer;">
                ×
            </button>
        </div>

        <p style="font-size:13px;line-height:1.6;color:#64748b;margin:0 0 14px;">
            Pastikan pembayaran pelanggan sudah benar-benar diterima sebelum mengonfirmasi transaksi menjadi lunas.
        </p>

        <div id="paymentConfirmInfo"
             style="background:#f8fafc;border:1px solid #eef2f7;border-radius:11px;padding:12px;margin-bottom:18px;font-size:12px;line-height:1.9;">
        </div>

        <div style="display:flex;justify-content:flex-end;gap:9px;">
            <button type="button" id="paymentConfirmCancel" class="admin-btn admin-btn-outline">
                Batal
            </button>

            <button type="button" id="paymentConfirmSubmit" class="admin-btn admin-btn-success">
                Ya, Konfirmasi Lunas
            </button>
        </div>
    </div>
</div>

<style>
    .admin-confirm-overlay:not([hidden]) {
        display: flex !important;
    }

    body.admin-confirm-open {
        overflow: hidden;
    }
</style>

<script>
(() => {
    const modal = document.getElementById('paymentConfirmModal');
    const info = document.getElementById('paymentConfirmInfo');
    const submit = document.getElementById('paymentConfirmSubmit');

    let pendingForm = null;
    let approvedForm = null;
    let oldFocus = null;

    const rupiah = (value) => 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.max(0, Math.round(value)));

    // Hitung kembalian langsung di form konfirmasi cash agar admin tahu
    // nominal yang harus dikembalikan sebelum menekan tombol konfirmasi.
    document.querySelectorAll('[data-cash-field]').forEach((field) => {
        const input = field.querySelector('[name="cash_received"]');
        const output = field.querySelector('[data-cash-change]');
        const total = Number(field.dataset.total || 0);

        if (!input || !output) return;

        const render = () => {
            if (input.value === '') {
                output.textContent = 'Kembalian: Rp 0';
                output.classList.remove('is-short');
                return;
            }

            const received = Number(input.value);

            if (received >= total) {
                output.textContent = 'Kembalian: ' + rupiah(received - total);
                output.classList.remove('is-short');
            } else {
                output.textContent = 'Kurang: ' + rupiah(total - received);
                output.classList.add('is-short');
            }
        };

        input.addEventListener('input', render);
        render();
    });

    function open(form) {
        pendingForm = form;
        oldFocus = document.activeElement;

        info.replaceChildren();

        const fields = [
            ['Service ID', form.querySelector('[name="service_id"]')?.value],
            ['Pelanggan', form.querySelector('[name="customer"]')?.value],
            ['Metode Pembayaran', form.querySelector('[name="method"]')?.value],
            ['Total Pembayaran', form.querySelector('[name="amount"]')?.value],
        ];

        const cashInput = form.querySelector('[name="cash_received"]');

        if (cashInput) {
            const total = Number(cashInput.closest('[data-cash-field]')?.dataset.total || 0);
            const received = Number(cashInput.value || 0);

            fields.push(['Uang Tunai Diterima', rupiah(received)]);
            fields.push([
                'Kembalian',
                received >= total ? rupiah(received - total) : 'Kurang ' + rupiah(total - received),
            ]);
        }

        fields.forEach(([label, value]) => {
            const row = document.createElement('div');
            row.style.display = 'flex';
            row.style.justifyContent = 'space-between';
            row.style.gap = '12px';

            const left = document.createElement('span');
            left.style.color = '#64748b';
            left.textContent = label;

            const right = document.createElement('strong');
            right.textContent = value || '—';
            right.style.textAlign = 'right';

            row.append(left, right);
            info.appendChild(row);
        });

        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('admin-confirm-open');
        submit.focus();
    }

    function close() {
        if (modal.hidden) return;

        modal.hidden = true;
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('admin-confirm-open');

        pendingForm = null;
        oldFocus?.focus?.();
    }

    document.querySelectorAll('.js-payment-confirm').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (approvedForm === form) {
                approvedForm = null;
                return;
            }

            event.preventDefault();
            open(form);
        });
    });

    submit.addEventListener('click', () => {
        if (!pendingForm) return;

        const form = pendingForm;
        pendingForm = null;
        approvedForm = form;

        close();

        if (form.requestSubmit) {
            form.requestSubmit();
        } else {
            HTMLFormElement.prototype.submit.call(form);
        }
    });

    document.getElementById('paymentConfirmCancel').addEventListener('click', close);
    document.getElementById('paymentConfirmClose').addEventListener('click', close);

    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            close();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            close();
        }
    });
})();
</script>
@endsection