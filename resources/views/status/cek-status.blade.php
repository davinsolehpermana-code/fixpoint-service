@extends('layouts.app')

@section('title', 'Cek Status Servis')

@section('content')
    @php
        $hasBooking = isset($booking);
        $payment = $payment ?? ($hasBooking ? $booking->payment : null);
        $serviceRecord = $serviceRecord ?? null;

        $isPaid =
            $payment &&
            $payment->payment_status === 'PAID' &&
            in_array($payment->payment_method, ['CASH', 'QRIS'], true) &&
            is_numeric($payment->amount) &&
            (float) $payment->amount > 0;

        $bookingStatus = $hasBooking ? $booking->status : null;
        $recordStatus = $serviceRecord->service_status ?? null;

        $statusText = 'Menunggu Konfirmasi';
        $statusClass = 'status-pending';

        if ($bookingStatus === 'WAITING_VERIFICATION') {
            $statusText = 'Menunggu Verifikasi Booking';
            $statusClass = 'status-waiting';
        } elseif ($bookingStatus === 'CONFIRMED') {
            if ($recordStatus === 'DIAGNOSIS') {
                $statusText = 'Sedang Diagnosis';
                $statusClass = 'status-diagnosis';
            } elseif ($recordStatus === 'PROCESS') {
                $statusText = 'Sedang Dikerjakan';
                $statusClass = 'status-process';
            } elseif ($recordStatus === 'COMPLETED') {
                $statusText = 'Pemeriksaan Selesai';
                $statusClass = 'status-completed';
            } else {
                $statusText = 'Booking Dikonfirmasi';
                $statusClass = 'status-confirmed';
            }
        } elseif ($bookingStatus === 'COMPLETED') {
            $statusText = 'Servis Selesai';
            $statusClass = 'status-completed';
        } elseif ($bookingStatus === 'REJECTED') {
            $statusText = 'Booking Ditolak';
            $statusClass = 'status-cancelled';
        } elseif ($bookingStatus === 'CANCELLED') {
            $statusText = 'Booking Dibatalkan';
            $statusClass = 'status-cancelled';
        }

        $isQrisWaiting = $payment?->payment_status === 'WAITING_VERIFICATION';
        // UNPAID + payment_proof terisi dipakai sebagai penanda Admin meminta upload ulang.
        $isQrisReuploadRequested = $payment?->payment_method === 'QRIS'
            && $payment?->payment_status === 'UNPAID'
            && filled($payment?->payment_proof);

        $paymentStatusText = 'Belum Ada Tagihan';
        $paymentStatusClass = 'payment-unpaid';

        if ($payment?->payment_status === 'PAID' && $isPaid) {
            $paymentStatusText = 'Lunas';
            $paymentStatusClass = 'payment-paid';
        } elseif ($payment?->payment_status === 'WAITING_VERIFICATION') {
            $paymentStatusText = 'Menunggu Verifikasi';
            $paymentStatusClass = 'payment-waiting';
        } elseif ($isQrisReuploadRequested) {
            $paymentStatusText = 'Perlu Upload Ulang';
            $paymentStatusClass = 'payment-waiting';
        } elseif ($payment && (float) $payment->amount > 0) {
            $paymentStatusText = 'Belum Dibayar';
        }

        $paymentAmount = $payment && is_numeric($payment->amount) ? (float) $payment->amount : 0;
        // Nominal tunai dicatat Admin saat konfirmasi lunas; struk hanya menampilkannya.
        $cashReceived = $payment && is_numeric($payment->cash_received) ? (float) $payment->cash_received : null;
        $cashChange = $cashReceived !== null ? max(0, $cashReceived - $paymentAmount) : null;
        $estimatedCost = $serviceRecord?->estimated_cost;
        $finalCost = $serviceRecord?->final_cost;
        $isTerminalBooking = in_array($bookingStatus, ['CANCELLED', 'REJECTED'], true);
        $canCancel = in_array($bookingStatus, ['PENDING', 'WAITING_VERIFICATION'], true);
        $methodLabel = match ($payment?->payment_method) {
            'CASH' => 'Cash / Tunai',
            'QRIS' => 'QRIS',
            default => 'Belum dipilih',
        };

        $progressBooking = in_array(
            $bookingStatus,
            ['PENDING', 'WAITING_VERIFICATION', 'CONFIRMED', 'COMPLETED'],
            true,
        );
        $progressDiagnosis =
            $bookingStatus === 'COMPLETED' || in_array($recordStatus, ['DIAGNOSIS', 'PROCESS', 'COMPLETED'], true);
        $progressProcess = $bookingStatus === 'COMPLETED' || in_array($recordStatus, ['PROCESS', 'COMPLETED'], true);
        $progressCompleted = $bookingStatus === 'COMPLETED' || $recordStatus === 'COMPLETED';
    @endphp

    <style>
        .status-page {
            width: 100%;
            max-width: 1080px;
            margin: 0 auto;
            padding: 38px 24px 56px;
        }

        .status-header {
            margin-bottom: 26px;
        }

        .status-eyebrow {
            color: #2563eb;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .08em;
            margin-bottom: 7px;
        }

        .status-title {
            color: #111827;
            font-size: clamp(26px, 3vw, 34px);
            font-weight: 800;
            letter-spacing: -.7px;
            line-height: 1.2;
            margin-bottom: 9px;
        }

        .status-description {
            color: #64748b;
            font-size: 14px;
            max-width: 680px;
        }

        .status-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 15px;
            padding: 24px;
            margin-bottom: 18px;
            box-shadow: 0 4px 14px rgba(15, 23, 42, .035);
        }

        .card-title {
            color: #111827;
            font-size: 17px;
            font-weight: 800;
            margin-bottom: 7px;
        }

        .card-description {
            color: #64748b;
            font-size: 13px;
            margin-bottom: 18px;
        }

        .status-search-form {
            display: flex;
            gap: 10px;
        }

        .status-input,
        .status-textarea,
        .status-file {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 9px;
            background: #fff;
            color: #111827;
            font: inherit;
            font-size: 14px;
            outline: none;
            padding: 12px 14px;
        }

        .status-input:focus,
        .status-textarea:focus,
        .status-file:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, .10);
        }

        .status-search-form .status-input {
            flex: 1;
            min-width: 0;
            text-transform: uppercase;
        }

        .btn-primary,
        .btn-secondary,
        .btn-danger {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            border: 0;
            border-radius: 9px;
            cursor: pointer;
            font: inherit;
            font-size: 13px;
            font-weight: 750;
            padding: 12px 17px;
            text-decoration: none;
            transition: background .2s ease, transform .2s ease;
        }

        .btn-primary {
            background: #2563eb;
            color: #fff;
        }

        .btn-primary:hover {
            background: #1d4ed8;
        }

        .btn-secondary {
            background: #f1f5f9;
            color: #334155;
        }

        .btn-secondary:hover {
            background: #e2e8f0;
        }

        .btn-danger {
            background: #dc2626;
            color: #fff;
        }

        .btn-danger:hover {
            background: #b91c1c;
        }

        .alert-error,
        .alert-success {
            border-radius: 10px;
            padding: 12px 15px;
            margin-bottom: 16px;
            font-size: 13px;
        }

        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
        }

        .alert-success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
        }

        .validation-list {
            margin: 7px 0 0 18px;
        }

        .service-heading {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 20px;
        }

        .service-id-label,
        .field-label {
            color: #64748b;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .025em;
            margin-bottom: 5px;
        }

        .service-id-value {
            color: #2563eb;
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: 20px;
            font-weight: 800;
            overflow-wrap: anywhere;
        }

        .status-pill,
        .payment-pill {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
            padding: 7px 11px;
            white-space: nowrap;
        }

        .status-pending {
            background: #eff6ff;
            color: #1d4ed8;
        }

        .status-waiting {
            background: #fef3c7;
            color: #92400e;
        }

        .status-confirmed {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .status-diagnosis {
            background: #ede9fe;
            color: #6d28d9;
        }

        .status-process {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .status-completed {
            background: #dcfce7;
            color: #166534;
        }

        .status-cancelled {
            background: #fee2e2;
            color: #991b1b;
        }

        .payment-unpaid {
            background: #fef3c7;
            color: #92400e;
        }

        .payment-waiting {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .payment-paid {
            background: #dcfce7;
            color: #166534;
        }

        .info-grid,
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .info-item,
        .detail-item {
            min-width: 0;
            border: 1px solid #edf0f4;
            border-radius: 10px;
            padding: 14px;
        }

        .info-value {
            color: #111827;
            font-size: 13px;
            font-weight: 750;
            overflow-wrap: anywhere;
        }

        .muted-value {
            color: #94a3b8;
            font-size: 13px;
            font-style: italic;
            font-weight: 500;
        }

        .progress-track {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 8px;
            margin-top: 26px;
        }

        .progress-step {
            position: relative;
            text-align: center;
        }

        .progress-dot {
            width: 13px;
            height: 13px;
            background: #cbd5e1;
            border: 3px solid #e2e8f0;
            border-radius: 50%;
            margin: 0 auto 8px;
        }

        .progress-step.active .progress-dot {
            background: #2563eb;
            border-color: #bfdbfe;
        }

        .progress-text {
            color: #94a3b8;
            font-size: 11px;
            font-weight: 650;
        }

        .progress-step.active .progress-text {
            color: #1d4ed8;
        }

        .detail-item.full {
            grid-column: 1 / -1;
        }

        .detail-value {
            color: #111827;
            font-size: 13px;
            font-weight: 750;
            overflow-wrap: anywhere;
        }

        .detail-text {
            color: #475569;
            font-size: 13px;
            line-height: 1.65;
            white-space: pre-line;
            overflow-wrap: anywhere;
        }

        .highlight-cost {
            color: #2563eb;
            font-size: 18px;
            font-weight: 850;
        }

        .payment-panel {
            border: 1px solid #bfdbfe;
            border-radius: 13px;
            background: #eff6ff;
            padding: 20px;
            margin-bottom: 18px;
        }

        .payment-panel-title {
            color: #1e3a8a;
            font-size: 16px;
            font-weight: 850;
            margin-bottom: 5px;
        }

        .payment-panel-description {
            color: #475569;
            font-size: 13px;
            line-height: 1.6;
        }

        .bill-total {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 12px;
            border: 1px solid #dbeafe;
            border-radius: 10px;
            background: #fff;
            padding: 15px;
            margin: 15px 0;
        }

        .bill-amount {
            color: #1d4ed8;
            font-size: 23px;
            font-weight: 850;
            text-align: right;
        }

        .qris-wrap {
            border: 1px solid #dbeafe;
            background: #fff;
            border-radius: 10px;
            padding: 16px;
            text-align: center;
            margin: 14px 0;
        }

        .qris-image {
            display: block;
            width: 100%;
            max-width: 250px;
            height: auto;
            margin: 0 auto;
            border: 1px solid #e2e8f0;
            border-radius: 9px;
        }

        .payment-note {
            border-radius: 10px;
            padding: 14px;
            margin-top: 13px;
            font-size: 13px;
            line-height: 1.6;
        }

        .payment-note.cash {
            background: #fff;
            border: 1px solid #e2e8f0;
            color: #334155;
        }

        .payment-note.waiting {
            background: #fffbeb;
            border: 1px solid #fde68a;
            color: #92400e;
        }

        .payment-note.success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
        }

        .proof-preview {
            display: inline-block;
            color: #2563eb;
            font-size: 12px;
            font-weight: 700;
            margin-top: 9px;
            text-decoration: underline;
        }

        .cancel-panel {
            background: #fff;
            border: 1px solid #fecaca;
        }

        .cancel-panel .card-title {
            color: #991b1b;
        }

        .cancel-panel .card-description {
            color: #7f1d1d;
        }

        .status-textarea {
            min-height: 94px;
            resize: vertical;
            margin-bottom: 12px;
        }

        .receipt-card {
            background: #fff;
            border: 1px solid #d1d5db;
            border-radius: 14px;
            padding: 25px;
            margin-bottom: 18px;
        }

        .receipt-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 15px;
            padding-bottom: 17px;
            border-bottom: 1px dashed #cbd5e1;
        }

        .receipt-brand-wrap {
            display: flex;
            align-items: center;
            gap: 11px;
        }

        .receipt-logo {
            width: 46px;
            height: 46px;
            border-radius: 11px;
            object-fit: cover;
            border: 1px solid #e2e7f8;
            background: #fff;
            flex-shrink: 0;
        }

        .receipt-brand {
            color: #111827;
            font-size: 18px;
            font-weight: 900;
        }

        .receipt-subtitle {
            color: #64748b;
            font-size: 12px;
        }

        .receipt-title {
            color: #166534;
            font-size: 13px;
            font-weight: 850;
            text-align: right;
        }

        .receipt-id {
            color: #2563eb;
            font-family: var(--font-mono, ui-monospace, monospace);
            font-size: 13px;
            font-weight: 800;
        }

        .receipt-section {
            padding: 16px 0;
            border-bottom: 1px dashed #cbd5e1;
        }

        .receipt-row {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            padding: 6px 0;
            font-size: 13px;
        }

        .receipt-row span:first-child {
            color: #64748b;
        }

        .receipt-row strong {
            color: #111827;
            text-align: right;
            overflow-wrap: anywhere;
        }

        .receipt-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding-top: 15px;
        }

        .receipt-total strong {
            color: #166534;
            font-size: 21px;
        }

        .receipt-cash-summary {
            border-bottom: 0;
            padding-bottom: 0;
        }

        .receipt-cash-summary strong {
            font-family: var(--font-mono, ui-monospace, monospace);
            font-size: 14px;
        }

        .receipt-footer {
            color: #64748b;
            font-size: 11px;
            line-height: 1.6;
            text-align: center;
            padding-top: 16px;
        }

        .status-bottom-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-top: 6px;
        }

        .back-link {
            color: #2563eb;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        @media (max-width: 680px) {
            .status-page {
                padding: 26px 15px 42px;
            }

            .status-card {
                padding: 18px;
            }

            .status-search-form {
                flex-direction: column;
            }

            .status-search-form .btn-primary {
                width: 100%;
            }

            .service-heading {
                flex-direction: column;
                align-items: flex-start;
            }

            .info-grid,
            .detail-grid {
                grid-template-columns: 1fr;
            }

            .detail-item.full {
                grid-column: auto;
            }

            .progress-track {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                row-gap: 18px;
            }

            .bill-total {
                align-items: flex-start;
                flex-direction: column;
            }

            .bill-amount {
                text-align: left;
            }

            .receipt-card {
                padding: 16px;
            }

            .receipt-top {
                flex-direction: column;
                gap: 10px;
            }

            .receipt-title {
                text-align: left;
            }

            .receipt-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 2px;
                font-size: 12.5px;
            }

            .receipt-row strong {
                text-align: left;
                max-width: 100%;
            }

            .receipt-id {
                font-size: 12px;
                overflow-wrap: anywhere;
            }

            .receipt-total {
                flex-direction: column;
                align-items: flex-start;
                gap: 2px;
            }

            .receipt-total strong {
                font-size: 20px;
            }

            .receipt-cash-summary strong {
                font-size: 13px;
            }

            .qris-image {
                max-width: 100%;
                height: auto;
            }

            .status-file {
                width: 100%;
            }

            .proof-preview {
                display: inline-block;
                overflow-wrap: anywhere;
            }

            .status-bottom-actions {
                align-items: stretch;
                flex-direction: column;
                gap: 10px;
            }

            .status-bottom-actions .btn-primary,
            .status-bottom-actions .btn-secondary {
                width: 100%;
            }
        }

    </style>

    <div class="status-page">
        <header class="status-header">
            <div class="status-eyebrow">FIXPOINT SERVICE</div>
            <h1 class="status-title">Cek Status Servis</h1>
            <p class="status-description">
                Masukkan Service ID untuk melihat perkembangan pemeriksaan, pengerjaan, estimasi biaya,
                dan informasi pembayaran perangkat Anda.
            </p>
        </header>

        @if (session('error'))
            <div class="alert-error" role="alert">{{ session('error') }}</div>
        @endif

        @if (session('success'))
            <div class="alert-success" role="status">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert-error" role="alert">
                <strong>Data belum dapat diproses.</strong>
                <ul class="validation-list">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="status-card">
            <h2 class="card-title">Cari Service ID</h2>
            <p class="card-description">Gunakan kode Service ID yang diberikan setelah booking berhasil.</p>
            <form action="{{ route('cek-status.show') }}" method="GET" class="status-search-form">
                <input type="text" name="code" class="status-input" placeholder="Contoh: FP-20261030-ABCDE"
                    value="{{ old('code', request('code')) }}" maxlength="50" autocomplete="off" required>
                <button type="submit" class="btn-primary">Cari Status Servis</button>
            </form>
        </section>

        @if ($hasBooking)
            <section class="status-card">
                <div class="service-heading">
                    <div>
                        <div class="service-id-label">SERVICE ID</div>
                        <div class="service-id-value">{{ $booking->service_id_code }}</div>
                    </div>
                    <span class="status-pill {{ $statusClass }}">{{ $statusText }}</span>
                </div>

                <div class="info-grid">
                    <div class="info-item">
                        <div class="field-label">Nama Pelanggan</div>
                        <div class="info-value">{{ $booking->customer?->name ?? '-' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="field-label">Perangkat</div>
                        <div class="info-value">
                            {{ $booking->device?->device_type ?? 'Perangkat' }}
                            {{ $booking->device?->brand ?? '' }}
                            {{ $booking->device?->model && $booking->device?->model !== $booking->device?->brand ? '(' . $booking->device->model . ')' : '' }}
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="field-label">Kategori Layanan</div>
                        <div class="info-value">{{ $booking->service?->name ?? '-' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="field-label">Jadwal Booking</div>
                        <div class="info-value">
                            @if ($booking->schedule)
                                {{ \Carbon\Carbon::parse($booking->schedule->schedule_date)->locale('id')->translatedFormat('d F Y') }}
                                <br>
                                {{ substr($booking->schedule->start_time, 0, 5) }}–{{ substr($booking->schedule->end_time, 0, 5) }}
                            @else
                                -
                            @endif
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="field-label">Metode Pembayaran</div>
                        <div class="info-value">{{ $methodLabel }}</div>
                    </div>
                    <div class="info-item">
                        <div class="field-label">Status Pembayaran</div>
                        <div class="info-value">
                            <span class="payment-pill {{ $paymentStatusClass }}">{{ $paymentStatusText }}</span>
                        </div>
                    </div>
                </div>

                @if (!$isTerminalBooking)
                    <div class="progress-track" aria-label="Tahapan servis">
                        <div class="progress-step {{ $progressBooking ? 'active' : '' }}">
                            <div class="progress-dot"></div>
                            <div class="progress-text">Booking</div>
                        </div>
                        <div class="progress-step {{ $progressDiagnosis ? 'active' : '' }}">
                            <div class="progress-dot"></div>
                            <div class="progress-text">Pemeriksaan</div>
                        </div>
                        <div class="progress-step {{ $progressProcess ? 'active' : '' }}">
                            <div class="progress-dot"></div>
                            <div class="progress-text">Pengerjaan</div>
                        </div>
                        <div class="progress-step {{ $progressCompleted ? 'active' : '' }}">
                            <div class="progress-dot"></div>
                            <div class="progress-text">Selesai</div>
                        </div>
                    </div>
                @endif
            </section>

            <section class="status-card">
                <h2 class="card-title">Detail Servis</h2>
                <p class="card-description">Informasi pemeriksaan dan pengerjaan yang diperbarui oleh Admin.</p>

                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="field-label">Diagnosis</div>
                        @if (filled($serviceRecord?->diagnosis))
                            <div class="detail-text">{{ $serviceRecord->diagnosis }}</div>
                        @else
                            <div class="muted-value">Belum ada hasil diagnosis.</div>
                        @endif
                    </div>

                    <div class="detail-item">
                        <div class="field-label">Estimasi Biaya</div>
                        @if ($estimatedCost !== null)
                            <div class="highlight-cost">Rp {{ number_format((float) $estimatedCost, 0, ',', '.') }}</div>
                        @else
                            <div class="muted-value">Menunggu hasil pemeriksaan.</div>
                        @endif
                    </div>

                    @if ($booking->status === 'COMPLETED' && $finalCost !== null)
                        <div class="detail-item">
                            <div class="field-label">Biaya Akhir</div>
                            <div class="highlight-cost">Rp {{ number_format((float) $finalCost, 0, ',', '.') }}</div>
                        </div>
                    @endif

                    <div class="detail-item">
                        <div class="field-label">Mulai Pengerjaan</div>
                        <div class="detail-value">
                            {{ $serviceRecord?->started_at ? \Carbon\Carbon::parse($serviceRecord->started_at)->locale('id')->translatedFormat('d F Y, H:i') : 'Belum dimulai' }}
                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="field-label">Selesai Pengerjaan</div>
                        <div class="detail-value">
                            {{ $serviceRecord?->completed_at ? \Carbon\Carbon::parse($serviceRecord->completed_at)->locale('id')->translatedFormat('d F Y, H:i') : 'Belum selesai' }}
                        </div>
                    </div>

                    <div class="detail-item full">
                        <div class="field-label">Keluhan Awal</div>
                        <div class="detail-text">{{ $booking->device?->complaint ?? 'Belum ada keluhan.' }}</div>
                    </div>

                    <div class="detail-item full">
                        <div class="field-label">Estimasi Pengerjaan & Catatan Servis</div>
                        @if (filled($serviceRecord?->service_notes))
                            <div class="detail-text">{{ $serviceRecord->service_notes }}</div>
                        @else
                            <div class="muted-value">Estimasi pengerjaan akan ditampilkan setelah pemeriksaan.</div>
                        @endif
                    </div>

                    @if (filled($booking->notes))
                        <div class="detail-item full">
                            <div class="field-label">Catatan Booking</div>
                            <div class="detail-text">{{ $booking->notes }}</div>
                        </div>
                    @endif
                </div>
            </section>

            @if ($canCancel)
                <section class="status-card cancel-panel">
                    <h2 class="card-title">Batalkan Booking</h2>
                    <p class="card-description">
                        Pembatalan hanya dapat dilakukan sebelum booking dikonfirmasi untuk pemeriksaan atau pengerjaan.
                        Slot jadwal akan dikembalikan setelah pembatalan berhasil.
                    </p>
                    <form action="{{ route('cek-status.cancel', $booking->service_id_code) }}" method="POST"
                        onsubmit="return confirm('Apakah Anda yakin ingin membatalkan booking ini?')">
                        @csrf
                        <label class="field-label" for="cancellation_reason">Alasan Pembatalan</label>
                        <textarea name="cancellation_reason" id="cancellation_reason" class="status-textarea" maxlength="500"
                            placeholder="Tuliskan alasan pembatalan..." required>{{ old('cancellation_reason') }}</textarea>
                        <button type="submit" class="btn-danger">Ajukan Pembatalan</button>
                    </form>
                </section>
            @endif

            @if ($booking->status === 'CANCELLED')
                <section class="status-card" style="border-color:#fecaca;">
                    <h2 class="card-title" style="color:#991b1b;">Booking Dibatalkan</h2>
                    <div class="detail-text"><strong>Alasan:</strong>
                        {{ $booking->cancellation_reason ?: 'Tidak ada alasan yang dicatat.' }}</div>
                </section>
            @elseif ($booking->status === 'REJECTED')
                <section class="status-card" style="border-color:#fecaca;">
                    <h2 class="card-title" style="color:#991b1b;">Booking Tidak Dapat Diproses</h2>
                    <p class="detail-text">Silakan hubungi Admin FixPoint untuk informasi lebih lanjut.</p>
                </section>
            @endif

            @if ($booking->status === 'COMPLETED' && !$isPaid)
                <section class="payment-panel">
                    <h2 class="payment-panel-title">Informasi Pembayaran</h2>
                    <p class="payment-panel-description">
                        Servis telah selesai. Periksa total tagihan dan ikuti petunjuk pembayaran sesuai metode yang dipilih
                        saat booking.
                    </p>

                    @if ($payment && $paymentAmount > 0)
                        <div class="bill-total">
                            <div>
                                <div class="field-label">TOTAL TAGIHAN</div>
                                <div class="payment-panel-description">{{ $booking->service?->name ?? 'Jasa servis' }}
                                </div>
                            </div>
                            <div class="bill-amount">Rp {{ number_format($paymentAmount, 0, ',', '.') }}</div>
                        </div>

                        @if ($payment->payment_method === 'CASH')
                            <div class="payment-note cash">
                                <strong>Pembayaran Cash / Tunai</strong><br>
                                Silakan lakukan pembayaran saat mengambil perangkat di tempat servis. Status pembayaran akan
                                berubah menjadi <strong>Lunas</strong> setelah Admin menerima dan mengonfirmasi pembayaran.
                            </div>
                        @elseif ($payment->payment_method === 'QRIS')
                            @if ($isQrisWaiting)
                                <div class="payment-note waiting">
                                    <strong>Bukti pembayaran sedang diverifikasi.</strong><br>
                                    Admin sedang memeriksa bukti pembayaran QRIS Anda. Struk akan tersedia setelah
                                    pembayaran dikonfirmasi lunas.
                                    @if ($payment->payment_proof)
                                        <br>
                                        <a class="proof-preview" href="{{ route('cek-status.proof', $booking->service_id_code) }}"
                                            target="_blank" rel="noopener">
                                            Lihat bukti pembayaran yang dikirim
                                        </a>
                                    @endif
                                </div>
                            @else
                                @if ($isQrisReuploadRequested)
                                    <div class="payment-note waiting" style="margin-bottom:14px;">
                                        <strong>Admin meminta bukti pembayaran diunggah ulang.</strong><br>
                                        Pastikan gambar baru terlihat jelas dan nominalnya sesuai tagihan.
                                        @if (filled($payment->notes))
                                            <br><strong>Catatan Admin:</strong> {{ $payment->notes }}
                                        @endif
                                        <br>
                                        <a class="proof-preview" href="{{ route('cek-status.proof', $booking->service_id_code) }}"
                                            target="_blank" rel="noopener">
                                            Lihat bukti sebelumnya
                                        </a>
                                    </div>
                                @endif
                                <div class="qris-wrap">
                                    <div class="field-label" style="margin-bottom:12px;">
                                        SCAN QRIS UNTUK MEMBAYAR
                                    </div>

                                    @if (file_exists(public_path('qris/qris.toko.png')))
                                        <img src="{{ asset('qris/qris.toko.png') }}" alt="Kode QRIS FixPoint Service"
                                            class="qris-image">
                                    @else
                                        <p class="detail-text" style="color:#b91c1c;">
                                            Kode QRIS belum tersedia. Silakan hubungi Admin.
                                        </p>
                                    @endif

                                    <p class="payment-panel-description" style="margin-top:12px;">
                                        Setelah membayar, unggah bukti transaksi agar Admin dapat memverifikasinya.
                                    </p>
                                </div>

                                <form action="{{ route('cek-status.upload-proof') }}" method="POST"
                                    enctype="multipart/form-data">
                                    @csrf
                                    <input type="hidden" name="service_id_code"
                                        value="{{ $booking->service_id_code }}">
                                    <label class="field-label" for="payment_proof">Bukti Pembayaran (JPG, PNG, WEBP;
                                        maksimal 2 MB)</label>
                                    <input type="file" name="payment_proof" id="payment_proof" class="status-file"
                                        accept="image/jpeg,image/png,image/jpg,image/webp" required>
                                    <button type="submit" class="btn-primary" style="width:100%;margin-top:12px;">
                                        Kirim Bukti Pembayaran
                                    </button>
                                </form>
                            @endif
                        @else
                            <div class="payment-note cash">
                                Metode pembayaran belum tercatat. Silakan hubungi Admin untuk mendapatkan informasi tagihan.
                            </div>
                        @endif
                    @else
                        <div class="payment-note waiting">
                            Total tagihan belum ditetapkan oleh Admin. Silakan periksa kembali setelah biaya akhir dicatat.
                        </div>
                    @endif
                </section>
            @endif

            @if ($isPaid)
                <section class="receipt-card" id="paymentReceipt">
                    <div class="receipt-top">
                        <div class="receipt-brand-wrap">
                            <img src="{{ asset('images/logo.fixpoint.jpeg') }}" alt="Logo FixPoint Service"
                                class="receipt-logo">
                            <div>
                                <div class="receipt-brand">FixPoint Service</div>
                                <div class="receipt-subtitle">Bukti pembayaran jasa servis perangkat</div>
                            </div>
                        </div>
                        <div>
                            <div class="receipt-title">PEMBAYARAN LUNAS</div>
                            <div class="receipt-subtitle" style="text-align:right;">Struk transaksi</div>
                        </div>
                    </div>

                    <div class="receipt-section">
                        <div class="receipt-row">
                            <span>Service ID</span>
                            <strong class="receipt-id">{{ $booking->service_id_code }}</strong>
                        </div>
                        <div class="receipt-row">
                            <span>Tanggal pembayaran</span>
                            <strong>{{ $payment->payment_date?->locale('id')->translatedFormat('d F Y, H:i') ?? '-' }}</strong>
                        </div>
                        <div class="receipt-row">
                            <span>ID Pelanggan</span>
                            <strong class="receipt-id">{{ $booking->customer?->customer_id ?? '-' }}</strong>
                        </div>
                        <div class="receipt-row">
                            <span>Nama pelanggan</span>
                            <strong>{{ $booking->customer?->name ?? '-' }}</strong>
                        </div>
                        <div class="receipt-row">
                            <span>No. Telepon</span>
                            <strong>{{ $booking->customer?->phone_number ?? '-' }}</strong>
                        </div>
                        <div class="receipt-row">
                            <span>Perangkat</span>
                            <strong>{{ trim(($booking->device?->device_type ?? '') . ' ' . ($booking->device?->brand ?? '') . ' ' . ($booking->device?->model ?? '')) ?: '-' }}</strong>
                        </div>
                        <div class="receipt-row">
                            <span>Layanan</span>
                            <strong>{{ $booking->service?->name ?? '-' }}</strong>
                        </div>
                    </div>

                    <div class="receipt-section">
                        <div class="receipt-row">
                            <span>Metode pembayaran</span>
                            <strong>{{ $methodLabel }}</strong>
                        </div>
                        <div class="receipt-row">
                            <span>Status pembayaran</span>
                            <strong style="color:#15803d;">LUNAS</strong>
                        </div>
                        @if (filled($serviceRecord?->diagnosis))
                            <div class="receipt-row">
                                <span>Diagnosis</span>
                                <strong>{{ $serviceRecord->diagnosis }}</strong>
                            </div>
                        @endif
                    </div>

                    <div class="receipt-total">
                        <span style="color:#475569;font-size:14px;font-weight:750;">
                            {{ $payment->payment_method === 'CASH' ? 'Total tagihan' : 'Total dibayar' }}
                        </span>
                        <strong>Rp {{ number_format($paymentAmount, 0, ',', '.') }}</strong>
                    </div>

                    @if ($payment->payment_method === 'CASH')
                        <div class="receipt-section receipt-cash-summary">
                            <div class="receipt-row">
                                <span>Bayar (tunai)</span>
                                <strong>
                                    {{ $cashReceived !== null ? 'Rp ' . number_format($cashReceived, 0, ',', '.') : '—' }}
                                </strong>
                            </div>
                            <div class="receipt-row">
                                <span>Kembalian</span>
                                <strong>
                                    {{ $cashChange !== null ? 'Rp ' . number_format($cashChange, 0, ',', '.') : '—' }}
                                </strong>
                            </div>
                        </div>
                    @endif

                    <div class="receipt-footer">
                        Terima kasih telah menggunakan layanan FixPoint Service.<br>
                        Rincian ini tersimpan pada sistem kami dan dapat dibuka kembali dengan Service ID di atas.
                    </div>
                </section>
            @endif

            <div class="status-bottom-actions">
                <a href="{{ route('cek-status') }}" class="back-link">← Cek Service ID lainnya</a>
                @if ($isPaid)
                    <span class="payment-pill payment-paid">Pembayaran telah dikonfirmasi</span>
                @endif
            </div>
        @endif
    </div>
@endsection
