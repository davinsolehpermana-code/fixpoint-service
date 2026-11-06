<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk {{ $payment->service_id_code }} · FixPoint Service</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=space-grotesk:500,600,700|ibm-plex-sans:400,500,600|jetbrains-mono:500,600"
        rel="stylesheet">
    <style>
        :root {
            --ink: #0f172a;
            --muted: #64748b;
            --line: #e2e8f0;
            --accent: #2563eb;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 28px 16px 48px;
            background: #eef2f7;
            color: var(--ink);
            font-family: 'IBM Plex Sans', system-ui, -apple-system, sans-serif;
        }

        .struk-wrap {
            max-width: 420px;
            margin: 0 auto;
        }

        .struk-toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .struk-toolbar a,
        .struk-toolbar button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 42px;
            padding: 0 16px;
            border-radius: 9px;
            border: 1px solid transparent;
            font: inherit;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
        }

        .struk-back {
            background: #fff;
            border-color: var(--line);
            color: var(--ink);
        }

        .struk-print {
            background: var(--accent);
            color: #fff;
            box-shadow: 0 10px 20px -12px rgba(37, 99, 235, .9);
        }

        .struk-card {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 22px 20px;
            box-shadow: 0 18px 40px -30px rgba(15, 23, 42, .55);
        }

        .struk-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 14px;
            padding-bottom: 14px;
            border-bottom: 1px dashed var(--line);
        }

        .struk-brand-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .struk-logo {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            object-fit: cover;
        }

        .struk-brand {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 16px;
            font-weight: 700;
            letter-spacing: -.02em;
        }

        .struk-subtitle {
            font-size: 11px;
            color: var(--muted);
            line-height: 1.45;
        }

        .struk-title {
            font-family: 'JetBrains Mono', monospace;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: .09em;
            color: var(--accent);
        }

        .struk-section {
            padding: 14px 0;
            border-bottom: 1px dashed var(--line);
        }

        .struk-row {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 14px;
            font-size: 12.5px;
            padding: 4px 0;
        }

        .struk-row span {
            color: var(--muted);
            flex: 0 0 auto;
        }

        .struk-row strong {
            font-weight: 600;
            text-align: right;
            overflow-wrap: anywhere;
        }

        .struk-row .mono {
            font-family: 'JetBrains Mono', monospace;
            letter-spacing: .02em;
        }

        .struk-total {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 14px 0;
            border-bottom: 1px dashed var(--line);
        }

        .struk-total span {
            font-size: 12px;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: .06em;
        }

        .struk-total strong {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 20px;
            font-weight: 700;
        }

        .struk-note {
            margin-top: 14px;
            border-radius: 10px;
            background: #f1f5f9;
            padding: 10px 12px;
            font-size: 11px;
            line-height: 1.6;
            color: #475569;
        }

        .struk-footer {
            margin-top: 16px;
            text-align: center;
            font-size: 11px;
            line-height: 1.6;
            color: var(--muted);
        }

        .struk-hint {
            margin: 12px 0 0;
            text-align: center;
            font-size: 11px;
            color: #94a3b8;
        }

        @media print {
            @page {
                size: 80mm auto;
                margin: 0;
            }

            html,
            body {
                width: 80mm;
                margin: 0;
                padding: 0;
                background: #fff;
            }

            .struk-wrap {
                max-width: 80mm;
                margin: 0;
            }

            .struk-toolbar,
            .struk-hint {
                display: none !important;
            }

            .struk-card {
                width: 80mm;
                border: 0;
                border-radius: 0;
                box-shadow: none;
                padding: 5mm;
            }

            .struk-logo {
                width: 14mm;
                height: 14mm;
            }

            .struk-brand {
                font-size: 14px;
            }

            .struk-row {
                font-size: 11px;
            }

            .struk-total strong {
                font-size: 16px;
            }

            .struk-card {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>

<body>
    @php
        $amount = is_numeric($payment->amount) ? (float) $payment->amount : 0;
        $cashReceived = is_numeric($payment->cash_received) ? (float) $payment->cash_received : null;
        $cashChange = $cashReceived !== null ? max(0, $cashReceived - $amount) : null;
        $methodLabel = $payment->payment_method === 'CASH' ? 'Cash / Tunai' : 'QRIS';
        $deviceName = trim(($payment->device_type ?? '') . ' ' . ($payment->brand ?? '') . ' ' . ($payment->model ?? ''));
    @endphp

    <div class="struk-wrap">
        <div class="struk-toolbar">
            <a href="{{ route('admin.payments') }}" class="struk-back">← Kembali ke Pembayaran</a>
            <button type="button" class="struk-print" onclick="window.print()">Cetak Struk</button>
        </div>

        <div class="struk-card">
            <div class="struk-head">
                <div class="struk-brand-wrap">
                    <img src="{{ asset('images/logo.fixpoint.jpeg') }}" alt="Logo FixPoint Service" class="struk-logo">
                    <div>
                        <div class="struk-brand">FixPoint Service</div>
                        <div class="struk-subtitle">Bukti pembayaran jasa servis perangkat</div>
                    </div>
                </div>
                <div style="text-align:right;">
                    <div class="struk-title">LUNAS</div>
                    <div class="struk-subtitle">Struk transaksi</div>
                </div>
            </div>

            <div class="struk-section">
                <div class="struk-row">
                    <span>Service ID</span>
                    <strong class="mono">{{ $payment->service_id_code }}</strong>
                </div>
                <div class="struk-row">
                    <span>Tanggal pembayaran</span>
                    <strong>{{ $payment->payment_date ? \Illuminate\Support\Carbon::parse($payment->payment_date)->locale('id')->translatedFormat('d F Y, H:i') : '-' }}</strong>
                </div>
                <div class="struk-row">
                    <span>ID Pelanggan</span>
                    <strong class="mono">{{ $payment->customer_code ?? '-' }}</strong>
                </div>
                <div class="struk-row">
                    <span>Nama pelanggan</span>
                    <strong>{{ $payment->customer_name ?? '-' }}</strong>
                </div>
                <div class="struk-row">
                    <span>No. Telepon</span>
                    <strong>{{ $payment->phone_number ?? '-' }}</strong>
                </div>
                <div class="struk-row">
                    <span>Perangkat</span>
                    <strong>{{ $deviceName ?: '-' }}</strong>
                </div>
                <div class="struk-row">
                    <span>Layanan</span>
                    <strong>{{ $payment->service_name ?? '-' }}</strong>
                </div>
            </div>

            <div class="struk-section">
                <div class="struk-row">
                    <span>Metode pembayaran</span>
                    <strong>{{ $methodLabel }}</strong>
                </div>
                <div class="struk-row">
                    <span>Status pembayaran</span>
                    <strong style="color:#15803d;">LUNAS</strong>
                </div>
                @if (filled($serviceRecord?->diagnosis))
                    <div class="struk-row">
                        <span>Diagnosis</span>
                        <strong>{{ $serviceRecord->diagnosis }}</strong>
                    </div>
                @endif
                <div class="struk-row">
                    <span>Diproses oleh</span>
                    <strong>{{ $payment->admin_email ?? 'Admin' }}</strong>
                </div>
            </div>

            <div class="struk-total">
                <span>{{ $payment->payment_method === 'CASH' ? 'Total tagihan' : 'Total dibayar' }}</span>
                <strong>Rp {{ number_format($amount, 0, ',', '.') }}</strong>
            </div>

            @if ($payment->payment_method === 'CASH')
                <div class="struk-section">
                    <div class="struk-row">
                        <span>Bayar (tunai)</span>
                        <strong>{{ $cashReceived !== null ? 'Rp ' . number_format($cashReceived, 0, ',', '.') : '—' }}</strong>
                    </div>
                    <div class="struk-row">
                        <span>Kembalian</span>
                        <strong>{{ $cashChange !== null ? 'Rp ' . number_format($cashChange, 0, ',', '.') : '—' }}</strong>
                    </div>
                </div>
            @endif

            <div class="struk-note">
                Struk ini dicetak oleh Admin FixPoint Service sebagai bukti pembayaran yang sah.
                Pelanggan dapat melihat rincian yang sama melalui menu Cek Status dengan Service ID di atas.
            </div>

            <div class="struk-footer">
                Terima kasih telah menggunakan layanan FixPoint Service.
            </div>
        </div>

        <p class="struk-hint">Tips: pilih printer thermal 80mm pada dialog cetak untuk hasil struk terbaik.</p>
    </div>
</body>

</html>
