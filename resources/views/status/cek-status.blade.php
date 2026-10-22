@extends('layouts.app')
@section('title', 'Cek Status Service')
@section('content')
    <style>
        .status-page {
            max-width: 900px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .status-header {
            margin-bottom: 32px;
        }

        .status-label {
            color: #2563eb;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 6px;
            text-transform: uppercase;
        }

        .status-title {
            font-size: 32px;
            color: #111827;
            margin-bottom: 10px;
            font-weight: 800;
        }

        .status-description {
            color: #6b7280;
            font-size: 15px;
        }

        .status-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 28px;
            margin-bottom: 20px;
        }

        .card-title {
            font-size: 18px;
            color: #111827;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .card-description {
            color: #6b7280;
            font-size: 13px;
            margin-bottom: 20px;
        }

        .search-form {
            display: flex;
            gap: 12px;
        }

        .search-input {
            flex: 1;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 12px 14px;
            font-size: 14px;
            outline: none;
        }

        .search-input:focus {
            border-color: #2563eb;
        }

        .search-button {
            border: none;
            background: #2563eb;
            color: #ffffff;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
        }

        .search-button:hover {
            background: #1d4ed8;
        }

        .service-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 24px;
        }

        .service-id {
            color: #2563eb;
            font-size: 20px;
            font-weight: 800;
        }

        .status-badge {
            display: inline-block;
            padding: 7px 11px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
        }

        .status-badge-pending {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .status-badge-waiting {
            background: #fef3c7;
            color: #92400e;
        }

        .status-badge-confirmed {
            background: #d1fae5;
            color: #065f46;
        }

        .status-badge-completed {
            background: #dcfce7;
            color: #166534;
        }

        .status-badge-rejected,
        .status-badge-cancelled {
            background: #fee2e2;
            color: #991b1b;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            margin-bottom: 20px;
        }

        .info-item {
            border: 1px solid #eef0f4;
            border-radius: 9px;
            padding: 14px;
        }

        .info-label {
            color: #6b7280;
            font-size: 11px;
            margin-bottom: 5px;
        }

        .info-value {
            color: #111827;
            font-size: 14px;
            font-weight: 700;
        }

        .progress {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            margin-top: 28px;
            margin-bottom: 20px;
        }

        .progress-item {
            text-align: center;
        }

        .progress-dot {
            width: 14px;
            height: 14px;
            border-radius: 50%;
            margin: 0 auto 8px;
            background: #d1d5db;
        }

        .progress-item.active .progress-dot {
            background: #2563eb;
        }

        .progress-text {
            color: #6b7280;
            font-size: 11px;
        }

        .progress-item.active .progress-text {
            color: #2563eb;
            font-weight: 700;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .detail-box {
            border: 1px solid #eef0f4;
            border-radius: 9px;
            padding: 16px;
        }

        .detail-box.full {
            grid-column: 1 / -1;
        }

        .detail-label {
            color: #6b7280;
            font-size: 11px;
            margin-bottom: 6px;
        }

        .detail-value {
            color: #111827;
            font-size: 14px;
            font-weight: 700;
        }

        .detail-text {
            color: #4b5563;
            font-size: 13px;
            line-height: 1.6;
        }

        .payment-badge {
            display: inline-block;
            padding: 6px 9px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 800;
        }

        .payment-unpaid {
            background: #fef3c7;
            color: #92400e;
        }

        .payment-paid {
            background: #dcfce7;
            color: #166534;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .back-link {
            display: inline-block;
            margin-top: 12px;
            color: #2563eb;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
        }

        .qris-section {
            background: #f0f9ff;
            border: 2px solid #2563eb;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
        }

        .qris-title {
            font-size: 18px;
            font-weight: 800;
            color: #111827;
            margin-bottom: 12px;
            text-align: center;
        }

        .qris-amount {
            font-size: 32px;
            font-weight: 800;
            color: #2563eb;
            text-align: center;
            margin: 16px 0;
        }

        .qris-image {
            max-width: 280px;
            width: 100%;
            margin: 0 auto 16px;
            display: block;
            border-radius: 8px;
        }

        .qris-instruction {
            background: #ffffff;
            border: 1px solid #dbeafe;
            border-radius: 8px;
            padding: 16px;
            margin-top: 16px;
        }

        .qris-instruction h4 {
            font-size: 14px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 8px;
        }

        .qris-instruction ol {
            margin: 0;
            padding-left: 20px;
            color: #4b5563;
            font-size: 13px;
            line-height: 1.8;
        }

        .qris-note {
            background: #fef3c7;
            border: 1px solid #fde68a;
            border-radius: 8px;
            padding: 12px;
            margin-top: 16px;
            font-size: 13px;
            color: #92400e;
            font-weight: 600;
            text-align: center;
        }

        .cash-note {
            background: #ffffff;
            border: 1px solid #dbeafe;
            border-radius: 8px;
            padding: 20px;
            margin-top: 16px;
            text-align: center;
            color: #4b5563;
            font-size: 14px;
            line-height: 1.7;
        }

        @media (max-width: 700px) {
            .status-page {
                padding: 24px 16px;
            }

            .search-form {
                flex-direction: column;
            }

            .info-grid,
            .detail-grid {
                grid-template-columns: 1fr;
            }

            .detail-box.full {
                grid-column: auto;
            }

            .service-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .progress {
                grid-template-columns: repeat(2, 1fr);
                row-gap: 18px;
            }

            .status-title {
                font-size: 26px;
            }

            .search-input,
            .search-button {
                font-size: 16px;
                padding: 14px;
            }

            .qris-amount {
                font-size: 28px;
            }

            .status-card,
            .qris-section {
                padding: 20px;
            }
        }
    </style>

    <div class="status-page">
        <div class="status-header">
            <div class="status-label">FIXPOINT SERVICE</div>
            <h1 class="status-title">Cek Status Service</h1>
            <p class="status-description">Masukkan Service ID untuk melihat perkembangan servis perangkat Anda.</p>
        </div>

        @if (session('error'))
            <div class="alert-error">{{ session('error') }}</div>
        @endif
        @if (session('success'))
            <div class="alert-success">{{ session('success') }}</div>
        @endif

        <div class="status-card">
            <h2 class="card-title">Masukkan Service ID</h2>
            <p class="card-description">Service ID diberikan setelah proses booking selesai.</p>
            <form action="{{ route('cek-status.show') }}" method="GET" class="search-form">
                <input type="text" name="code" class="search-input" placeholder="Contoh: FP-20261011-GWYTW"
                    value="{{ request('code') }}" required>
                <button type="submit" class="search-button">Cek Status</button>
            </form>
        </div>

        {{-- ✅ SEMUA DETAIL BOOKING HARUS BERADA DI DALAM BLOK INI --}}
        @if (isset($booking))
            @php
                $payment = $booking->payment;
                $isPaid =
                    $payment &&
                    $payment->payment_status === 'PAID' &&
                    in_array($payment->payment_method, ['CASH', 'QRIS'], true) &&
                    is_numeric($payment->amount) &&
                    (float) $payment->amount > 0;

                $badgeClass = 'status-badge-pending';
                $statusText = 'Menunggu Konfirmasi';
                if ($booking->status === 'WAITING_VERIFICATION') {
                    $badgeClass = 'status-badge-waiting';
                    $statusText = 'Menunggu Verifikasi';
                } elseif ($booking->status === 'CONFIRMED') {
                    $badgeClass = 'status-badge-confirmed';
                    $statusText = 'Sedang Dikerjakan';
                } elseif ($booking->status === 'COMPLETED') {
                    $badgeClass = 'status-badge-completed';
                    $statusText = 'Selesai';
                } elseif ($booking->status === 'REJECTED') {
                    $badgeClass = 'status-badge-rejected';
                    $statusText = 'Ditolak';
                } elseif ($booking->status === 'CANCELLED') {
                    $badgeClass = 'status-badge-cancelled';
                    $statusText = 'Dibatalkan';
                }
            @endphp

            <div class="status-card">
                <div class="service-header">
                    <div>
                        <div class="info-label">Service ID</div>
                        <div class="service-id">{{ $booking->service_id_code }}</div>
                    </div>
                    <span class="status-badge {{ $badgeClass }}">{{ $statusText }}</span>
                </div>

                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Nama Pelanggan</div>
                        <div class="info-value">{{ $booking->customer->name }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Perangkat</div>
                        <div class="info-value">{{ $booking->device->brand }} </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Jadwal Servis</div>
                        <div class="info-value">
                            {{ \Carbon\Carbon::parse($booking->schedule->schedule_date)->translatedFormat('d F Y') }}
                            ({{ substr($booking->schedule->start_time, 0, 5) }} -
                            {{ substr($booking->schedule->end_time, 0, 5) }})</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Status Pembayaran</div>
                        <div class="info-value">
                            <span class="payment-badge {{ $isPaid ? 'payment-paid' : 'payment-unpaid' }}">
                                {{ $isPaid ? 'Lunas' : 'Belum Bayar' }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="progress">
                    <div
                        class="progress-item {{ in_array($booking->status, ['PENDING', 'WAITING_VERIFICATION', 'CONFIRMED', 'COMPLETED']) ? 'active' : '' }}">
                        <div class="progress-dot"></div>
                        <div class="progress-text">Menunggu</div>
                    </div>
                    <div
                        class="progress-item {{ in_array($booking->status, ['CONFIRMED', 'COMPLETED']) ? 'active' : '' }}">
                        <div class="progress-dot"></div>
                        <div class="progress-text">Pemeriksaan</div>
                    </div>
                    <div class="progress-item {{ $booking->status === 'COMPLETED' ? 'active' : '' }}">
                        <div class="progress-dot"></div>
                        <div class="progress-text">Dikerjakan</div>
                    </div>
                    <div class="progress-item {{ $booking->status === 'COMPLETED' ? 'active' : '' }}">
                        <div class="progress-dot"></div>
                        <div class="progress-text">Selesai</div>
                    </div>
                </div>
            </div>

            <div class="status-card">
                <h2 class="card-title">Detail Service</h2>
                <div class="detail-grid">
                    <div class="detail-box">
                        <div class="detail-label">Diagnosis</div>
                        <div class="detail-text">Hasil diagnosis akan ditampilkan setelah proses pemeriksaan perangkat.
                        </div>
                    </div>
                    <div class="detail-box">
                        <div class="detail-label">Estimasi Biaya</div>
                        <div class="detail-value">
                            {{ $payment && $payment->amount > 0 ? 'Rp ' . number_format($payment->amount, 0, ',', '.') : 'Menunggu pemeriksaan' }}
                        </div>
                    </div>
                    <div class="detail-box full">
                        <div class="detail-label">Keluhan Awal</div>
                        <div class="detail-text">
                            {{ $booking->device->complaint ?? 'Belum ada keluhan.' }}
                        </div>
                    </div>

                    <div class="detail-box full">
                        <div class="detail-label">Catatan Admin</div>
                        <div class="detail-text"
                            style="color: {{ filled($booking->notes) ? '#111827' : '#9ca3af' }};
                font-style: {{ filled($booking->notes) ? 'normal' : 'italic' }};">
                            {{ filled($booking->notes) ? $booking->notes : 'Belum ada catatan.' }}
                        </div>
                    </div>
                    <div class="detail-box">
                        <div class="detail-label">Layanan</div>
                        <div class="detail-value">{{ $booking->service->name }}</div>
                    </div>
                </div>
            </div>

            {{-- BAGIAN PEMBATALAN --}}
            @if (in_array($booking->status, ['PENDING', 'WAITING_VERIFICATION']))
                <div class="status-card" style="border: 1px solid #fca5a5; background-color: #fef2f2;">
                    <h2 class="card-title" style="color: #991b1b;">Batalkan Booking Service</h2>
                    <p class="card-description" style="color: #991b1b;">Ingin membatalkan booking ini? Silakan berikan
                        alasan pembatalan Anda di bawah ini.</p>
                    <form action="{{ route('cek-status.cancel', $booking->service_id_code) }}" method="POST">
                        @csrf
                        <div style="margin-bottom: 16px;">
                            <label for="cancellation_reason"
                                style="display: block; font-size: 13px; font-weight: 700; color: #374151; margin-bottom: 6px;">Alasan
                                Pembatalan</label>
                            <textarea name="cancellation_reason" id="cancellation_reason" class="search-input"
                                style="width: 100%; box-sizing: border-box; min-height: 80px;" placeholder="Tuliskan alasan pembatalan..." required>{{ old('cancellation_reason') }}</textarea>
                        </div>
                        <button type="submit" onclick="return confirm('Apakah Anda yakin ingin membatalkan booking ini?')"
                            style="background-color: #dc2626; color: white; border: none; padding: 10px 18px; border-radius: 8px; font-weight: 700; cursor: pointer; font-size: 14px;">Konfirmasi
                            Pembatalan</button>
                    </form>
                </div>
            @endif

            @if ($booking->status === 'CANCELLED')
                <div class="status-card" style="background: #fee2e2; border: 1px solid #fecaca;">
                    <h3 style="color: #991b1b; font-size: 16px; font-weight: 800; margin-bottom: 6px;">Status: Booking Telah
                        Dibatalkan</h3>
                    <p style="color: #7f1d1d; font-size: 13px; margin: 0;"><strong>Alasan Pembatalan:</strong>
                        {{ $booking->cancellation_reason ?? 'Tidak ada alasan yang tertera.' }}</p>
                </div>
            @endif

            {{-- ✅ BAGIAN PEMBAYARAN YANG SUDAH DIGABUNG & DIRAPIKAN --}}
            @if ($booking->status === 'COMPLETED' && !$isPaid)
                <div class="qris-section">
                    <h3 class="qris-title">Pembayaran Servis</h3>

                    <div
                        style="background: #ffffff; border: 1px solid #bae6fd; border-radius: 8px; padding: 16px; margin-bottom: 16px; text-align: center;">
                        <div style="font-size: 13px; color: #0369a1; margin-bottom: 4px;">Total Tagihan:</div>
                        <div style="font-size: 28px; font-weight: 800; color: #0284c7;">Rp
                            {{ number_format($payment->amount ?? 0, 0, ',', '.') }}</div>
                    </div>

                    @if (($payment->payment_method ?? '') === 'CASH')
                        {{-- KALKULATOR CASH --}}
                        <div style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px;">
                            <h4 style="font-size: 14px; font-weight: 700; margin-bottom: 12px; color: #111827;">💵
                                Kalkulator Kembalian Tunai</h4>
                            <div style="margin-bottom: 12px;">
                                <label
                                    style="font-size: 12px; font-weight: 600; color: #4b5563; display: block; margin-bottom: 6px;">Uang
                                    yang Anda Bayarkan (Rp)</label>
                                <input type="number" id="cash-input" class="form-control"
                                    placeholder="Masukkan nominal uang..."
                                    style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 15px; font-weight: 700; box-sizing: border-box;">
                            </div>
                            <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px;">
                                <button type="button" onclick="setCash(100000)"
                                    style="background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 700; cursor: pointer;">Rp
                                    100.000</button>
                                <button type="button" onclick="setCash(200000)"
                                    style="background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 700; cursor: pointer;">Rp
                                    200.000</button>
                                <button type="button" onclick="setCash(500000)"
                                    style="background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 700; cursor: pointer;">Rp
                                    500.000</button>
                                <button type="button" onclick="setCash(1000000)"
                                    style="background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 700; cursor: pointer;">Rp
                                    1.000.000</button>
                            </div>
                            <div id="calc-result"
                                style="background: #f9fafb; border-radius: 8px; padding: 12px; text-align: center;">
                                <div style="font-size: 12px; color: #6b7280;">Kembalian Anda:</div>
                                <div id="change-amount" style="font-size: 20px; font-weight: 800; color: #16a34a;">Rp 0
                                </div>
                            </div>
                        </div>
                        <p style="text-align: center; font-size: 12px; color: #6b7280; margin-top: 12px;">💡 Tunjukkan
                            kalkulasi ini ke kasir saat pembayaran di toko.</p>
                    @elseif(($payment->payment_method ?? '') === 'QRIS')
                        {{-- UPLOAD BUKTI QRIS --}}
                        @if ($payment && $payment->payment_status === 'WAITING_VERIFICATION')
                            <div
                                style="background: #fef3c7; border: 1px solid #fde68a; border-radius: 8px; padding: 16px; text-align: center; color: #92400e;">
                                <strong>⏳ Menunggu Verifikasi Admin</strong><br>
                                <span style="font-size: 13px;">Bukti pembayaran Anda sedang diperiksa oleh admin. Silakan
                                    tunggu konfirmasi.</span>
                            </div>
                        @else
                            <div style="text-align: center; margin-bottom: 16px;">
                                @if (file_exists(public_path('qris/qris-toko.png')))
                                    <img src="{{ asset('qris/qris-toko.png') }}" alt="QRIS"
                                        style="max-width: 250px; border-radius: 8px; border: 1px solid #e5e7eb;">
                                @else
                                    <p style="color: #dc2626; font-size: 13px;">Gambar QRIS belum tersedia.</p>
                                @endif
                            </div>
                            <form action="{{ route('cek-status.upload-proof') }}" method="POST"
                                enctype="multipart/form-data"
                                style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px;">
                                @csrf
                                <input type="hidden" name="service_id_code" value="{{ $booking->service_id_code }}">
                                <div style="margin-bottom: 12px;">
                                    <label
                                        style="font-size: 12px; font-weight: 600; color: #4b5563; display: block; margin-bottom: 6px;">Upload
                                        Bukti Transfer (Maks 2MB)</label>
                                    <input type="file" name="payment_proof" required
                                        accept="image/jpeg,image/png,image/jpg,image/webp"
                                        style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 13px; box-sizing: border-box;">
                                    @error('payment_proof')
                                        <div style="color: #dc2626; font-size: 12px; margin-top: 4px;">{{ $message }}
                                        </div>
                                    @enderror
                                </div>
                                <button type="submit"
                                    style="width: 100%; background: #2563eb; color: white; border: none; padding: 12px; border-radius: 8px; font-size: 14px; font-weight: 700; cursor: pointer;">Kirim
                                    Bukti Pembayaran</button>
                            </form>
                        @endif
                    @else
                        <div class="cash-note">Metode pembayaran belum dipilih. Silakan hubungi admin untuk informasi
                            pembayaran.</div>
                    @endif
                </div>
            @endif

            {{-- PESAN LUNAS --}}
            @if ($isPaid)
                <div class="status-card" style="background: #dcfce7; border: 2px solid #16a34a;">
                    <h3 style="color: #166534; font-size: 18px; font-weight: 800; margin-bottom: 8px; text-align: center;">
                        ✅ Pembayaran Lunas</h3>
                    <p style="color: #15803d; text-align: center; font-size: 14px; margin: 0;">Terima kasih! Silakan ambil
                        perangkat Anda di toko.</p>
                </div>
            @endif

            <a href="{{ route('cek-status') }}" class="back-link">← Cek Status Lainnya</a>
        @endif {{-- ✅ INI ADALAH PENUTUP @if (isset($booking)) YANG BENAR --}}
    </div>

    <script>
        // Kalkulator Cash Otomatis
        const totalTagihan = {{ $payment->amount ?? 0 }};
        const cashInput = document.getElementById('cash-input');
        const changeAmount = document.getElementById('change-amount');
        const calcResult = document.getElementById('calc-result');

        if (cashInput && changeAmount) {
            window.setCash = function(amount) {
                cashInput.value = amount;
                calculateChange(amount);
            };

            function calculateChange(amount) {
                const bayar = parseInt(amount) || 0;
                const kembalian = bayar - totalTagihan;

                if (kembalian < 0) {
                    changeAmount.textContent = 'Kurang Rp ' + Math.abs(kembalian).toLocaleString('id-ID');
                    changeAmount.style.color = '#dc2626';
                    calcResult.style.background = '#fee2e2';
                } else if (kembalian === 0) {
                    changeAmount.textContent = 'Pas! Tidak ada kembalian';
                    changeAmount.style.color = '#2563eb';
                    calcResult.style.background = '#eff6ff';
                } else {
                    changeAmount.textContent = 'Rp ' + kembalian.toLocaleString('id-ID');
                    changeAmount.style.color = '#16a34a';
                    calcResult.style.background = '#f0fdf4';
                }
            }

            cashInput.addEventListener('input', function(e) {
                calculateChange(e.target.value);
            });
        }
    </script>
@endsection
