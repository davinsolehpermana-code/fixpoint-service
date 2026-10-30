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
        href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">

    <style>
        /* ========================================
           RESET & BASE
        ======================================== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Plus Jakarta Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
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
            margin-top: 40px;
            padding: 18px 20px;
            text-align: center;
            background-color: var(--primary-soft);
            border-top: 1px solid var(--border);
            color: var(--text-secondary);
            font-family: var(--font-mono);
            font-size: 12px;
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


    {{-- FOOTER (Opsional) --}}
    <footer class="site-footer">
        &copy; {{ date('Y') }} FixPoint Service. All rights reserved.
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