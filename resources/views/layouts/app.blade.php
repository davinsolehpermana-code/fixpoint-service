<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        @yield('title', 'FixPoint Service')
    </title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background-color: #f8fafc;
            color: #1f2937;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        .navbar {
            width: 100%;
            background-color: #ffffff;
            border-bottom: 1px solid #e5e7eb;
        }

        .navbar-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 18px 24px;

            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .brand {
            font-size: 20px;
            font-weight: 700;
            color: #111827;
        }

        .brand span {
            color: #2563eb;
        }

        .nav-menu {
            display: flex;
            align-items: center;
            gap: 24px;
        }

        .nav-link {
            font-size: 14px;
            color: #4b5563;
            transition: 0.2s;
        }

        .nav-link:hover {
            color: #2563eb;
        }

        .nav-button {
            padding: 9px 16px;
            border-radius: 7px;
            background-color: #2563eb;
            color: #ffffff;
            font-size: 14px;
            border: none;
            cursor: pointer;
        }

        .nav-button:hover {
            background-color: #1d4ed8;
        }

        .main-content {
            min-height: calc(100vh - 140px);
            padding: 30px 24px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .footer {
            border-top: 1px solid #e5e7eb;
            background-color: #ffffff;
            padding: 20px 24px;
            text-align: center;
            color: #6b7280;
            font-size: 13px;
        }

        @media (max-width: 768px) {
            .navbar-container {
                padding: 16px;
            }

            .nav-menu {
                gap: 12px;
            }

            .nav-link {
                font-size: 13px;
            }

            .main-content {
                padding: 24px 16px;
            }
        }
    </style>

    @stack('styles')
</head>

<body>

    <header class="navbar">
        <div class="navbar-container">

            <a href="{{ url('/') }}" class="brand">
                FIXPOINT <span>SERVICE</span>
            </a>

            <nav class="nav-menu">

                <a href="{{ url('/') }}" class="nav-link">
                    Beranda
                </a>

                <a href="{{ url('/ketersediaan-jadwal') }}" class="nav-link">
                    Ketersediaan Jadwal
                </a>

                <a href="{{ url('/booking') }}" class="nav-link">
                    Booking
                </a>

                <a href="{{ url('/cek-status') }}" class="nav-link">
                    Cek Status
                </a>

                <a href="{{ route('login') }}" class="nav-button">
                    Login
                </a>

            </nav>

        </div>
    </header>


    <main class="main-content">
        <div class="container">

            @yield('content')

        </div>
    </main>


    <footer class="footer">
        <p>
            &copy; {{ date('Y') }} FixPoint Service. All rights reserved.
        </p>
    </footer>


    @stack('scripts')

</body>
</html>