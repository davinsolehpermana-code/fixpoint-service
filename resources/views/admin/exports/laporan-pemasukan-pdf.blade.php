<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Pemasukan FixPoint Service</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: "DejaVu Sans", Arial, sans-serif; font-size: 9px; color: #1f2937; margin: 0; }
        h1 { font-size: 15px; margin: 0 0 3px; }
        .subtitle { font-size: 9px; color: #475569; margin: 0; }
        .header { border-bottom: 2px solid #1d4ed8; padding-bottom: 8px; margin-bottom: 10px; }
        .meta { margin: 0 0 10px; font-size: 9px; }
        .meta td { padding: 1px 12px 1px 0; vertical-align: top; }
        .summary { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .summary td { border: 1px solid #cbd5e1; padding: 6px 8px; font-size: 9px; width: 25%; }
        .summary .label { color: #475569; display: block; font-size: 8px; margin-bottom: 2px; }
        .summary .value { font-weight: bold; font-size: 11px; }
        table.report { width: 100%; border-collapse: collapse; }
        table.report th { background: #e8eefb; border: 1px solid #94a3b8; padding: 4px 5px; font-size: 8px; text-align: left; }
        table.report td { border: 1px solid #cbd5e1; padding: 4px 5px; font-size: 8px; vertical-align: top; }
        table.report tr:nth-child(even) td { background: #f8fafc; }
        table.report tfoot td { font-weight: bold; background: #eef2ff; }
        .right { text-align: right; white-space: nowrap; }
        .center { text-align: center; }
        .empty { text-align: center; padding: 16px; color: #64748b; font-size: 10px; }
        .footer { margin-top: 12px; font-size: 8px; color: #64748b; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Laporan Pemasukan FixPoint Service</h1>
        <p class="subtitle">Sistem Informasi Booking &amp; Manajemen Jasa Servis Laptop dan PC</p>
    </div>

    <table class="meta">
        <tr>
            <td><strong>Periode:</strong> {{ $periodLabel }}</td>
            <td><strong>Metode:</strong> {{ $method ?? 'Semua metode' }}</td>
            <td><strong>Dasar perhitungan:</strong> Tanggal pembayaran, status Lunas (PAID)</td>
            <td><strong>Dicetak:</strong> {{ $printedAt->locale('id')->translatedFormat('d F Y, H:i') }} WIB</td>
        </tr>
    </table>

    <table class="summary">
        <tr>
            <td><span class="label">Total Pemasukan</span><span class="value">Rp {{ number_format((float) $summary->total_pemasukan, 0, ',', '.') }}</span></td>
            <td><span class="label">Pemasukan QRIS</span><span class="value">Rp {{ number_format((float) $summary->total_qris, 0, ',', '.') }}</span></td>
            <td><span class="label">Pemasukan Cash</span><span class="value">Rp {{ number_format((float) $summary->total_cash, 0, ',', '.') }}</span></td>
            <td><span class="label">Transaksi Berhasil</span><span class="value">{{ (int) $summary->total_transaksi }} transaksi</span></td>
        </tr>
    </table>

    <table class="report">
        <thead>
            <tr>
                <th style="width:22px;">No</th>
                <th>Tanggal Pembayaran</th>
                <th>Service ID</th>
                <th>Pelanggan</th>
                <th>Perangkat</th>
                <th>Metode</th>
                <th class="right">Jumlah</th>
                <th>Status</th>
                <th>Diproses Oleh</th>
            </tr>
        </thead>
        <tbody>
            @forelse($payments as $payment)
                <tr>
                    <td class="center">{{ $loop->iteration }}</td>
                    <td>{{ $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->locale('id')->translatedFormat('d M Y, H:i') : '-' }}</td>
                    <td>{{ $payment->service_id_code }}</td>
                    <td>{{ $payment->customer_name ?? '-' }}</td>
                    <td>{{ trim(($payment->device_type ?? '') . ' ' . ($payment->brand ?? '')) ?: '-' }}</td>
                    <td>{{ $payment->payment_method }}</td>
                    <td class="right">Rp {{ number_format((float) $payment->amount, 0, ',', '.') }}</td>
                    <td>{{ $payment->payment_status === 'PAID' ? 'Lunas' : $payment->payment_status }}</td>
                    <td>{{ $payment->processed_by_email ?? 'Sistem' }}</td>
                </tr>
            @empty
                <tr><td colspan="9" class="empty">Tidak ada transaksi lunas pada periode ini.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6">TOTAL PEMASUKAN</td>
                <td class="right">Rp {{ number_format((float) $summary->total_pemasukan, 0, ',', '.') }}</td>
                <td colspan="2">{{ (int) $summary->total_transaksi }} transaksi</td>
            </tr>
        </tfoot>
    </table>

    <p class="footer">Dokumen ini dihasilkan otomatis oleh FixPoint Service. Pemasukan dihitung dari pembayaran berstatus PAID (lunas) berdasarkan tanggal pembayaran; pembayaran pending atau belum diterima tidak dihitung.</p>
</body>
</html>
