@extends('layouts.admin')

@section('title', 'Data Perangkat')

@section('admin-content')
<div class="admin-page-header">
    <div>
        <div class="admin-eyebrow">Data Master</div>
        <h1 class="admin-page-title">Data Perangkat</h1>
        <p class="admin-page-subtitle">Lihat perangkat yang pernah didaftarkan pelanggan beserta keluhan dan riwayat jumlah booking.</p>
    </div>
</div>

<section class="admin-card">
    <div class="admin-toolbar">
        <form action="{{ route('admin.data-perangkat') }}" method="GET" class="admin-search">
            <input type="text" name="q" class="admin-input" value="{{ $search }}" placeholder="Cari pelanggan, tipe, merek, atau model...">
            <button type="submit" class="admin-btn admin-btn-primary">Cari</button>
        </form>
        <span class="admin-badge badge-blue">{{ $devices->total() }} perangkat</span>
    </div>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead><tr><th>Pelanggan</th><th>Perangkat</th><th>Merek / Model</th><th>Keluhan Awal</th><th>Nomor Telepon</th><th>Total Booking</th></tr></thead>
            <tbody>
                @forelse($devices as $device)
                    @php
                        $deviceLabel = ($device->brand && $device->model && $device->brand !== $device->model)
                            ? trim($device->brand . ' ' . $device->model)
                            : ($device->brand ?: ($device->model ?: '—'));
                    @endphp
                    <tr>
                        <td><span class="admin-primary-text">{{ $device->customer_name }}</span><div class="admin-muted">ID Perangkat: {{ $device->device_id }}</div></td>
                        <td>{{ $device->device_type }}</td>
                        <td>{{ $deviceLabel }}</td>
                        <td style="min-width:190px;max-width:280px;white-space:normal;">{{ $device->complaint ?: '—' }}</td>
                        <td>{{ $device->phone_number }}</td>
                        <td>{{ $device->booking_count }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="admin-empty"><strong>Data perangkat tidak ditemukan</strong>Data perangkat akan muncul setelah pelanggan membuat booking.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="admin-pagination">{{ $devices->links() }}</div>
</section>
@endsection
