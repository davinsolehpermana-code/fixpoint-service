<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'FixPoint Service')</title>
    
    <style>
        /* --- RESET & BASE --- */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; }
        body { background-color: #f8fafc; color: #111827; line-height: 1.6; padding-top: 70px; /* Space for fixed navbar */ }
        a { text-decoration: none; color: inherit; }
        ul { list-style: none; }

        /* --- NAVBAR STYLES --- */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            background: #ffffff;
            border-bottom: 1px solid #e5e7eb;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px;
            height: 70px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 24px;
        }

        
.nav-logo {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
}

.logo-image {
    height: 48px;
    width: 48px;
    object-fit: cover;
    object-position: center;
    border-radius: 50%;
    flex-shrink: 0;
}


        /* Desktop Menu */
        .nav-menu {
            display: flex;
            align-items: center;
            gap: clamp(16px, 2vw, 24px);
            min-width: 0;
        }

        .nav-link {
            font-size: 14px;
            font-weight: 600;
            color: #4b5563;
            transition: color 0.2s;
        }

        .nav-link:hover { color: #2563eb; }

        .btn-logout {
            background: #fee2e2;
            color: #991b1b !important;
            padding: 8px 16px;
            border-radius: 8px;
            border: none;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-logout:hover { background: #fecaca; }

        /* Burger Button (Hidden on Desktop) */
        .nav-burger {
            display: none;
            flex-direction: column;
            justify-content: space-between;
            width: 24px;
            height: 18px;
            background: transparent;
            border: none;
            cursor: pointer;
            padding: 0;
            z-index: 1001;
        }

        .nav-burger span {
            display: block;
            width: 100%;
            height: 2px;
            background: #111827;
            border-radius: 2px;
            transition: all 0.3s ease;
        }

        /* --- MOBILE RESPONSIVE --- */
        @media (max-width: 768px) {
            .nav-container {
                height: 64px;
                padding: 0 18px;
            }

            body {
                padding-top: 64px;
            }

            .nav-burger { display: flex; }

            .nav-menu {
                position: fixed;
                top: 64px;
                left: 0;
                width: 100%;
                max-height: calc(100vh - 64px);
                overflow-y: auto;
                background: #ffffff;
                flex-direction: column;
                align-items: flex-start;
                padding: 20px;
                gap: 0;
                border-bottom: 1px solid #e5e7eb;
                box-shadow: 0 10px 15px rgba(0,0,0,0.1);
                transform: translateY(-8px);
                opacity: 0;
                visibility: hidden;
                transition: opacity 0.2s ease, transform 0.2s ease, visibility 0.2s;
                pointer-events: none;
            }

            .nav-menu.active {
                transform: translateY(0);
                opacity: 1;
                visibility: visible;
                pointer-events: all;
            }

            .nav-menu li {
                width: 100%;
                border-bottom: 1px solid #f3f4f6;
            }

            .nav-menu li:last-child { border-bottom: none; }

            .nav-link, .btn-logout {
                display: block;
                width: 100%;
                padding: 16px 0;
                font-size: 16px;
                text-align: left;
            }

            .btn-logout {
                text-align: center;
                margin-top: 10px;
            }

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

        @media (max-width: 380px) {
            .nav-container {
                padding: 0 14px;
            }

            .nav-logo {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>

    {{-- NAVBAR --}}
    <nav class="navbar">
        <div class="nav-container">
          
<a href="{{ route('home') }}" class="nav-logo">
    <img src="{{ asset('images/logo.fixpoint.jpeg') }}"
         alt="Logo FixPoint Service"
         class="logo-image">
    <span>FixPoint Service</span>
</a>

            
            {{-- Burger Button --}}
            <button class="nav-burger" id="navBurger" type="button" aria-label="Toggle menu" aria-controls="navMenu" aria-expanded="false">
                <span></span>
                <span></span>
                <span></span>
            </button>

            {{-- Menu Links --}}
            <ul class="nav-menu" id="navMenu">
                <li><a href="{{ route('home') }}" class="nav-link">Beranda</a></li>
                <li><a href="{{ route('ketersediaan-jadwal') }}" class="nav-link">Jadwal</a></li>
                <li><a href="{{ route('booking') }}" class="nav-link">Booking</a></li>
                <li><a href="{{ route('cek-status') }}" class="nav-link">Cek Status</a></li>
                
                @auth
                    @if(auth()->user()->role === 'ADMIN')
                        <li><a href="{{ route('admin.dashboard') }}" class="nav-link">Dashboard Admin</a></li>
                    @elseif(auth()->user()->role === 'PEMILIK_USAHA')
                        <li><a href="{{ route('pemilik.dashboard') }}" class="nav-link">Dashboard Pemilik</a></li>
                    @endif
                    
                    <li>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn-logout">Logout</button>
                        </form>
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
    <footer style="text-align: center; padding: 30px 20px; color: #6b7280; font-size: 13px; border-top: 1px solid #e5e7eb; margin-top: 40px;">
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