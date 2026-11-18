@php
    // Variabel diwarisi dari halaman cek status: $booking, $payment, $methodLabel, $paymentAmount.
    $successMethodLabel = $methodLabel ?? match ($payment?->payment_method) {
        'CASH' => 'Cash / Tunai',
        'QRIS' => 'QRIS',
        'TRANSFER' => 'Transfer Bank',
        default => 'Belum dipilih',
    };
    $successAmount = $paymentAmount ?? ($payment && is_numeric($payment->amount) ? (float) $payment->amount : 0);
@endphp

<style>
    .payment-success {
        position: relative;
        overflow: hidden;
        background: linear-gradient(180deg, #f0fdf4 0%, #ffffff 46%);
        border: 1px solid #bbf7d0;
        border-radius: 16px;
        padding: 30px 24px 26px;
        margin-bottom: 18px;
        text-align: center;
        box-shadow: 0 10px 26px rgba(22, 101, 52, .07);
    }

    .payment-success::before {
        content: '';
        position: absolute;
        inset: 0 0 auto;
        height: 4px;
        background: linear-gradient(90deg, #16a34a, #4ade80);
    }

    .payment-success-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 68px;
        height: 68px;
        margin-bottom: 14px;
        border-radius: 50%;
        background: #dcfce7;
        border: 2px solid #86efac;
        color: #15803d;
    }

    .payment-success-eyebrow {
        color: #15803d;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: .1em;
        margin-bottom: 6px;
    }

    .payment-success-title {
        color: #14532d;
        font-size: clamp(22px, 3.4vw, 28px);
        font-weight: 850;
        letter-spacing: -.5px;
        line-height: 1.25;
        margin-bottom: 8px;
    }

    .payment-success-subtitle {
        color: #4b5563;
        font-size: 13px;
        line-height: 1.65;
        max-width: 520px;
        margin: 0 auto 14px;
    }

    .payment-success-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #16a34a;
        color: #fff;
        border-radius: 999px;
        font-size: 10.5px;
        font-weight: 850;
        letter-spacing: .08em;
        padding: 7px 14px;
        margin-bottom: 20px;
    }

    .payment-success-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
        text-align: left;
        max-width: 720px;
        margin: 0 auto 22px;
    }

    .payment-success-item {
        min-width: 0;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 12px 14px;
    }

    .payment-success-item.full {
        grid-column: 1 / -1;
    }

    .payment-success-item .field-label {
        margin-bottom: 4px;
    }

    .payment-success-value {
        color: #111827;
        font-size: 13px;
        font-weight: 750;
        overflow-wrap: anywhere;
    }

    .payment-success-value.amount {
        color: #15803d;
        font-size: 18px;
        font-weight: 850;
    }

    .payment-success-value.id {
        color: #2563eb;
        font-family: var(--font-mono, ui-monospace, monospace);
    }

    .payment-success-value.status {
        color: #15803d;
        letter-spacing: .04em;
    }

    .payment-success-actions {
        display: flex;
        justify-content: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    @media (max-width: 680px) {
        .payment-success {
            padding: 24px 16px 22px;
        }

        .payment-success-grid {
            grid-template-columns: 1fr;
        }

        .payment-success-item.full {
            grid-column: auto;
        }

        .payment-success-actions .btn-primary {
            width: 100%;
        }
    }
</style>

<section class="payment-success" id="pembayaranBerhasil" aria-label="Pembayaran berhasil">
    <div class="payment-success-icon" aria-hidden="true">
        <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"
            stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 6 9 17l-5-5" />
        </svg>
    </div>

    <div class="payment-success-eyebrow">FIXPOINT SERVICE</div>
    <h2 class="payment-success-title">Pembayaran Berhasil</h2>
    <p class="payment-success-subtitle">
        Pembayaran Anda telah diverifikasi Admin. Terima kasih telah menyelesaikan pembayaran servis perangkat Anda.
    </p>

    <div class="payment-success-badge">BERHASIL</div>

    <div class="payment-success-grid">
        <div class="payment-success-item full">
            <div class="field-label">NOMOR SERVICE ID</div>
            <div class="payment-success-value id">{{ $booking->service_id_code }}</div>
        </div>
        <div class="payment-success-item">
            <div class="field-label">Nama Pelanggan</div>
            <div class="payment-success-value">{{ $booking->customer?->name ?? '-' }}</div>
        </div>
        <div class="payment-success-item">
            <div class="field-label">Total Pembayaran</div>
            <div class="payment-success-value amount">Rp {{ number_format($successAmount, 0, ',', '.') }}</div>
        </div>
        <div class="payment-success-item">
            <div class="field-label">Metode Pembayaran</div>
            <div class="payment-success-value">{{ $successMethodLabel }}</div>
        </div>
        <div class="payment-success-item">
            <div class="field-label">Tanggal Pembayaran</div>
            <div class="payment-success-value">
                {{ $payment?->payment_date?->locale('id')->translatedFormat('d F Y, H:i') ?? '-' }}
            </div>
        </div>
        <div class="payment-success-item full">
            <div class="field-label">Status Pembayaran</div>
            <div class="payment-success-value status">BERHASIL</div>
        </div>
    </div>

    <div class="payment-success-actions">
        <a href="{{ route('cek-status.show', ['code' => $booking->service_id_code]) }}" class="btn-primary">
            Kembali ke Status Servis
        </a>
    </div>
</section>
