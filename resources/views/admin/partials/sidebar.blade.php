<aside class="admin-sidebar">
    <div class="admin-brand">
        <div class="admin-brand-mark">FP</div>
        <div>
            <div class="admin-brand-name">FixPoint Service</div>
            <div class="admin-brand-caption">Panel Administrasi</div>
        </div>
    </div>

    <button class="admin-sidebar-toggle" id="adminSidebarToggle" type="button" aria-controls="adminSidebarNav" aria-expanded="false">
        <span>Menu Admin</span>
        <span aria-hidden="true">☰</span>
    </button>

    <nav class="admin-nav" id="adminSidebarNav">
        <div class="admin-nav-group">
            <div class="admin-nav-label">Menu Utama</div>
            <a href="{{ route('admin.dashboard') }}" class="admin-nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <span class="admin-nav-icon">▦</span> Dashboard
            </a>
        </div>

        <div class="admin-nav-group">
            <div class="admin-nav-label">Operasional</div>
            <a href="{{ route('admin.schedules.index') }}" class="admin-nav-link {{ request()->routeIs('admin.schedules.*') ? 'active' : '' }}">
                <span class="admin-nav-icon">＋</span> Tambah Jadwal Servis
            </a>
            <a href="{{ route('admin.service-process') }}" class="admin-nav-link {{ request()->routeIs('admin.service-process') ? 'active' : '' }}">
                <span class="admin-nav-icon">▤</span> Kelola Proses Servis
            </a>
        </div>

        <div class="admin-nav-group">
            <div class="admin-nav-label">Data Master</div>
            <a href="{{ route('admin.data-customer') }}" class="admin-nav-link {{ request()->routeIs('admin.data-customer') ? 'active' : '' }}">
                <span class="admin-nav-icon">♙</span> Data Customer
            </a>
            <a href="{{ route('admin.data-perangkat') }}" class="admin-nav-link {{ request()->routeIs('admin.data-perangkat') ? 'active' : '' }}">
                <span class="admin-nav-icon">▣</span> Data Perangkat
            </a>
            <a href="{{ route('admin.data-service') }}" class="admin-nav-link {{ request()->routeIs('admin.data-service') ? 'active' : '' }}">
                <span class="admin-nav-icon">⚙</span> Data Service
            </a>
        </div>

        <div class="admin-nav-group">
            <div class="admin-nav-label">Transaksi</div>
            <a href="{{ route('admin.payments') }}" class="admin-nav-link {{ request()->routeIs('admin.payments') ? 'active' : '' }}">
                <span class="admin-nav-icon">▤</span> Pembayaran
            </a>
        </div>

        <div class="admin-nav-group">
            <div class="admin-nav-label">Laporan</div>
            <a href="{{ route('admin.service-history') }}" class="admin-nav-link {{ request()->routeIs('admin.service-history') ? 'active' : '' }}">
                <span class="admin-nav-icon">◷</span> Riwayat Servis
            </a>
        </div>
    </nav>
</aside>
