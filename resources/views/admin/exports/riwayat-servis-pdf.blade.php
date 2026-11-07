<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Riwayat Servis FixPoint Service</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: "DejaVu Sans", Arial, sans-serif; font-size: 9px; color: #1f2937; margin: 0; }
        h1 { font-size: 15px; margin: 0 0 3px; }
        .subtitle { font-size: 9px; color: #475569; margin: 0; }
        .header { border-bottom: 2px solid #1d4ed8; padding-bottom: 8px; margin-bottom: 10px; }
        .meta { margin: 0 0 10px; font-size: 9px; }
        .meta td { padding: 1px 12px 1px 0; vertical-align: top; }
        .summary { width: 100%; margin-bottom: 10px; border-collapse: collapse; }
        .summary td { border: 1px solid #cbd5e1; padding: 5px 7px; font-size: 9px; }
        .summary .label { color: #475569; width: 20%; }
        .summary .value { font-weight: bold; }
        table.report { width: 100%; border-collapse: collapse; }
        table.report th { background: #e8eefb; border: 1px solid #94a3b8; padding: 4px 5px; font-size: 8px; text-align: left; }
        table.report td { border: 1px solid #cbd5e1; padding: 4px 5px; font-size: 8px; vertical-align: top; }
        table.report tr:nth-child(even) td { background: #f8fafc; }
        .right { text-align: right; white-space: nowrap; }
        .center { text-align: center; }
        .empty { text-align: center; padding: 16px; color: #64748b; font-size: 10px; }
        .footer { margin-top: 12px; font-size: 8px; color: #64748b; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Laporan Riwayat Servis FixPoint Service</h1>
        <p class="subtitle">Sistem Informasi Booking &amp; Manajemen Jasa Servis Laptop dan PC</p>
    </div>

    <table class="meta">
        <tr>
            <td><strong>Periode:</strong> {{ $periodLabel }}</td>
            <td><strong>Status:</strong> Servis selesai (COMPLETED)</td>
            <td><strong>Pencarian:</strong> {{ $search !== '' ? $search : '-' }}</td>
            <td><strong>Dicetak:</strong> {{ $printedAt->locale('id')->translatedFormat('d F Y, H:i') }} WIB</td>
        </tr>
    </table>

    <table class="summary">
        <tr>
            <td class="label">Jumlah servis selesai</td>
            <td class="value">{{ $records->count() }} servis</td>
            <td class="label">Total biaya akhir</td>
            <td class="value">Rp {{ number_format($totalBiaya, 0, ',', '.') }}</td>
        </tr>
    </table>

    <table class="report">
        <thead>
            <tr>
                <th style="width:20px;">No</th>
                <th>Tanggal Selesai</th>
                <th>Service ID</th>
                <th>Pelanggan</th>
                <th>No. Telepon</th>
                <th>Perangkat</th>
                <th>Layanan</th>
                <th>Keluhan Awal</th>
                <th>Tgl Booking</th>
                <th>Diagnosis</th>
                <th class="right">Estimasi</th>
                <th class="right">Biaya Akhir</th>
                <th>Pembayaran</th>
            </tr>
        </thead>
        <tbody>
            @forelse($records as $record)
                <tr>
                    <td class="center">{{ $loop->iteration }}</td>
                    <td>{{ $record->completed_at ? \Carbon\Carbon::parse($record->completed_at)->locale('id')->translatedFormat('d M Y, H:i') : '-' }}</td>
                    <td>{{ $record->service_id_code }}</td>
                    <td>{{ $record->customer_name ?? '-' }}</td>
                    <td>{{ $record->phone_number ?? '-' }}</td>
                    <td>{{ trim(($record->device_type ?? '') . ' ' . ($record->brand ?? '') . ' ' . ($record->model ?? '')) ?: '-' }}</td>
                    <td>{{ $record->service_name ?? '-' }}</td>
                    <td>{{ $record->complaint ?? '-' }}</td>
                    <td>{{ $record->booking_date ? \Carbon\Carbon::parse($record->booking_date)->locale('id')->translatedFormat('d M Y') : '-' }}</td>
                    <td>{{ $record->diagnosis ?? '-' }}</td>
                    <td class="right">{{ $record->estimated_cost !== null ? 'Rp ' . number_format((float) $record->estimated_cost, 0, ',', '.') : '-' }}</td>
                    <td class="right">Rp {{ number_format((float) ($record->final_cost ?? 0), 0, ',', '.') }}</td>
                    <td>{{ $record->payment_method ?? '-' }} · {{ $record->payment_status === 'PAID' ? 'Lunas' : 'Belum Bayar' }}</td>
                </tr>
            @empty
                <tr><td colspan="13" class="empty">Tidak ada riwayat servis pada periode ini.</td></tr>
            @endforelse
        </tbody>
    </table>

    <p class="footer">Dokumen ini dihasilkan otomatis oleh FixPoint Service berdasarkan data pada database. Periode laporan mengikuti tanggal selesai servis.</p>
</body>
</html>
