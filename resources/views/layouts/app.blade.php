<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'FixPoint Service')</title>

    {{-- Font sesuai wireframe (ada fallback jika gagal dimuat) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap"
        rel="stylesheet">

    <style>
        /* ========================================
           RESET & BASE
        ======================================== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: var(--font-sans);
        }

        :root {
            /* Variabel lama (nama tetap, agar halaman lain tidak terpengaruh) */
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --text-main: #0f172a;
            --text-secondary: #64748b;
            --border: #e4e8f5;
            --surface: #ffffff;
            --background: #f5f7ff;
            --danger-bg: #fef2f2;
            --danger-text: #dc2626;

            /* Variabel tambahan untuk tampilan baru */
            --primary-soft: #eaf0ff;
            /* Tipografi: judul/menu, isi data, dan Service ID memakai famili berbeda. */
            --font-display: "Space Grotesk", "IBM Plex Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            --font-sans: "IBM Plex Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            --font-mono: "JetBrains Mono", ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            --shadow-soft: 0 4px 18px rgba(37, 99, 235, 0.06);
        }

        body {
            background-color: var(--background);
            color: var(--text-main);
            line-height: 1.6;
            padding-top: 76px;
            -webkit-font-smoothing: antialiased;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        ul {
            list-style: none;
        }

        button {
            font-family: inherit;
        }


        /* ========================================
           NAVBAR
        ======================================== */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            background: rgba(255, 255, 255, 0.97);
            border-bottom: 1px solid var(--border);
            z-index: 1000;
            box-shadow: var(--shadow-soft);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }

        .nav-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 32px;
            height: 76px;

            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 24px;
        }


        /* ========================================
           LOGO
        ======================================== */
        .nav-logo {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--text-main);
            white-space: nowrap;
            flex-shrink: 0;
            transition: opacity 0.2s ease;
        }

        .nav-logo:hover {
            opacity: 0.85;
        }

        .logo-image {
            height: 44px;
            width: 44px;
            object-fit: cover;
            object-position: center;
            border-radius: 12px;
            flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.15);
        }

        .logo-text {
            display: flex;
            flex-direction: column;
            line-height: 1.15;
        }

        .logo-name {
            font-size: 18px;
            font-weight: 800;
            letter-spacing: -0.4px;
            text-transform: uppercase;
        }

        .logo-name em {
            font-style: normal;
            color: var(--primary);
        }

        .logo-tagline {
            font-family: var(--font-mono);
            font-size: 10px;
            font-weight: 500;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: var(--text-secondary);
            margin-top: 2px;
        }


        /* ========================================
           DESKTOP MENU
        ======================================== */
        .nav-menu {
            display: flex;
            align-items: center;
            gap: 4px;
            min-width: 0;
        }

        .nav-menu li {
            display: flex;
            align-items: center;
        }

        .nav-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;

            padding: 9px 16px;
            border-radius: 8px;

            font-size: 14px;
            font-weight: 500;
            color: #475569;

            white-space: nowrap;
            transition: color 0.2s ease, background-color 0.2s ease;
        }

        .nav-link:hover {
            color: var(--primary);
            background-color: var(--primary-soft);
        }

        /* Menu aktif: pil biru seperti wireframe */
        .nav-link.active {
            background-color: var(--primary);
            color: #ffffff;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
        }

        .nav-link.active:hover {
            background-color: var(--primary-hover);
            color: #ffffff;
        }

        .nav-link:focus-visible,
        .btn-logout:focus-visible,
        .nav-burger:focus-visible {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
        }


        /* ========================================
           LOGOUT BUTTON
        ======================================== */
        .btn-logout {
            display: inline-flex;
            align-items: center;
            justify-content: center;

            background: var(--danger-bg);
            color: var(--danger-text) !important;

            padding: 9px 18px;
            margin-left: 8px;
            border-radius: 8px;
            border: 1px solid #fee2e2;

            font-size: 14px;
            font-weight: 600;
            cursor: pointer;

            transition: background-color 0.2s ease,
                border-color 0.2s ease,
                transform 0.2s ease;
        }

        .btn-logout:hover {
            background: #fee2e2;
            border-color: #fecaca;
            transform: translateY(-1px);
        }


        /* ========================================
           BURGER BUTTON
        ======================================== */
        .nav-burger {
            display: none;
            flex-direction: column;
            justify-content: space-between;

            width: 25px;
            height: 19px;

            background: transparent;
            border: none;
            cursor: pointer;
            padding: 0;
            z-index: 1001;
        }

        .nav-burger span {
            display: block;
            width: 100%;
            height: 2.5px;
            background: var(--text-main);
            border-radius: 3px;
            transition: all 0.3s ease;
        }


        /* ========================================
           FOOTER
        ======================================== */
        .site-footer {
            margin-top: 56px;
            background: linear-gradient(180deg, #f8faff 0%, #eef2ff 100%);
            border-top: 1px solid #e2e6fb;
            color: #5b6884;
            font-family: var(--font-sans);
            font-size: 13px;
        }

        .footer-inner {
            max-width: 1180px;
            margin: 0 auto;
            padding: 42px 32px 30px;
            display: grid;
            grid-template-columns: 1.6fr 1fr 1.25fr;
            gap: 36px;
            align-items: start;
        }

        .footer-logo {
            display: inline-flex;
            align-items: center;
            gap: 11px;
            text-decoration: none;
        }

        .footer-logo-img {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            object-fit: cover;
            background: #fff;
            border: 1px solid #e2e7f8;
            flex-shrink: 0;
        }

        .footer-brand-name {
            display: block;
            font-family: var(--font-display);
            font-size: 17px;
            font-weight: 800;
            letter-spacing: -.02em;
            color: #0f172a;
            line-height: 1.2;
        }

        .footer-brand-tag {
            display: block;
            font-size: 11px;
            color: #7b88a8;
            margin-top: 2px;
        }

        .footer-desc {
            margin-top: 15px;
            max-width: 330px;
            font-size: 12.5px;
            line-height: 1.75;
            color: #64748b;
        }

        .footer-badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            margin-top: 15px;
            padding: 7px 12px;
            border-radius: 999px;
            background: #fff;
            border: 1px solid #e2e6fb;
            color: #364fc7;
            font-size: 11px;
            font-weight: 700;
        }

        .footer-badge::before {
            content: "";
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #22c55e;
            box-shadow: 0 0 0 3px rgba(34, 197, 94, .16);
            flex-shrink: 0;
        }

        .footer-heading {
            font-family: var(--font-display);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .09em;
            text-transform: uppercase;
            color: #1b2848;
            margin-bottom: 14px;
        }

        .footer-links {
            display: flex;
            flex-direction: column;
            gap: 9px;
            list-style: none;
        }

        .footer-links a {
            color: #5b6884;
            font-size: 12.5px;
            text-decoration: none;
            transition: color .18s ease, transform .18s ease;
        }

        .footer-links a:hover {
            color: var(--primary);
        }

        .footer-contact {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 10px 12px;
            margin-bottom: 9px;
            border-radius: 11px;
            background: #fff;
            border: 1px solid #e4e8f5;
            color: #334155;
            font-size: 12px;
            font-weight: 650;
            text-decoration: none;
            transition: border-color .18s ease, transform .18s ease, box-shadow .18s ease;
        }

        .footer-contact:hover {
            border-color: #c7d2fe;
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(37, 99, 235, .10);
        }

        .footer-contact svg {
            width: 16px;
            height: 16px;
            flex-shrink: 0;
        }

        .footer-contact-label {
            margin-left: auto;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .02em;
            color: #2563eb;
            white-space: nowrap;
        }

        .footer-contact-primary {
            background: var(--primary);
            border-color: var(--primary);
            color: #fff;
        }

        .footer-contact-primary .footer-contact-label {
            color: rgba(255, 255, 255, .82);
        }

        .footer-contact-primary:hover {
            background: var(--primary-hover);
            border-color: var(--primary-hover);
        }

        .footer-note {
            margin-top: 4px;
            font-size: 11px;
            line-height: 1.65;
            color: #8490aa;
        }

        .footer-bottom {
            max-width: 1180px;
            margin: 0 auto;
            padding: 16px 32px 24px;
            border-top: 1px solid #e4e8f5;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            flex-wrap: wrap;
            font-size: 11px;
            color: #8490aa;
        }


        /* ========================================
           MOBILE RESPONSIVE
        ======================================== */
        @media (max-width: 768px) {

            body {
                padding-top: 66px;
            }

            .nav-container {
                height: 66px;
                padding: 0 22px;
            }

            .nav-logo {
                gap: 10px;
            }

            .logo-image {
                width: 40px;
                height: 40px;
                border-radius: 11px;
            }

            .logo-name {
                font-size: 17px;
                text-transform: none;
            }

            .nav-burger {
                display: flex;
            }


            /* Mobile Dropdown Menu */
            .nav-menu {
                position: fixed;
                top: 66px;
                left: 0;
                width: 100%;

                max-height: calc(100vh - 66px);
                overflow-y: auto;

                background: var(--surface);
                flex-direction: column;
                align-items: stretch;

                padding: 12px 22px 20px;
                gap: 4px;

                border-bottom: 1px solid var(--border);
                box-shadow: 0 12px 25px rgba(15, 23, 42, 0.08);

                transform: translateY(-8px);
                opacity: 0;
                visibility: hidden;
                pointer-events: none;

                transition: opacity 0.2s ease,
                    transform 0.2s ease,
                    visibility 0.2s;
            }

            .nav-menu.active {
                transform: translateY(0);
                opacity: 1;
                visibility: visible;
                pointer-events: all;
            }

            .nav-menu li {
                width: 100%;
                display: block;
                border-bottom: 1px solid #eef1fa;
            }

            .nav-menu li:last-child {
                border-bottom: none;
                padding-top: 10px;
            }

            .nav-link {
                display: flex;
                width: 100%;
                justify-content: flex-start;

                padding: 14px 12px;
                font-size: 15px;
                border-radius: 8px;
            }

            .nav-link:hover {
                background: var(--primary-soft);
            }

            .nav-menu li form {
                width: 100%;
            }

            .btn-logout {
                width: 100%;
                margin-left: 0;
                padding: 13px 16px;
                font-size: 15px;
                text-align: center;
            }


            /* Burger Animation */
            .nav-burger.active span:nth-child(1) {
                transform: translateY(8px) rotate(45deg);
            }

            .nav-burger.active span:nth-child(2) {
                opacity: 0;
            }

            .nav-burger.active span:nth-child(3) {
                transform: translateY(-8px) rotate(-45deg);
            }
        }


        /* ========================================
           SMALL MOBILE
        ======================================== */
        @media (max-width: 380px) {

            .nav-container {
                padding: 0 15px;
            }

            .logo-name {
                font-size: 16px;
            }

            .logo-image {
                width: 38px;
                height: 38px;
            }

            .nav-burger {
                width: 23px;
                height: 18px;
            }

            .nav-menu {
                padding-left: 15px;
                padding-right: 15px;
            }
        }


        /* ========================================
           REDUCED MOTION
        ======================================== */
        @media (prefers-reduced-motion: reduce) {

            *,
            *::before,
            *::after {
                scroll-behavior: auto !important;
                transition-duration: 0.01ms !important;
                animation-duration: 0.01ms !important;
            }
        }
    </style>
</head>

<body>

    {{-- NAVBAR --}}
    <nav class="navbar">
        <div class="nav-container">

            <a href="{{ route('home') }}" class="nav-logo">
                <img src="{{ asset('images/logo.fixpoint.jpeg') }}" alt="Logo FixPoint Service" class="logo-image">
                <span class="logo-text">
                    <span class="logo-name">FixPoint <em>Service</em></span>
                    <span class="logo-tagline">Servis Laptop &amp; PC</span>
                </span>
            </a>


            {{-- Burger Button --}}
            <button class="nav-burger" id="navBurger" type="button" aria-label="Toggle menu"
                aria-controls="navMenu" aria-expanded="false">
                <span></span>
                <span></span>
                <span></span>
            </button>


            {{-- Menu Links --}}
            <ul class="nav-menu" id="navMenu">

                @auth
                    {{-- MENU ADMIN --}}
                    @if (auth()->user()->role === 'ADMIN')
                        <li>
                            <a href="{{ route('admin.dashboard') }}"
                                class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                                Dashboard Admin
                            </a>
                        </li>

                        {{-- MENU PEMILIK USAHA --}}
                    @elseif (auth()->user()->role === 'PEMILIK_USAHA')
                        <li>
                            <a href="{{ route('pemilik.dashboard') }}"
                                class="nav-link {{ request()->routeIs('pemilik.dashboard') ? 'active' : '' }}">
                                Dashboard Pemilik
                            </a>
                        </li>
                    @endif

                    {{-- LOGOUT --}}
                    <li>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn-logout">
                                Logout
                            </button>
                        </form>
                    </li>

                @else
                    {{-- MENU PUBLIK UNTUK PELANGGAN --}}
                    <li>
                        <a href="{{ route('home') }}"
                            class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}">Beranda</a>
                    </li>
                    <li>
                        <a href="{{ route('ketersediaan-jadwal') }}"
                            class="nav-link {{ request()->routeIs('ketersediaan-jadwal') ? 'active' : '' }}">Jadwal</a>
                    </li>
                    <li>
                        <a href="{{ route('booking') }}"
                            class="nav-link {{ request()->routeIs('booking') ? 'active' : '' }}">Booking</a>
                    </li>
                    <li>
                        <a href="{{ route('cek-status') }}"
                            class="nav-link {{ request()->routeIs('cek-status') ? 'active' : '' }}">Cek Status</a>
                    </li>
                @endauth

            </ul>
        </div>
    </nav>


    {{-- MAIN CONTENT --}}
    <main>
        @yield('content')
    </main>


<style id="fixpoint-ui-refresh">
    :root {
        --primary: #2459df;
        --primary-hover: #1748c5;
        --primary-soft: #e9edff;
        --text-main: #17213b;
        --text-secondary: #68738f;
        --border: #e4e8fb;
        --surface: #ffffff;
        --background: #f6f7ff;
        --font-display: "Space Grotesk", "IBM Plex Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        --font-sans: "IBM Plex Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        --font-mono: "JetBrains Mono", ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        --shadow-soft: 0 12px 34px rgba(48, 68, 139, .075);
        --shadow-card: 0 7px 22px rgba(44, 62, 125, .055);
    }

    html { scroll-behavior: smooth; }

    body {
        background: #f6f7ff;
        color: var(--text-main);
        line-height: 1.65;
    }

    main {
        min-height: calc(100vh - 150px);
    }

    .navbar {
        background: rgba(255, 255, 255, .94);
        border-bottom: 1px solid #e7eafe;
        box-shadow: 0 5px 22px rgba(45, 64, 130, .055);
    }

    .nav-container {
        max-width: 1360px;
        padding-left: 28px;
        padding-right: 28px;
    }

    .logo-image {
        border: 1px solid #e8ecff;
        box-shadow: 0 4px 12px rgba(37, 83, 210, .13);
    }

    .nav-link {
        border-radius: 10px;
        color: #53617e;
        font-weight: 650;
    }

    .nav-link.active {
        background: #2459df;
        box-shadow: 0 5px 14px rgba(36, 89, 223, .20);
    }

    .btn-logout {
        border-radius: 10px;
        font-weight: 700;
    }

    .site-footer {
        margin-top: 56px;
        letter-spacing: .01em;
    }

    /* PUBLIC HOMEPAGE */
    .hero {
        max-width: 1320px;
        margin: 28px auto 0;
        padding: 62px 38px;
        border: 1px solid #e8ebff;
        border-radius: 26px;
        background:
            radial-gradient(circle at 88% 18%, rgba(190, 202, 255, .42), transparent 28%),
            linear-gradient(135deg, #f0f2ff 0%, #f8f9ff 58%, #e9efff 100%);
        box-shadow: 0 14px 38px rgba(57, 74, 148, .055);
    }

    .hero-grid { gap: clamp(26px, 4vw, 58px); }
    .hero-label, .section .section-kicker {
        color: #2459df;
        font-family: var(--font-mono);
        font-size: 10px;
        font-weight: 700;
        letter-spacing: .10em;
    }

    .hero-title {
        max-width: 680px;
        color: #17213b;
        font-size: clamp(34px, 4vw, 52px);
        letter-spacing: -.055em;
        line-height: 1.08;
    }

    .hero-text {
        max-width: 620px;
        color: #66718d;
        font-size: 15px;
        line-height: 1.8;
    }

    .hero-actions { gap: 11px; }
    .btn-primary, .btn-secondary, .btn-book, .booking-button, .login-button {
        border-radius: 11px;
        font-weight: 750;
        transition: transform .18s ease, box-shadow .18s ease, background-color .18s ease;
    }

    .hero-actions .btn-primary, .hero-actions .btn-secondary {
        padding: 13px 19px;
    }

    .btn-primary, .btn-book, .booking-button, .login-button {
        background: linear-gradient(135deg, #2863ed, #2454d8);
        box-shadow: 0 7px 16px rgba(37, 91, 222, .17);
    }

    .btn-primary:hover, .btn-book:hover, .booking-button:hover, .login-button:hover {
        background: linear-gradient(135deg, #2058dc, #1948c4);
        box-shadow: 0 9px 19px rgba(37, 91, 222, .23);
        transform: translateY(-1px);
    }

    .btn-secondary {
        border: 1px solid #dce3ff;
        background: rgba(255,255,255,.84);
        color: #2459df;
    }

    .btn-secondary:hover {
        background: #fff;
        border-color: #b9c8ff;
        transform: translateY(-1px);
    }

    .hero-card, .service-card, .schedule-card, .schedule-item {
        border: 1px solid #e5e9fb;
        border-radius: 17px;
        box-shadow: var(--shadow-card);
    }

    .hero-card {
        padding: 25px;
        background: rgba(255,255,255,.94);
    }

    .hero-card-title, .section-title { color: #17213b; letter-spacing: -.035em; }
    .schedule-item {
        background: #f8f9ff;
        padding: 15px 16px;
    }

    .schedule-status, .schedule-slots { color: #16835d; }

    .section {
        max-width: 1320px;
        padding: 54px 24px;
    }

    .section-title { font-size: clamp(24px, 3vw, 31px); }
    .section-text { color: #73809c; }
    .service-grid { gap: 17px; }
    .service-card {
        position: relative;
        padding: 23px;
        background: #fff;
        transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
    }

    .service-card:hover {
        transform: translateY(-3px);
        border-color: #cbd5ff;
        box-shadow: 0 14px 28px rgba(51, 75, 151, .10);
    }

    .service-card h3 { color: #1c2948; }
    .service-card p { color: #71809c; line-height: 1.7; }

    /* SCHEDULE PAGE */
    .schedule-page {
        max-width: 1260px;
        padding: 46px 24px 58px;
    }

    .page-label {
        font-family: var(--font-mono);
        font-size: 10px;
        letter-spacing: .10em;
        color: #2459df;
    }

    .page-title {
        color: #17213b;
        font-weight: 800;
        letter-spacing: -.045em;
        font-size: clamp(27px, 3vw, 36px);
    }

    .page-desc { color: #6b7894; }
    .schedule-card {
        padding: 25px;
        background: rgba(255,255,255,.95);
    }

    .schedule-card .card-title { color: #1b2848; font-weight: 800; }
    .schedule-grid { gap: 15px; }
    .schedule-page .schedule-item {
        padding: 19px;
        border: 1px solid #e0e6ff;
        background: linear-gradient(145deg, #f8f9ff, #eef2ff);
        border-radius: 15px;
    }

    .schedule-page .schedule-item:hover {
        border-color: #aebfff;
        box-shadow: 0 12px 24px rgba(48, 79, 180, .10);
    }

    .schedule-day {
        color: #71809d;
        font-family: var(--font-mono);
        font-size: 10px;
        letter-spacing: .07em;
        text-transform: uppercase;
    }

    .schedule-page .schedule-date { color: #1a2848; font-size: 17px; }
    .schedule-time { color: #687592; }
    .schedule-slots { font-size: 12px; }

    /* BOOKING PAGE */
    .booking-page {
        max-width: 1320px;
        padding: 42px 24px 60px;
    }

    .booking-label {
        color: #2459df;
        font-family: var(--font-mono);
        font-size: 10px;
        letter-spacing: .10em;
        text-transform: uppercase;
    }

    .booking-title {
        color: #17213b;
        font-size: clamp(28px, 3vw, 36px);
        font-weight: 800;
        letter-spacing: -.045em;
    }

    .booking-description { color: #6b7894; line-height: 1.75; }
    .booking-layout { gap: 19px; }

    .booking-card, .summary-card {
        border: 1px solid #e5e9fb;
        border-radius: 17px;
        background: #fff;
        box-shadow: var(--shadow-card);
    }

    .booking-card { padding: 24px; }
    .booking-card-title { color: #1b2848; font-size: 17px; font-weight: 800; }
    .form-label { color: #52617f; font-weight: 750; }

    .form-control, .admin-input, .admin-select, .admin-textarea,
    .date-filter input, .status-input, .status-textarea, .status-file {
        border: 1px solid #dce2f5;
        border-radius: 10px;
        background: #fbfcff;
        color: #1c2948;
        transition: border-color .18s ease, box-shadow .18s ease, background .18s ease;
    }

    .form-control:focus, .admin-input:focus, .admin-select:focus, .admin-textarea:focus,
    .date-filter input:focus, .status-input:focus, .status-textarea:focus, .status-file:focus {
        border-color: #8ba7ff;
        background: #fff;
        box-shadow: 0 0 0 4px rgba(66, 111, 236, .10);
        outline: none;
    }

    .service-option {
        border: 1px solid #e1e6f8;
        border-radius: 13px;
        background: #fbfcff;
    }

    .service-option:hover {
        border-color: #aebfff;
        background: #f1f4ff;
    }

    .service-option-title { color: #1c2948; }
    .service-option-text { color: #6e7a96; line-height: 1.65; }
    .summary-card { padding: 21px; }
    .summary-label { color: #74809a; }
    .summary-value { color: #24314f; }
    .summary-total { border-color: #e7eafe; }
    .summary-total strong { color: #2459df; }
    .booking-button { padding: 13px 16px; }
    .back-link { color: #2459df; }

    /* LOGIN */
    .login-page {
        min-height: 74vh;
        padding: 36px 20px;
        background: radial-gradient(circle at 50% 18%, rgba(213, 221, 255, .55), transparent 34%);
    }

    .login-card {
        max-width: 450px;
        padding: 36px;
        border: 1px solid #e5e9fb;
        border-radius: 22px;
        background: rgba(255,255,255,.96);
        box-shadow: 0 18px 48px rgba(43, 62, 135, .10);
    }

    .login-label {
        font-family: var(--font-mono);
        font-size: 10px;
        letter-spacing: .12em;
        color: #2459df;
    }

    .login-title { color: #17213b; font-weight: 800; letter-spacing: -.04em; }
    .login-description, .login-note { color: #73809a; }
    .role-option label { border-color: #e0e5f6; border-radius: 11px; }
    .role-option input:checked + label { border-color: #8aa4ff; background: #eef2ff; color: #2459df; }
    .login-button { padding: 14px; }

    /* STATUS / PAYMENT PAGE */
    .status-page {
        max-width: 1140px;
        padding: 42px 24px 60px;
    }

    .status-eyebrow {
        color: #2459df;
        font-family: var(--font-mono);
        font-size: 10px;
        letter-spacing: .10em;
    }

    .status-title { color: #17213b; letter-spacing: -.045em; }
    .status-description { color: #6e7a96; line-height: 1.75; }
    .status-card, .payment-panel, .cancel-panel {
        border: 1px solid #e5e9fb;
        border-radius: 17px;
        background: #fff;
        box-shadow: var(--shadow-card);
    }

    .status-card { padding: 24px; }
    .status-page .card-title, .payment-panel-title { color: #1b2848; font-weight: 800; }
    .status-page .card-description, .payment-panel-description { color: #71809c; }
    .service-id-value, .receipt-id { font-family: var(--font-mono); }
    .info-item, .detail-item {
        border-color: #e7ebfa;
        border-radius: 12px;
        background: #fbfcff;
    }

    .info-value, .detail-value { color: #24314f; }
    .progress-track { background: #edf0ff; }
    .progress-step.active .progress-dot { box-shadow: 0 0 0 5px rgba(59, 101, 226, .12); }
    .payment-panel { padding: 22px; }
    .bill-total { border-color: #e5e9fb; background: #f5f7ff; border-radius: 13px; }
    .bill-amount { color: #2459df; }
    .qris-wrap {
        border: 1px dashed #b8c6fb;
        border-radius: 15px;
        background: #f8f9ff;
    }

    .payment-note { border-radius: 11px; }
    .proof-preview { border: 1px solid #e5e9fb; border-radius: 13px; }
    .receipt-card { border-color: #e2e7f8; border-radius: 16px; }
    .receipt-brand { color: #2459df; font-family: var(--font-mono); }

    /* ADMIN LAYOUT + ADMIN PAGES */
    .admin-shell {
        max-width: 1560px;
        padding: 30px 26px 54px;
        grid-template-columns: 252px minmax(0, 1fr);
        gap: 21px;
        color: #17213b;
    }

    .admin-sidebar {
        top: 94px;
        padding: 18px;
        border: 1px solid #e5e9fb;
        border-radius: 19px;
        background: rgba(255,255,255,.96);
        box-shadow: var(--shadow-card);
    }

    .admin-brand { border-bottom-color: #edf0fc; }
    .admin-brand-mark {
        border-radius: 13px;
        background: linear-gradient(145deg, #eaf0ff, #dfe7ff);
        color: #2459df;
        box-shadow: inset 0 0 0 1px #dbe4ff;
    }

    .admin-brand-name { color: #1b2848; }
    .admin-brand-caption { color: #8490aa; }
    .admin-nav-label {
        color: #98a2ba;
        font-family: var(--font-mono);
        font-size: 9px;
        letter-spacing: .11em;
    }

    .admin-nav-link {
        border: 1px solid transparent;
        border-radius: 11px;
        padding: 10px 11px;
        color: #5b6885;
        font-weight: 650;
    }

    .admin-nav-link:hover {
        background: #f0f3ff;
        color: #2459df;
        border-color: #e7ecff;
    }

    .admin-nav-link.active {
        background: linear-gradient(135deg, #2d65ec, #2454d8);
        color: #fff;
        box-shadow: 0 6px 14px rgba(37, 88, 220, .18);
    }

    .admin-main { min-width: 0; }
    .admin-page-header { margin-bottom: 23px; }
    .admin-eyebrow {
        color: #2459df;
        font-family: var(--font-mono);
        font-size: 10px;
        letter-spacing: .11em;
    }

    .admin-page-title {
        color: #17213b;
        letter-spacing: -.045em;
        font-size: clamp(25px, 2.5vw, 34px);
    }

    .admin-page-subtitle { color: #71809c; line-height: 1.7; }
    .admin-card, .admin-stat {
        border: 1px solid #e5e9fb;
        border-radius: 17px;
        background: #fff;
        box-shadow: var(--shadow-card);
    }

    .admin-card { padding: 21px; margin-bottom: 19px; }
    .admin-card-title { color: #1b2848; font-weight: 800; }
    .admin-card-description { color: #75819c; }
    .admin-stat { padding: 17px; transition: transform .18s ease, box-shadow .18s ease; }
    .admin-stat:hover { transform: translateY(-2px); box-shadow: 0 12px 23px rgba(44, 62, 125, .085); }
    .admin-stat-top { color: #71809c; }
    .admin-stat-icon { background: #edf1ff; color: #2459df; border-radius: 10px; }
    .admin-stat-value { color: #17213b; }
    .admin-stat-note { color: #8792aa; }

    .admin-table-wrap {
        border: 1px solid #e7ebf9;
        border-radius: 13px;
        background: #fff;
    }

    .admin-table th {
        background: #f1f3ff;
        color: #7783a0;
        border-bottom-color: #e4e8fa;
        font-family: var(--font-mono);
        font-size: 9px;
        letter-spacing: .075em;
    }

    .admin-table td { color: #4c5976; border-bottom-color: #edf0fa; }
    .admin-table tbody tr:hover { background: #f8f9ff; }
    .admin-id { color: #2459df; font-family: var(--font-mono); font-size: 11px; }
    .admin-primary-text { color: #24314f; }

    .admin-badge { border-radius: 8px; padding: 6px 9px; }
    .badge-blue { background: #e7edff; color: #2459df; }
    .badge-green { background: #e4f7ef; color: #16845d; }
    .badge-yellow { background: #fff4dc; color: #9a6814; }
    .badge-red { background: #ffe9eb; color: #c23f51; }
    .badge-gray { background: #eff1f8; color: #64708b; }

    .admin-btn {
        border-radius: 10px;
        padding: 10px 14px;
        font-weight: 750;
    }

    .admin-btn-primary {
        background: linear-gradient(135deg, #2d65ec, #2454d8);
        box-shadow: 0 5px 13px rgba(37, 88, 220, .15);
    }

    .admin-btn-primary:hover { background: #1d4fcf; }
    .admin-btn-soft { background: #edf1ff; color: #2459df; }
    .admin-btn-soft:hover { background: #e2e9ff; }
    .admin-btn-success { background: #16845d; }
    .admin-btn-danger { background: #fff0f1; color: #bd3d4e; }
    .admin-btn-outline { border-color: #dfe5f5; color: #596783; }

    .admin-label { color: #53617e; }
    .admin-input, .admin-select, .admin-textarea { padding: 11px 12px; }
    .admin-help, .admin-muted { color: #8994ad; }
    .admin-alert { border-radius: 12px; padding: 13px 15px; }
    .admin-alert-success { background: #eaf8f1; border-color: #c9eedb; color: #16734f; }
    .admin-alert-error { background: #fff0f1; border-color: #ffd8dd; color: #b93f4d; }
    .admin-empty { color: #8994ad; }
    .admin-empty strong { color: #52617f; }
    .admin-callout { border: 1px solid #e1e8ff; border-radius: 13px; background: #f0f3ff; color: #3656a8; }
    .admin-detail-item { border-color: #e7ebfa; border-radius: 11px; background: #f8f9ff; }
    .admin-detail-label { color: #8792aa; font-family: var(--font-mono); }
    .admin-detail-value { color: #24314f; }
    .admin-filter-link { border-color: #e0e6f7; border-radius: 9px; color: #71809a; }
    .admin-filter-link.active { background: #edf1ff; border-color: #cbd7ff; color: #2459df; }
    .admin-sidebar-toggle { border-color: #e0e6f7; border-radius: 10px; background: #f7f8ff; }

    /* OWNER DASHBOARD */
    .owner-dashboard {
        max-width: 1500px;
        padding: 34px 26px 56px;
    }

    .owner-header { margin-bottom: 25px; }
    .owner-label {
        color: #2459df;
        font-family: var(--font-mono);
        font-size: 10px;
        letter-spacing: .11em;
    }

    .owner-title {
        color: #17213b;
        font-size: clamp(27px, 3vw, 36px);
        letter-spacing: -.045em;
        font-weight: 800;
    }

    .owner-subtitle { color: #6e7a96; line-height: 1.7; }
    .owner-dashboard .stats-grid { gap: 15px; margin-bottom: 21px; }
    .owner-dashboard .stat-card, .owner-dashboard .card {
        border: 1px solid #e5e9fb;
        border-radius: 17px;
        background: #fff;
        box-shadow: var(--shadow-card);
    }

    .owner-dashboard .stat-card { padding: 19px; }
    .owner-dashboard .stat-label { color: #73809b; font-weight: 650; }
    .owner-dashboard .stat-value { color: #1d4fcf; font-weight: 850; letter-spacing: -.04em; }
    .owner-dashboard .stat-note { color: #8a95ac; }
    .owner-dashboard .content-grid { gap: 19px; }
    .owner-dashboard .card { padding: 21px; margin-bottom: 19px; }
    .owner-dashboard .card-title { color: #1b2848; font-weight: 800; }
    .owner-dashboard .card-description { color: #7a86a0; }
    .owner-dashboard .service-table-wrap { overflow-x: auto; border: 1px solid #e7ebf9; border-radius: 13px; }
    .owner-dashboard .service-table { min-width: 650px; }
    .owner-dashboard .service-table th {
        background: #f1f3ff;
        color: #7884a0;
        font-family: var(--font-mono);
        font-size: 9px;
        letter-spacing: .07em;
        border-bottom-color: #e4e8fa;
    }

    .owner-dashboard .service-table td { color: #4c5976; border-bottom-color: #edf0fa; }
    .owner-dashboard .service-id { color: #2459df; font-family: var(--font-mono); }
    .owner-dashboard .status { border-radius: 8px; padding: 6px 9px; }
    .owner-dashboard .status-process { background: #e7edff; color: #2459df; }
    .owner-dashboard .status-waiting { background: #fff4dc; color: #966719; }
    .owner-dashboard .status-done, .owner-dashboard .status-paid { background: #e4f7ef; color: #16845d; }
    .owner-dashboard .status-unpaid { background: #ffe9eb; color: #bf4352; }
    .owner-dashboard .info-item { border-color: #e6eafa; border-radius: 12px; background: #fafbff; }
    .owner-dashboard .info-label { color: #8490aa; }
    .owner-dashboard .info-value { color: #263451; }
    .owner-dashboard .activity-item { border-bottom-color: #edf0fa; }
    .owner-dashboard .activity-id { color: #2459df; font-family: var(--font-mono); }
    .owner-dashboard .activity-text { color: #65718d; }
    .owner-dashboard .empty-state { background: #f7f8ff; border-radius: 11px; color: #8792aa; }

    /* RESPONSIVE POLISH */
    @media (max-width: 1100px) {
        .admin-shell { grid-template-columns: 220px minmax(0, 1fr); padding-left: 18px; padding-right: 18px; }
        .hero { margin-left: 20px; margin-right: 20px; }
        .owner-dashboard { padding-left: 20px; padding-right: 20px; }
    }

    @media (max-width: 800px) {
        .hero { margin: 18px 14px 0; padding: 38px 23px; border-radius: 20px; }
        .hero-title { font-size: clamp(31px, 7vw, 42px); }
        .section { padding: 38px 18px; }
        .schedule-page, .booking-page, .status-page { padding: 29px 16px 44px; }
        .booking-layout { grid-template-columns: 1fr; }
        .summary-card { position: static; }
        .admin-shell { grid-template-columns: 1fr; padding: 20px 15px 40px; }
        .admin-sidebar { position: static; }
        .admin-main { width: 100%; }
        .owner-dashboard { padding: 26px 16px 44px; }
        .footer-inner { grid-template-columns: 1fr; gap: 28px; padding: 32px 20px 24px; }
        .footer-desc { max-width: none; }
        .footer-bottom { flex-direction: column; align-items: flex-start; padding: 15px 20px 26px; }
    }

    @media (max-width: 600px) {
        .nav-container { padding-left: 17px; padding-right: 17px; }
        .hero { padding: 31px 18px; }
        .hero-actions { flex-direction: column; }
        .hero-actions a { width: 100%; }
        .hero-card, .booking-card, .status-card, .schedule-card, .admin-card { padding: 17px; }
        .service-grid { grid-template-columns: 1fr; }
        .form-grid, .service-options, .info-grid, .detail-grid { grid-template-columns: 1fr; }
        .status-search-form { flex-direction: column; }
        .status-search-form .btn-primary { width: 100%; }
        .service-heading { flex-direction: column; }
        .admin-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .admin-nav { grid-template-columns: 1fr; }
        .admin-toolbar, .admin-search { width: 100%; }
        .owner-dashboard .stats-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .owner-dashboard .content-grid { grid-template-columns: 1fr; }
        .owner-dashboard .card { padding: 16px; }
        .login-card { padding: 27px 21px; }
        .footer-logo-img { width: 38px; height: 38px; }
        .footer-contact { min-height: 46px; padding: 12px 13px; font-size: 12.5px; }
        .footer-links { gap: 12px; }
        .footer-links a { display: inline-block; padding: 3px 0; }
        .footer-heading { margin-bottom: 11px; }
        .footer-badge { font-size: 10.5px; }
    }

    @media (max-width: 380px) {
        .admin-stats, .owner-dashboard .stats-grid { grid-template-columns: 1fr; }
        .hero-title { font-size: 29px; }
    }

    @media (prefers-reduced-motion: reduce) {
        html { scroll-behavior: auto; }
        *, *::before, *::after { transition-duration: .01ms !important; }
    }

    /* ========================================
       TIPOGRAFI
       Judul & menu : Space Grotesk
       Tabel/status/isi data : IBM Plex Sans
       Service ID : JetBrains Mono
    ======================================== */
    body,
    .admin-table,
    .admin-input,
    .admin-select,
    .admin-textarea,
    .admin-badge,
    .status,
    .payment-pill,
    .detail-text,
    .info-value,
    .detail-value {
        font-family: var(--font-sans);
    }

    h1, h2, h3, h4, h5, h6,
    .logo-name,
    .nav-link,
    .btn-logout,
    .section-title,
    .card-title,
    .booking-card-title,
    .login-title,
    .payment-panel-title,
    .status-title,
    .admin-page-title,
    .admin-card-title,
    .admin-brand-name,
    .admin-eyebrow,
    .admin-nav-label,
    .admin-nav-link,
    .receipt-brand,
    .receipt-title,
    .owner-label {
        font-family: var(--font-display);
    }

    .admin-id,
    .service-id-value,
    .receipt-id,
    .logo-tagline,
    .fp-qris-admin-id,
    .fp-qris-owner-id {
        font-family: var(--font-mono);
    }

    /* ========================================
       MOBILE POLISH (halaman publik)
    ======================================== */
    @media (max-width: 640px) {
        body { overflow-x: hidden; }

        .nav-container { padding-left: 15px; padding-right: 15px; }
        .hero { margin: 14px 12px 0; padding: 28px 17px; border-radius: 18px; }
        .hero-title { font-size: clamp(26px, 8.4vw, 31px); line-height: 1.16; }
        .hero-card { padding: 16px; }
        .section { padding: 32px 15px; }
        .schedule-page, .booking-page, .status-page { padding: 22px 14px 34px; }

        input, select, textarea { font-size: 16px; }

        .btn-primary, .btn-secondary, .btn-book, .booking-button, .login-button {
            min-height: 46px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .service-card, .schedule-card { padding: 16px; }
    }

    @media (max-width: 380px) {
        .hero { margin-left: 10px; margin-right: 10px; }
        .footer-inner { padding-left: 16px; padding-right: 16px; }
    }
</style>

    @php
        $adminWhatsapp = preg_replace('/\D+/', '', (string) config('site.admin_whatsapp'));
        $adminEmail = (string) config('site.admin_email');
        $adminHours = (string) config('site.admin_hours');
    @endphp

    <footer class="site-footer">
        <div class="footer-inner">
            <div class="footer-brand">
                <a href="{{ route('home') }}" class="footer-logo">
                    <img src="{{ asset('images/logo.fixpoint.jpeg') }}" alt="Logo FixPoint Service"
                        class="footer-logo-img">
                    <span>
                        <span class="footer-brand-name">FixPoint Service</span>
                        <span class="footer-brand-tag">Servis perangkat yang rapi &amp; transparan</span>
                    </span>
                </a>

                <p class="footer-desc">
                    Pantau booking, jadwal servis, dan status pembayaran perangkat Anda dalam satu tempat.
                </p>

                @if (filled($adminHours))
                    <span class="footer-badge">{{ $adminHours }}</span>
                @endif
            </div>

            <div class="footer-col">
                <h3 class="footer-heading">Navigasi</h3>
                <ul class="footer-links">
                    <li><a href="{{ route('home') }}">Beranda</a></li>
                    <li><a href="{{ route('ketersediaan-jadwal') }}">Jadwal Servis</a></li>
                    <li><a href="{{ route('booking') }}">Booking Servis</a></li>
                    <li><a href="{{ route('cek-status') }}">Cek Status</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h3 class="footer-heading">Kontak Admin</h3>

                @if (filled($adminWhatsapp))
                    <a class="footer-contact footer-contact-primary"
                        href="https://wa.me/{{ $adminWhatsapp }}" target="_blank" rel="noopener">
                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path
                                d="M12.04 2a9.9 9.9 0 0 0-8.42 15.14L2 22l4.98-1.58A9.9 9.9 0 1 0 12.04 2Zm0 1.8a8.1 8.1 0 0 1 0 16.2 8 8 0 0 1-4.1-1.12l-.3-.18-2.94.93.94-2.87-.19-.3a8.1 8.1 0 0 1 6.59-12.66Zm-3.6 4.2c-.2 0-.5.07-.77.37-.27.3-1.02 1-1.02 2.42s1.05 2.8 1.2 3c.14.2 2.04 3.24 5.03 4.4 2.48.96 2.99.77 3.53.72.54-.05 1.74-.71 1.98-1.4.25-.68.25-1.27.17-1.4-.07-.13-.27-.2-.57-.35-.3-.15-1.74-.86-2.01-.96-.27-.1-.47-.15-.67.15-.2.3-.77.96-.94 1.16-.18.2-.35.22-.65.07-.3-.15-1.25-.46-2.38-1.47-.88-.79-1.47-1.76-1.64-2.06-.17-.3-.02-.47.13-.62.13-.13.3-.35.45-.52.15-.18.2-.3.3-.5.1-.2.05-.38-.02-.53-.08-.15-.67-1.62-.92-2.21-.24-.58-.49-.5-.67-.5h-.57Z" />
                        </svg>
                        <span>WhatsApp Admin</span>
                        <span class="footer-contact-label">Chat sekarang</span>
                    </a>
                @endif

                <a class="footer-contact" href="mailto:{{ $adminEmail }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="2.5" y="4.5" width="19" height="15" rx="2.5"></rect>
                        <path d="m3.5 6.5 8.5 6 8.5-6"></path>
                    </svg>
                    <span>{{ $adminEmail }}</span>
                </a>

                <p class="footer-note">
                    Ada kendala booking atau pembayaran? Hubungi admin agar bisa dibantu lebih cepat.
                </p>
            </div>
        </div>

        <div class="footer-bottom">
            <span>&copy; {{ date('Y') }} FixPoint Service. Semua hak dilindungi.</span>
            <span>Dikelola oleh Admin FixPoint Service</span>
        </div>
    </footer>


    {{-- JAVASCRIPT FOR BURGER MENU --}}
    <script>
        const burger = document.getElementById('navBurger');
        const menu = document.getElementById('navMenu');
        const navLinks = document.querySelectorAll('.nav-link');

        // Toggle menu saat burger diklik
        burger.addEventListener('click', () => {
            const isExpanded = burger.getAttribute('aria-expanded') === 'true';
            burger.setAttribute('aria-expanded', String(!isExpanded));
            menu.classList.toggle('active', !isExpanded);
            burger.classList.toggle('active', !isExpanded);
        });

        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                menu.classList.remove('active');
                burger.classList.remove('active');
                burger.setAttribute('aria-expanded', 'false');
            });
        });
    </script>

</body>

</html>