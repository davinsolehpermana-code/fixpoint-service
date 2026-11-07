@extends('layouts.admin')

@section('title', 'Data Service')

@section('admin-content')
<div class="admin-page-header">
    <div>
        <div class="admin-eyebrow">Data Master</div>
        <h1 class="admin-page-title">Data Service</h1>
        <p class="admin-page-subtitle">Kelola kategori layanan yang ditampilkan pada formulir booking pelanggan.</p>
    </div>
</div>

<div class="admin-grid-form">
    <section class="admin-card">
        <div class="admin-card-header">
            <div><h2 class="admin-card-title">Tambah Layanan</h2><p class="admin-card-description">Layanan aktif akan tersedia untuk dipilih pelanggan.</p></div>
        </div>
        <form action="{{ route('admin.data-service.store') }}" method="POST">
            @csrf
            <div class="admin-form-grid">
                <div class="admin-form-group full"><label class="admin-label" for="new_service_name">Nama Layanan</label><input class="admin-input" type="text" id="new_service_name" name="name" value="{{ old('name') }}" maxlength="255" required placeholder="Contoh: Perbaikan Hardware"></div>
                <div class="admin-form-group full"><label class="admin-label" for="new_service_description">Deskripsi</label><textarea class="admin-textarea" id="new_service_description" name="description" maxlength="2000" placeholder="Jelaskan cakupan layanan...">{{ old('description') }}</textarea></div>
                <div class="admin-form-group"><label class="admin-label" for="new_service_price">Harga Estimasi (Rp)</label><input class="admin-input" type="number" id="new_service_price" name="estimated_price" min="0" step="0.01" placeholder="Opsional"></div>
                <div class="admin-form-group" style="justify-content:flex-end;"><label class="admin-label" style="display:flex;align-items:center;gap:8px;"><input type="checkbox" name="is_active" value="1" checked> Aktif dan tampil di booking</label></div>
                <div class="admin-form-group full"><button type="submit" class="admin-btn admin-btn-primary" style="width:100%;">＋ Simpan Layanan</button></div>
            </div>
        </form>
    </section>
    <aside class="admin-card">
        <h2 class="admin-card-title" style="margin-bottom:12px;">Informasi Data Service</h2>
        <div class="admin-callout"><strong>i</strong><span>Nama dan deskripsi layanan tampil pada pilihan kategori booking. Harga estimasi hanya informasi awal dan bukan biaya akhir servis.</span></div>
        <div class="admin-callout" style="margin-top:10px;background:#fffbeb;color:#92400e;"><strong>!</strong><span>Gunakan Aktif/Nonaktif untuk mengatur layanan yang tersedia. Jangan menghapus layanan yang sudah pernah digunakan booking agar riwayat tetap terjaga.</span></div>
    </aside>
</div>

<section class="admin-card">
    <div class="admin-card-header">
        <div><h2 class="admin-card-title">Daftar Layanan</h2><p class="admin-card-description">Ubah informasi layanan atau nonaktifkan layanan yang tidak lagi tersedia.</p></div>
        <span class="admin-badge badge-blue">{{ $services->total() }} layanan</span>
    </div>
    <div class="admin-toolbar">
        <form action="{{ route('admin.data-service') }}" method="GET" class="admin-search">
            <input type="text" name="q" class="admin-input" value="{{ $search }}" placeholder="Cari nama atau deskripsi layanan...">
            <button type="submit" class="admin-btn admin-btn-primary">Cari</button>
        </form>
    </div>
    <div style="display:grid;gap:12px;">
        @forelse($services as $service)
            <details style="border:1px solid #e5e7eb;border-radius:11px;padding:14px 15px;background:#fff;">
                <summary style="cursor:pointer;display:flex;align-items:center;justify-content:space-between;gap:12px;list-style:none;">
                    <div style="min-width:0;">
                        <div class="admin-primary-text">{{ $service->name }}</div>
                        <div class="admin-muted" style="margin-top:4px;">{{ $service->description ?: 'Tidak ada deskripsi' }}</div>
                    </div>
                    <div class="admin-inline-actions">
                        <span class="admin-badge {{ $service->is_active ? 'badge-green' : 'badge-gray' }}">{{ $service->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                        <span class="admin-muted">{{ $service->estimated_price !== null ? 'Rp ' . number_format((float) $service->estimated_price, 0, ',', '.') : 'Harga ditentukan setelah diagnosis' }}</span>
                    </div>
                </summary>
                <div style="border-top:1px solid #eef2f7;margin-top:13px;padding-top:14px;">
                    <form action="{{ route('admin.data-service.update', $service->service_id) }}" method="POST">
                        @csrf @method('PUT')
                        <div class="admin-form-grid">
                            <div class="admin-form-group full"><label class="admin-label">Nama Layanan</label><input class="admin-input" type="text" name="name" value="{{ $service->name }}" maxlength="255" required></div>
                            <div class="admin-form-group full"><label class="admin-label">Deskripsi</label><textarea class="admin-textarea" name="description" maxlength="2000">{{ $service->description }}</textarea></div>
                            <div class="admin-form-group"><label class="admin-label">Harga Estimasi (Rp)</label><input class="admin-input" type="number" name="estimated_price" min="0" step="0.01" value="{{ $service->estimated_price }}"></div>
                            <div class="admin-form-group" style="justify-content:flex-end;"><label class="admin-label" style="display:flex;align-items:center;gap:8px;"><input type="checkbox" name="is_active" value="1" {{ $service->is_active ? 'checked' : '' }}> Aktif</label></div>
                            <div class="admin-form-group full"><button type="submit" class="admin-btn admin-btn-primary">Simpan Perubahan</button></div>
                        </div>
                    </form>
                    <form action="{{ route('admin.data-service.toggle', $service->service_id) }}" method="POST" style="margin-top:10px;">
                        @csrf @method('PATCH')
                        <button type="submit" class="admin-btn admin-btn-outline">{{ $service->is_active ? 'Nonaktifkan Layanan' : 'Aktifkan Layanan' }}</button>
                    </form>
                </div>
            </details>
        @empty
            <div class="admin-empty"><strong>Belum ada layanan</strong>Tambahkan layanan melalui form di atas.</div>
        @endforelse
    </div>
    <div class="admin-pagination">{{ $services->links() }}</div>
</section>
@endsection
