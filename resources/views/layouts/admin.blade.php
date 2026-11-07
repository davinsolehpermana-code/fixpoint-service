@extends('layouts.app')

@section('content')
<style>
    .admin-shell{max-width:1500px;margin:0 auto;padding:28px 24px 48px;display:grid;grid-template-columns:250px minmax(0,1fr);gap:22px;align-items:start;color:#111827}
    .admin-sidebar{position:sticky;top:94px;background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:18px;box-shadow:0 5px 18px rgba(15,23,42,.04)}
    .admin-brand{display:flex;align-items:center;gap:11px;padding:2px 2px 18px;border-bottom:1px solid #eef2f7;margin-bottom:16px}
    .admin-brand-mark{width:40px;height:40px;border-radius:12px;background:#eff6ff;color:#2563eb;display:grid;place-items:center;font-size:17px;font-weight:900}
    .admin-brand-name{font-size:15px;font-weight:800;letter-spacing:-.2px}.admin-brand-caption{font-size:11px;color:#94a3b8;margin-top:2px}
    .admin-sidebar-toggle{display:none;width:100%;border:1px solid #e2e8f0;background:#f8fafc;color:#334155;border-radius:9px;padding:10px 12px;font-weight:700;text-align:left}
    .admin-nav-group{margin-top:15px}.admin-nav-label{font-size:10px;letter-spacing:.08em;text-transform:uppercase;font-weight:800;color:#94a3b8;margin:0 0 7px 10px}
    .admin-nav{display:flex;flex-direction:column;gap:4px}.admin-nav-link{display:flex;align-items:center;gap:10px;padding:10px 11px;border-radius:9px;color:#475569;font-size:13px;font-weight:650;transition:.18s ease;text-decoration:none}
    .admin-nav-link:hover{background:#eff6ff;color:#2563eb}.admin-nav-link.active{background:#2563eb;color:#fff;box-shadow:0 4px 10px rgba(37,99,235,.16)}
    .admin-nav-icon{width:18px;text-align:center;font-size:14px;flex-shrink:0}
    .admin-main{min-width:0}.admin-page-header{display:flex;justify-content:space-between;align-items:flex-start;gap:18px;margin-bottom:22px}
    .admin-eyebrow{font-size:11px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:#2563eb;margin-bottom:5px}
    .admin-page-title{font-size:clamp(23px,2.4vw,30px);line-height:1.2;font-weight:800;letter-spacing:-.7px;color:#0f172a;margin:0 0 7px}
    .admin-page-subtitle{font-size:13px;color:#64748b;margin:0;max-width:720px}
    .admin-card{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:19px;margin-bottom:18px;box-shadow:0 4px 15px rgba(15,23,42,.025);min-width:0}
    .admin-card-header{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:15px}.admin-card-title{font-size:15px;font-weight:800;color:#111827;margin:0}.admin-card-description{font-size:12px;color:#64748b;margin:4px 0 0}
    .admin-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:13px;margin-bottom:18px}.admin-stat{background:#fff;border:1px solid #e5e7eb;border-radius:13px;padding:16px;min-width:0}.admin-stat-top{display:flex;align-items:center;justify-content:space-between;gap:8px;color:#64748b;font-size:11px;font-weight:700}.admin-stat-icon{width:31px;height:31px;border-radius:9px;background:#eff6ff;color:#2563eb;display:grid;place-items:center;font-size:14px}.admin-stat-value{font-size:26px;font-weight:850;letter-spacing:-.8px;margin-top:10px;color:#0f172a;line-height:1.1}.admin-stat-note{font-size:11px;color:#94a3b8;margin-top:5px}
    .admin-grid-2{display:grid;grid-template-columns:minmax(0,1.5fr) minmax(280px,1fr);gap:18px;align-items:start}.admin-grid-form{display:grid;grid-template-columns:minmax(0,1.25fr) minmax(300px,.8fr);gap:18px;align-items:start}
    .admin-table-wrap{width:100%;overflow-x:auto;border:1px solid #eef2f7;border-radius:10px}.admin-table{width:100%;border-collapse:collapse;min-width:680px}.admin-table th{background:#f8fafc;color:#64748b;text-align:left;font-size:10px;letter-spacing:.04em;text-transform:uppercase;font-weight:800;padding:12px 13px;border-bottom:1px solid #e5e7eb;white-space:nowrap}.admin-table td{padding:13px;border-bottom:1px solid #f1f5f9;color:#334155;font-size:12px;vertical-align:middle}.admin-table tr:last-child td{border-bottom:0}.admin-table tbody tr:hover{background:#fbfdff}
    .admin-id{font-weight:800;color:#2563eb;white-space:nowrap}.admin-muted{font-size:11px;color:#94a3b8}.admin-primary-text{font-size:12px;font-weight:750;color:#0f172a}.admin-badge{display:inline-flex;align-items:center;gap:5px;padding:5px 9px;border-radius:999px;font-size:10px;line-height:1.2;font-weight:800;white-space:nowrap}.badge-blue{background:#dbeafe;color:#1d4ed8}.badge-green{background:#dcfce7;color:#166534}.badge-yellow{background:#fef3c7;color:#92400e}.badge-red{background:#fee2e2;color:#991b1b}.badge-gray{background:#f1f5f9;color:#475569}
    .admin-btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;border:0;border-radius:9px;padding:10px 14px;font-size:12px;font-weight:750;text-decoration:none;cursor:pointer;transition:.18s ease;white-space:nowrap}.admin-btn:hover{transform:translateY(-1px)}.admin-btn-primary{background:#2563eb;color:#fff}.admin-btn-primary:hover{background:#1d4ed8}.admin-btn-soft{background:#eff6ff;color:#1d4ed8}.admin-btn-success{background:#16a34a;color:#fff}.admin-btn-danger{background:#fef2f2;color:#b91c1c}.admin-btn-outline{background:#fff;color:#475569;border:1px solid #dbe2ea}
    .admin-form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:13px}.admin-form-group{display:flex;flex-direction:column;gap:6px;min-width:0}.admin-form-group.full{grid-column:1/-1}.admin-label{font-size:11px;font-weight:750;color:#475569}.admin-input,.admin-select,.admin-textarea{width:100%;min-width:0;border:1px solid #d8dee8;border-radius:9px;background:#fff;color:#0f172a;padding:10px 12px;font:inherit;font-size:12px;outline:none;transition:border .15s,box-shadow .15s}.admin-input:focus,.admin-select:focus,.admin-textarea:focus{border-color:#60a5fa;box-shadow:0 0 0 3px rgba(59,130,246,.10)}.admin-textarea{resize:vertical;min-height:84px}.admin-help{font-size:10px;color:#94a3b8}
    .admin-alert{padding:11px 13px;border-radius:9px;font-size:12px;font-weight:650;margin-bottom:14px}.admin-alert-success{background:#f0fdf4;border:1px solid #bbf7d0;color:#166534}.admin-alert-error{background:#fef2f2;border:1px solid #fecaca;color:#991b1b}.admin-empty{padding:28px 16px;text-align:center;color:#94a3b8;font-size:12px}.admin-empty strong{display:block;color:#475569;font-size:13px;margin-bottom:4px}
    .admin-toolbar{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:14px}.admin-search{display:flex;align-items:center;gap:8px;flex:1;max-width:430px}.admin-search .admin-input{flex:1}.admin-filter{display:flex;align-items:center;gap:7px;flex-wrap:wrap}.admin-filter-link{padding:7px 11px;border:1px solid #e2e8f0;border-radius:8px;color:#64748b;font-size:11px;font-weight:700;text-decoration:none}.admin-filter-link.active{background:#eff6ff;border-color:#bfdbfe;color:#1d4ed8}
    .admin-pagination{margin-top:14px}.admin-pagination nav{display:flex;justify-content:flex-end}
    .admin-callout{display:flex;align-items:flex-start;gap:10px;padding:12px 13px;border-radius:10px;background:#eff6ff;color:#1e40af;font-size:11px;line-height:1.5}.admin-inline-actions{display:flex;align-items:center;gap:7px;flex-wrap:wrap}.admin-detail-list{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.admin-detail-item{background:#f8fafc;border:1px solid #eef2f7;border-radius:9px;padding:11px}.admin-detail-label{font-size:10px;color:#94a3b8;font-weight:700}.admin-detail-value{font-size:12px;color:#0f172a;font-weight:750;margin-top:4px;word-break:break-word}
    @media(max-width:1100px){.admin-stats{grid-template-columns:repeat(2,minmax(0,1fr))}.admin-grid-2,.admin-grid-form{grid-template-columns:1fr}}
    @media(max-width:800px){.admin-shell{grid-template-columns:1fr;padding:19px 15px 36px;gap:15px}.admin-sidebar{position:static;padding:13px}.admin-brand{padding-bottom:12px;margin-bottom:10px}.admin-sidebar-toggle{display:flex;align-items:center;justify-content:space-between}.admin-nav{display:none;grid-template-columns:repeat(2,minmax(0,1fr));margin-top:10px}.admin-nav.active{display:grid}.admin-nav-group{margin-top:8px}.admin-page-header{flex-direction:column}.admin-card{padding:15px}}
    @media(max-width:520px){.admin-stats{grid-template-columns:repeat(2,minmax(0,1fr));gap:9px}.admin-stat{padding:12px}.admin-stat-value{font-size:23px}.admin-nav{grid-template-columns:1fr}.admin-form-grid,.admin-detail-list{grid-template-columns:1fr}.admin-form-group.full{grid-column:auto}.admin-toolbar{align-items:stretch}.admin-search{max-width:none;width:100%}}
</style>

<div class="admin-shell">
    @include('admin.partials.sidebar')
    <main class="admin-main">
        @if(session('success'))
            <div class="admin-alert admin-alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="admin-alert admin-alert-error">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="admin-alert admin-alert-error">
                <strong>Periksa kembali data yang dimasukkan:</strong>
                <ul style="margin:7px 0 0 18px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @yield('admin-content')
    </main>
</div>

<script>
    (() => {
        const toggle = document.getElementById('adminSidebarToggle');
        const nav = document.getElementById('adminSidebarNav');
        if (!toggle || !nav) return;
        toggle.addEventListener('click', () => {
            const expanded = toggle.getAttribute('aria-expanded') === 'true';
            toggle.setAttribute('aria-expanded', String(!expanded));
            nav.classList.toggle('active', !expanded);
        });
    })();
</script>
@endsection
