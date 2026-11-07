@extends('layouts.admin')

@section('title', 'Data Customer')

@section('admin-content')
<div class="admin-page-header">
    <div>
        <div class="admin-eyebrow">Data Master</div>
        <h1 class="admin-page-title">Data Customer</h1>
        <p class="admin-page-subtitle">Lihat data pelanggan yang tersimpan dari proses booking dan jumlah perangkat maupun booking miliknya.</p>
    </div>
</div>

<section class="admin-card">
    <div class="admin-toolbar">
        <form action="{{ route('admin.data-customer') }}" method="GET" class="admin-search">
            <input type="text" name="q" class="admin-input" value="{{ $search }}" placeholder="Cari nama atau nomor telepon pelanggan...">
            <button type="submit" class="admin-btn admin-btn-primary">Cari</button>
        </form>
        <span class="admin-badge badge-blue">{{ $customers->total() }} pelanggan</span>
    </div>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead><tr><th>Nama Customer</th><th>Nomor Telepon</th><th>Perangkat</th><th>Total Booking</th></tr></thead>
            <tbody>
                @forelse($customers as $customer)
                    <tr>
                        <td><span class="admin-primary-text">{{ $customer->name }}</span><div class="admin-muted">ID: {{ $customer->customer_id }}</div></td>
                        <td>{{ $customer->phone_number }}</td>
                        <td>{{ $customer->device_count }}</td>
                        <td>{{ $customer->booking_count }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4"><div class="admin-empty"><strong>Data customer tidak ditemukan</strong>Coba gunakan kata kunci pencarian lain.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="admin-pagination">{{ $customers->links() }}</div>
</section>

<div class="admin-callout"><strong>i</strong><span>Data pelanggan dibuat atau diperbarui melalui proses booking. Halaman ini berfungsi untuk melihat dan mencari data customer, sehingga tidak membuat data pelanggan ganda di luar alur booking.</span></div>
@endsection
