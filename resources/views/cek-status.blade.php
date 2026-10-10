@extends('layouts.app')

@section('title', 'Cek Status Service')

@section('content')

<style>
    .status-page { max-width: 900px; margin: 0 auto; }
    .status-header { margin-bottom: 28px; }
    .status-label { color: #2563eb; font-size: 13px; font-weight: 700; margin-bottom: 6px; }
    .status-title { font-size: 32px; color: #111827; margin-bottom: 8px; }
    .status-description { color: #6b7280; max-width: 650px; }
    .status-card { background: #ffffff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px; margin-bottom: 20px; }
    .card-title { font-size: 18px; color: #111827; margin-bottom: 8px; }
    .card-description { color: #6b7280; font-size: 13px; margin-bottom: 18px; }
    .search-form { display: flex; gap: 12px; }
    .search-input { flex: 1; border: 1px solid #d1d5db; border-radius: 8px; padding: 12px 14px; font-size: 14px; outline: none; }
    .search-input:focus { border-color: #2563eb; }
    .search-button { border: none; background: #2563eb; color: #ffffff; padding: 12px 20px; border-radius: 8px; font-size: 14px; font-weight: 700; cursor: pointer; }
    .search-button:hover { background: #1d4ed8; }
    .service-header { display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 20px; }
    .service-id { color: #2563eb; font-size: 20px; font-weight: 800; }
    .status-badge { display: inline-block; padding: 7px 11px; border-radius: 999px; font-size: 11px; font-weight: 800; }
    .status-badge-pending { background: #dbeafe; color: #1d4ed8; }
    .status-badge-waiting { background: #fef3c7; color: #92400e; }
    .status-badge-confirmed { background: #d1fae5; color: #065f46; }
    .status-badge-completed { background: #dcfce7; color: #166534; }
    .status-badge-rejected { background: #fee2e2; color: #991b1b; }
    .info-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px; }
    .info-item { border: 1px solid #eef0f4; border-radius: 9px; padding: 14px; }
    .info-label { color: #6b7280; font-size: 11px; margin-bottom: 5px; }
    .info-value { color: #111827; font-size: 14px; font-weight: 700; }
    .progress { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-top: 24px; }
    .progress-item { text-align: center; }
    .progress-dot { width: 14px; height: 14px; border-radius: 50%; margin: 0 auto 8px; background: #d1d5db; }
    .progress-item.active .progress-dot { background: #2563eb; }
    .progress-text { color: #6b7280; font-size: 11px; }
    .progress-item.active .progress-text { color: #2563eb; font-weight: 700; }
    .detail-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px; }
    .detail-box { border: 1px solid #eef0f4; border-radius: 9px; padding: 16px; }
    .detail-box.full { grid-column: 1 / -1; }
    .detail-label { color: #6b7280; font-size: 11px; margin-bottom: 6px; }
    .detail-value { color: #111827; font-size: 14px; font-weight: 700; }
    .detail-text { color: #4b5563; font-size: 13px; line-height: 1.6; }
    .payment-badge { display: inline-block; padding: 6px 9px; border-radius: 999px; font-size: 10px; font-weight: 800; }
    .payment-unpaid { background: #fef3c7; color: #92400e; }
    .payment-paid { background: #dcfce7; color: #166534; }
    .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; }
    .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; }
    .upload-section { margin-top: 20px; padding-top: 20px; border-top: 1px solid #e5e7eb; }
    .upload-form { display: flex; gap: 10px; align-items: flex-end; }
    .upload-input { width: 100%; border: 1px solid #d1d5db; padding: 8px; border-radius: 8px; font-size: 13px; }
    .back-link { display: inline-block; margin-top: 12px; color: #2563eb; font-size: 13px; font-weight: 600; }
    @media (max-width: 700px) {
        .search-form, .upload-form { flex-direction: column; }
        .info-grid, .detail-grid { grid-template-columns: 1fr; }
        .detail-box.full { grid-column: auto; }
        .service-header { flex-direction: column; align-items: flex-start; }
        .progress { grid-template-columns: repeat(2, 1fr); row-gap: 18px; }
    }
</style>

<div class="status-page">

    <div class="status-header">
        <div class="status-label">FIXPOINT SERVICE</div>
        <h1 class="status-title">Cek Status Service</h1>
        <p class="status-description">Masukkan Service ID untuk melihat perkembangan servis perangkat Anda.</p>
    </div>

    {{-- PESAN ERROR --}}
    @if(session('error'))
        <div class="alert-error">{{ session('error') }}</div>
    @endif

    {{-- PESAN SUKSES --}}
    @if(session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif

    {{-- FORM CEK STATUS --}}
    <div class="status-card">
        <h2 class="card-title">Masukkan Service ID</h2>
        <p class="card-description">Service ID diberikan setelah proses booking selesai.</p>
        
        <form action="{{ route('cek-status.show') }}" method="GET" class="search-form">
            <input 
                type="text" 
                name="code" 
                class="search-input" 
                placeholder="Contoh: FPS-ABC123XY" 
                value="{{ request('code') }}" 
                required
            >
            <button type="submit" class="search-button">Cek Status</button>
        </form>
    </div>

    {{-- HASIL STATUS (Hanya muncul jika ada data booking) --}}
    @if(isset($booking))
    <div class="status-card">
        <div class="service-header">
            <div>
                <div class="info-label">Service ID</div>
                <div class="service-id">{{ $booking->service_id_code }}</div>
            </div>
            
            @php
                $badgeClass = 'status-badge-pending';
                $statusText = 'Menunggu Konfirmasi';
                
                if($booking->status === 'WAITING_VERIFICATION') { 
                    $badgeClass = 'status-badge-waiting'; 
                    $statusText = 'Menunggu Verifikasi'; 
                }
                elseif($booking->status === 'CONFIRMED') { 
                    $badgeClass = 'status-badge-confirmed'; 
                    $statusText = 'Sedang Dikerjakan'; 
                }
                elseif($booking->status === 'COMPLETED') { 
                    $badgeClass = 'status-badge-completed'; 
                    $statusText = 'Selesai'; 
                }
                elseif($booking->status === 'REJECTED') { 
                    $badgeClass = 'status-badge-rejected'; 
                    $statusText = 'Ditolak'; 
                }
            @endphp
            
            <span class="status-badge {{ $badgeClass }}">
                {{ $statusText }}
            </span>
        </div>

        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Nama Pelanggan</div>
                <div class="info-value">{{ $booking->customer->name }}</div>
            </div>

            <div class="info-item">
                <div class="info-label">Perangkat</div>
                <div class="info-value">{{ $booking->device->brand }} {{ $booking->device->model }}</div>
            </div>

            <div class="info-item">
                <div class="info-label">Jadwal Servis</div>
                <div class="info-value">
                    {{ \Carbon\Carbon::parse($booking->schedule->schedule_date)->translatedFormat('d F Y') }}
                    ({{ substr($booking->schedule->start_time, 0, 5) }} - {{ substr($booking->schedule->end_time, 0, 5) }})
                </div>
            </div>

            <div class="info-item">
                <div class="info-label">Status Pembayaran</div>
                <div class="info-value">
                    @php
                        $paymentStatus = ($payment && $payment->payment_status === 'PAID') ? 'Lunas' : 'Belum Bayar';
                    @endphp
                    {{ $paymentStatus }}
                </div>
            </div>
        </div>

        {{-- PROGRESS STATUS --}}
        <div class="progress">
            <div class="progress-item {{ in_array($booking->status, ['PENDING', 'WAITING_VERIFICATION', 'CONFIRMED', 'COMPLETED']) ? 'active' : '' }}">
                <div class="progress-dot"></div>
                <div class="progress-text">Menunggu</div>
            </div>

            <div class="progress-item {{ in_array($booking->status, ['CONFIRMED', 'COMPLETED']) ? 'active' : '' }}">
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

    {{-- DETAIL SERVICE --}}
    <div class="status-card">
        <h2 class="card-title">Detail Service</h2>
        
        <div class="detail-grid">
            <div class="detail-box">
                <div class="detail-label">Diagnosis</div>
                <div class="detail-text">
                    {{ $booking->serviceRecord && $booking->serviceRecord->diagnosis ? $booking->serviceRecord->diagnosis : 'Hasil diagnosis akan ditampilkan setelah proses pemeriksaan perangkat.' }}
                </div>
            </div>

            <div class="detail-box">
                <div class="detail-label">Estimasi Biaya</div>
                <div class="detail-value">
                    {{ $booking->serviceRecord && $booking->serviceRecord->estimated_cost ? 'Rp ' . number_format($booking->serviceRecord->estimated_cost, 0, ',', '.') : 'Menunggu pemeriksaan' }}
                </div>
            </div>

            <div class="detail-box full">
                <div class="detail-label">Keluhan Awal</div>
                <div class="detail-text">{{ $booking->device->complaint }}</div>
            </div>

            {{-- CATATAN ADMIN / UPDATE SERVIS --}}
            <div class="detail-box full">
                <div class="detail-label">Catatan Admin / Update Servis</div>
                <div class="detail-text" style="color: {{ $booking->notes ? '#111827' : '#9ca3af' }}; font-style: {{ $booking->notes ? 'normal' : 'italic' }};">
                    @if($booking->notes && $booking->notes !== '')
                        {{ $booking->notes }}
                    @else
                        Belum ada catatan tambahan dari admin/teknisi.
                    @endif
                </div>
            </div>

            <div class="detail-box">
                <div class="detail-label">Status Pembayaran</div>
                <div>
                    @php
                        $paymentBadgeClass = ($payment && $payment->payment_status === 'PAID') ? 'payment-paid' : 'payment-unpaid';
                        $paymentText = ($payment && $payment->payment_status === 'PAID') ? 'Lunas' : 'Belum Bayar';
                    @endphp
                    <span class="payment-badge {{ $paymentBadgeClass }}">{{ $paymentText }}</span>
                </div>
            </div>

            <div class="detail-box">
                <div class="detail-label">Layanan</div>
                <div class="detail-value">{{ $booking->service->name }}</div>
            </div>
        </div>
    </div>

    {{-- FORM UPLOAD BUKTI BAYAR (Hanya muncul jika status PENDING) --}}
    @if($booking->status === 'PENDING')
    <div class="status-card">
        <h2 class="card-title">Upload Bukti Pembayaran</h2>
        <p class="card-description">Unggah foto struk transfer untuk memverifikasi pembayaran Anda.</p>
        
        <form action="{{ route('cek-status.upload', $booking->service_id_code) }}" method="POST" enctype="multipart/form-data" class="upload-form">
            @csrf
            <div style="flex: 1;">
                <input type="file" name="payment_proof" accept="image/*" class="upload-input" required>
                @error('payment_proof')
                    <span style="color: #dc2626; font-size: 11px; display: block; margin-top: 4px;">{{ $message }}</span>
                @enderror
            </div>
            <button type="submit" class="search-button" style="padding: 10px 16px;">Upload Bukti</button>
        </form>
    </div>
    @endif

    {{-- TAMPILKAN BUKTI JIKA SUDAH UPLOAD --}}
    @if($payment && $payment->payment_proof)
    <div class="status-card">
        <h2 class="card-title">Bukti Pembayaran</h2>
        <img src="{{ asset('storage/' . $payment->payment_proof) }}" alt="Bukti Bayar" style="max-width: 100%; border-radius: 8px; border: 1px solid #e5e7eb;">
    </div>
    @endif

    <a href="{{ route('cek-status') }}" class="back-link">← Cek Status Lainnya</a>
    
    @endif

</div>

@endsection