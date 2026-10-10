<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>FixPoint Service - @yield('title', 'Beranda')</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f7f9fc;
            color: #1f2937;
            line-height: 1.6;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        .container {
            width: min(1180px, 92%);
            margin: 0 auto;
        }

        /* NAVBAR */
        .navbar {
            background: #ffffff;
            border-bottom: 1px solid #e5e7eb;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .navbar-inner {
            min-height: 72px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
        }

        .brand {
            font-size: 20px;
            font-weight: 800;
            color: #2563eb;
            white-space: nowrap;
        }

        .brand span {
            color: #111827;
        }

        .nav-menu {
            display: flex;
            align-items: center;
            gap: 24px;
            list-style: none;
        }

        .nav-menu a {
            color: #4b5563;
            font-size: 14px;
            font-weight: 600;
            transition: 0.2s;
        }

        .nav-menu a:hover {
            color: #2563eb;
        }

        .nav-button {
            background: #2563eb;
            color: #ffffff !important;
            padding: 10px 16px;
            border-radius: 8px;
        }

        .nav-button:hover {
            background: #1d4ed8;
        }

        /* MAIN */
        main {
            min-height: calc(100vh - 140px);
        }

        .page-content {
            padding: 32px 0;
        }

        /* FOOTER */
        .footer {
            background: #ffffff;
            border-top: 1px solid #e5e7eb;
            padding: 22px 0;
            margin-top: 40px;
        }

        .footer-inner {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
        }

        .footer-text {
            color: #6b7280;
            font-size: 13px;
        }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .navbar-inner {
                flex-direction: column;
                align-items: flex-start;
                padding: 16px 0;
            }

            .nav-menu {
                width: 100%;
                flex-wrap: wrap;
                gap: 14px;
            }

            .footer-inner {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>

    @stack('styles')
</head>

<body>

    <nav class="navbar">
        <div class="container navbar-inner">

            <a href="/" class="brand">
                FixPoint <span>Service</span>
            </a>

            <ul class="nav-menu">
                <li>
                    <a href="/">Beranda</a>
                </li>

                <li>
                    <a href="/ketersediaan-jadwal">
                        Ketersediaan Jadwal
                    </a>
                </li>

                <li>
                    <a href="/booking">
                        Booking Service
                    </a>
                </li>

                <li>
                    <a href="/cek-status">
                        Cek Status
                    </a>
                </li>

                <li>
                    <a href="/login" class="nav-button">
                        Masuk
                    </a>
                </li>
            </ul>

        </div>
    </nav>

    <main>
        <div class="container page-content">
            @yield('content')
        </div>
    </main>

    <footer class="footer">
        <div class="container footer-inner">
            <div class="footer-text">
                © 2026 FixPoint Service
            </div>

            <div class="footer-text">
                Booking & Manajemen Jasa Servis Laptop dan PC
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>