<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'SprintLog | Expedition Logistics')</title>

    @include('fe.layouts.partials.theme-head')

    <!-- Connect CSS -->
    <link rel="stylesheet" href="/css/style.css?v={{ config('app.asset_version') }}">
    <link rel="stylesheet" href="/css/background-clean.css?v={{ config('app.asset_version') }}">

    <!-- Tailwind (Optional for layouts, but sticking to custom primarily) -->
    <!-- Page Specific Assets -->
    @stack('head_assets')
</head>
<body class="antialiased app-shell app-shell--fe theme-toy @yield('body_class')">
    
    @auth
        @if(auth()->user()->role === 'customer' && (!auth()->user()->address || !auth()->user()->latitude))
            <div class="protocol-alert" id="setup-alert" style="display: none;">
                <h4>Profile belum lengkap</h4>
                <p>Lengkapi alamat untuk mengaktifkan pengiriman.</p>
                <p style="margin-top: 0.5rem;">Lengkapi profil supaya pickup dan pengiriman bisa diproses.</p>
                <div style="margin-top: 1rem; text-align: right;">
                    <x-fe.button href="{{ route('profile') }}" variant="primary" style="font-size: 0.6rem; padding: 4px 10px;">Lengkapi</x-fe.button>
                    <x-fe.button type="button" onclick="localStorage.setItem('hide-setup-alert', 'true'); document.getElementById('setup-alert').style.display='none'" variant="secondary" style="font-size: 0.6rem; padding: 4px 10px;">Nanti</x-fe.button>
                </div>
            </div>
            <script>
                if(!localStorage.getItem('hide-setup-alert')) {
                    document.getElementById('setup-alert').style.display = 'block';
                }
            </script>
        @endif
    @endauth

    <!-- Navbar -->
    <header class="fe-header">
        <div class="logo-text">SPRINT<span class="accent">LOG</span></div>
        <nav class="fe-nav">
            <a href="/">BERANDA</a>
            <a href="{{ route('track.show') }}">TRACKING</a>
            @auth
                @if(in_array(auth()->user()->role, ['admin', 'manager', 'cashier', 'courier']))
                    <a href="{{ route('be.dashboard') }}" class="text-accent">STAFF HUB</a>
                @else
                    <a href="{{ route('dashboard') }}">DASHBOARD</a>
                    <a href="{{ route('profile') }}">PROFILE</a>
                @endif
                <form action="{{ route('logout') }}" method="POST" style="margin-left: 3rem;">
                    @csrf
                    <x-fe.button type="submit" variant="secondary" class="btn-logout" style="padding: 5px 15px; font-size: 0.8rem; border-color: #ff4444; background-color: #ff4444;">Logout</x-fe.button>
                </form>
            @else
                <x-fe.button href="{{ route('login') }}" variant="primary" style="margin-left:2rem; padding: 5px 15px;">LOGIN</x-fe.button>
                <x-fe.button href="{{ route('register') }}" variant="secondary" style="margin-left:1rem; padding: 5px 15px;">REGISTER</x-fe.button>
            @endauth
        </nav>
    </header>

    <!-- Main Content -->
    <main class="cyber-container">
        @if (session('success'))
            <div class="protocol-alert-static toy-alert toy-alert--success" style="margin-top: 1.5rem;">
                <h4>Berhasil</h4>
                <p>{{ session('success') }}</p>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Footer -->
    @include('fe.layouts.partials.footer')

    <script>
        window.previewImage = window.previewImage || function(input) {
            const file = input && input.files ? input.files[0] : null;
            if (!file) return;

            const preview = document.getElementById('photo-preview');
            const placeholder = document.getElementById('photo-placeholder');
            const reader = new FileReader();

            reader.onload = function(event) {
                if (preview) {
                    preview.src = event.target.result;
                    preview.style.display = 'block';
                }

                if (placeholder) {
                    placeholder.style.display = 'none';
                }
            };

            reader.readAsDataURL(file);
        };
    </script>

    @stack('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.SPRINTLOG_GSAP_PAGE) {
                return;
            }

            const revealTargets = document.querySelectorAll('.section-animate, .hud-panel');

            if (!('IntersectionObserver' in window)) {
                revealTargets.forEach((target) => target.classList.add('is-visible'));
                return;
            }

            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (!entry.isIntersecting) {
                        return;
                    }

                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                });
            }, {
                threshold: 0.12,
                rootMargin: '0px 0px -8% 0px',
            });

            revealTargets.forEach((target) => {
                target.classList.add('reveal-on-scroll');
                observer.observe(target);
            });
        });
    </script>
</body>
</html>
