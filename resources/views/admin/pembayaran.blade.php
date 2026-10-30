@extends('layouts.admin')

@section('title', 'Pembayaran')

@section('admin-content')
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
            @if($status !== 'all')<input type="hidden" name="status" value="{{ $status }}">@endif
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
            <thead><tr><th>Service ID</th><th>Pelanggan</th><th>Perangkat</th><th>Metode</th><th>Nominal</th><th>Status Pembayaran</th><th>Aksi</th></tr></thead>
            <tbody>
                @forelse($payments as $payment)
                    @php
                        $canMarkPaid = $payment->booking_status === 'COMPLETED'
                            && $payment->payment_status !== 'PAID'
                            && in_array($payment->payment_method, ['CASH', 'QRIS'], true)
                            && (float) $payment->amount > 0;
                    @endphp
                    <tr>
                        <td><span class="admin-id">{{ $payment->service_id_code }}</span></td>
                        <td><span class="admin-primary-text">{{ $payment->customer_name ?? '-' }}</span></td>
                        <td>{{ trim(($payment->device_type ?? '') . ' ' . ($payment->brand ?? '')) ?: '-' }}</td>
                        <td><span class="admin-badge {{ $payment->payment_method === 'CASH' ? 'badge-yellow' : 'badge-blue' }}">{{ $payment->payment_method ?: '—' }}</span></td>
                        <td>
                            @if((float) $payment->amount > 0)
                                <strong>Rp {{ number_format((float) $payment->amount, 0, ',', '.') }}</strong>
                            @else
                                <span class="admin-muted">Tagihan belum ditetapkan</span>
                            @endif
                        </td>
                        <td><span class="admin-badge {{ $payment->payment_status === 'PAID' ? 'badge-green' : 'badge-yellow' }}">{{ $payment->payment_status === 'PAID' ? 'Lunas' : 'Belum Bayar' }}</span></td>
                        <td>
                            @if($canMarkPaid)
                                <form action="{{ route('admin.booking.markPaid', $payment->booking_id) }}" method="POST" class="js-payment-confirm">
                                    @csrf @method('PUT')
                                    <input type="hidden" name="service_id" value="{{ $payment->service_id_code }}">
                                    <input type="hidden" name="customer" value="{{ $payment->customer_name ?? '-' }}">
                                    <input type="hidden" name="method" value="{{ $payment->payment_method }}">
                                    <input type="hidden" name="amount" value="Rp {{ number_format((float) $payment->amount, 0, ',', '.') }}">
                                    <button type="submit" class="admin-btn admin-btn-success">Konfirmasi Lunas</button>
                                </form>
                            @elseif($payment->payment_status === 'PAID')
                                <span class="admin-muted">Terverifikasi</span>
                            @else
                                <span class="admin-muted">Menunggu servis selesai</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7"><div class="admin-empty"><strong>Data pembayaran tidak ditemukan</strong>Data transaksi akan muncul setelah pelanggan membuat booking.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="admin-pagination">{{ $payments->links() }}</div>
</section>

<div class="admin-confirm-overlay" id="paymentConfirmModal" hidden aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="paymentConfirmTitle" style="position:fixed;inset:0;z-index:9999;background:rgba(15,23,42,.56);align-items:center;justify-content:center;padding:18px;">
    <div style="width:min(100%,430px);background:#fff;border-radius:17px;padding:23px;box-shadow:0 25px 70px rgba(15,23,42,.25);">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:13px;">
            <h2 id="paymentConfirmTitle" style="font-size:18px;font-weight:800;margin:0;">Konfirmasi Pembayaran</h2>
            <button type="button" id="paymentConfirmClose" aria-label="Tutup" style="border:0;background:#f1f5f9;border-radius:8px;width:32px;height:32px;font-size:19px;cursor:pointer;">×</button>
        </div>
        <p style="font-size:13px;line-height:1.6;color:#64748b;margin:0 0 14px;">Pastikan pembayaran pelanggan sudah benar-benar diterima sebelum mengonfirmasi transaksi menjadi lunas.</p>
        <div id="paymentConfirmInfo" style="background:#f8fafc;border:1px solid #eef2f7;border-radius:11px;padding:12px;margin-bottom:18px;font-size:12px;line-height:1.9;"></div>
        <div style="display:flex;justify-content:flex-end;gap:9px;">
            <button type="button" id="paymentConfirmCancel" class="admin-btn admin-btn-outline">Batal</button>
            <button type="button" id="paymentConfirmSubmit" class="admin-btn admin-btn-success">Ya, Konfirmasi Lunas</button>
        </div>
    </div>
</div>
<style>.admin-confirm-overlay:not([hidden]){display:flex!important}body.admin-confirm-open{overflow:hidden}</style>
<script>
(() => {
    const modal = document.getElementById('paymentConfirmModal');
    const info = document.getElementById('paymentConfirmInfo');
    const submit = document.getElementById('paymentConfirmSubmit');
    let pendingForm = null;
    let approvedForm = null;
    let oldFocus = null;

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
        fields.forEach(([label, value]) => {
            const row = document.createElement('div');
            row.style.display = 'flex'; row.style.justifyContent = 'space-between'; row.style.gap = '12px';
            const left = document.createElement('span'); left.style.color = '#64748b'; left.textContent = label;
            const right = document.createElement('strong'); right.textContent = value || '—'; right.style.textAlign = 'right';
            row.append(left, right); info.appendChild(row);
        });
        modal.hidden = false; modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('admin-confirm-open'); submit.focus();
    }
    function close() {
        if (modal.hidden) return;
        modal.hidden = true; modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('admin-confirm-open'); pendingForm = null;
        oldFocus?.focus?.();
    }
    document.querySelectorAll('.js-payment-confirm').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (approvedForm === form) { approvedForm = null; return; }
            event.preventDefault(); open(form);
        });
    });
    submit.addEventListener('click', () => {
        if (!pendingForm) return;
        const form = pendingForm; pendingForm = null; approvedForm = form; close();
        if (form.requestSubmit) form.requestSubmit(); else HTMLFormElement.prototype.submit.call(form);
    });
    document.getElementById('paymentConfirmCancel').addEventListener('click', close);
    document.getElementById('paymentConfirmClose').addEventListener('click', close);
    modal.addEventListener('click', (event) => { if (event.target === modal) close(); });
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape') close(); });
})();
</script>
@endsection
