<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('code') · FixPoint Service</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: #f1f5f9;
            color: #0f172a;
            font-family: 'Segoe UI', system-ui, -apple-system, Arial, sans-serif;
        }
        .error-card {
            width: 100%;
            max-width: 520px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 32px 28px;
            box-shadow: 0 18px 40px -28px rgba(15, 23, 42, .45);
            text-align: center;
        }
        .error-brand {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            font-weight: 800;
            color: #1d4ed8;
            font-size: 14px;
            margin-bottom: 18px;
        }
        .error-brand-mark {
            width: 32px;
            height: 32px;
            border-radius: 9px;
            background: #1d4ed8;
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }
        .error-code {
            font-size: 52px;
            font-weight: 800;
            line-height: 1;
            color: #1d4ed8;
            margin: 0 0 10px;
        }
        .error-title { font-size: 19px; margin: 0 0 8px; }
        .error-message { margin: 0 0 22px; color: #475569; font-size: 14px; line-height: 1.6; }
        .error-actions { display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; }
        .error-btn {
            display: inline-block;
            padding: 10px 18px;
            border-radius: 9px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            border: 1px solid transparent;
        }
        .error-btn-primary { background: #1d4ed8; color: #fff; }
        .error-btn-outline { border-color: #cbd5e1; color: #1e293b; background: #fff; }
        .error-note { margin: 22px 0 0; font-size: 11px; color: #94a3b8; }
    </style>
</head>
<body>
    <main class="error-card">
        <div class="error-brand">
            <span class="error-brand-mark">FP</span>
            <span>FixPoint Service</span>
        </div>
        <p class="error-code">@yield('code')</p>
        <h1 class="error-title">@yield('title')</h1>
        <p class="error-message">@yield('message')</p>
        <div class="error-actions">
            <a href="{{ url('/') }}" class="error-btn error-btn-primary">Kembali ke Beranda</a>
            <a href="{{ url('/cek-status') }}" class="error-btn error-btn-outline">Cek Status Servis</a>
        </div>
        <p class="error-note">Jika masalah berlanjut, hubungi Admin FixPoint Service.</p>
    </main>
</body>
</html>
