<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="sprintlog-dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'SprintLog | Expedition Logistics')</title>
    <meta name="description" content="@yield('meta_description', 'SprintLog adalah layanan ekspedisi logistik lokal cepat, stabil, dan hemat. Lakukan penjemputan paket (pickup), cek tarif ongkos kirim, dan pelacakan resi secara online.')">

    @include('fe.layouts.partials.theme-head')

    <!-- Compiled Tailwind & DaisyUI assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Page Specific Assets -->
    @stack('head_assets')
</head>
<body class="antialiased bg-[#070a13] text-slate-100 min-h-screen relative overflow-x-hidden @yield('body_class')">
    
    <!-- Smooth Interactive GSAP Glow Follower -->
    <div id="cursor-glow" class="fixed top-0 left-0 w-80 h-80 rounded-full bg-primary/15 blur-[120px] pointer-events-none z-[1] -translate-x-1/2 -translate-y-1/2 opacity-0"></div>

    <!-- Glow Background Radial Ambient Gradients (Zero Scroll Lag) -->
    <div class="glow-blob glow-blob-primary top-0 left-0"></div>
    <div class="glow-blob glow-blob-secondary top-1/3 right-0"></div>
    <div class="glow-blob glow-blob-primary bottom-0 left-1/4"></div>

    @auth
        @if(auth()->user()->role === 'customer' && (!auth()->user()->address || !auth()->user()->latitude))
            <div class="max-w-7xl mx-auto px-4 mt-4">
                <div class="alert alert-warning glass-panel border-warning/20 shadow-xl rounded-2xl p-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4" id="setup-alert" style="display: none;">
                    <div class="flex flex-col text-left">
                        <span class="font-bold text-sm text-warning">Profil belum lengkap</span>
                        <span class="text-xs text-slate-300 mt-1">Lengkapi alamat utama dan koordinat map untuk mengaktifkan order pickup pengiriman.</span>
                    </div>
                    <div class="flex gap-2 shrink-0">
                        <x-fe.button href="{{ route('profile') }}" variant="primary" class="btn-xs py-1.5 px-3">Lengkapi</x-fe.button>
                        <x-fe.button type="button" onclick="localStorage.setItem('hide-setup-alert', 'true'); document.getElementById('setup-alert').style.display='none'" variant="outline" class="btn-xs py-1.5 px-3">Nanti</x-fe.button>
                    </div>
                </div>
            </div>
            <script>
                if(!localStorage.getItem('hide-setup-alert')) {
                    document.getElementById('setup-alert').style.display = 'flex';
                }
            </script>
        @endif
    @endauth

    <!-- Floating Glass Navbar -->
    <header class="sticky top-0 z-50 w-full px-4 py-4">
        <div class="max-w-7xl mx-auto navbar glass-panel rounded-2xl border-white/5 backdrop-blur-md px-6 py-2">
            <div class="navbar-start">
                <a href="/" class="font-unbounded font-black text-xl md:text-2xl tracking-tight text-slate-100 hover:opacity-90 transition-opacity">
                    SPRINT<span class="text-primary">LOG</span>
                </a>
            </div>
            
            <div class="navbar-center hidden lg:flex">
                <ul class="menu menu-horizontal px-1 gap-4 text-xs font-bold tracking-wider uppercase font-alt">
                    <li><a href="/" class="hover:text-primary transition-colors py-2 px-3.5 rounded-xl flex items-center gap-1.5"><i class="bi bi-house text-sm"></i> Beranda</a></li>
                    <li><a href="{{ route('track.show') }}" class="hover:text-primary transition-colors py-2 px-3.5 rounded-xl flex items-center gap-1.5"><i class="bi bi-geo-alt text-sm"></i> Tracking</a></li>
                    @auth
                        @if(in_array(auth()->user()->role, ['admin', 'manager', 'cashier', 'courier']))
                        @php
                            $panelLabel = match(auth()->user()->role) {
                                'admin'   => 'Admin Panel',
                                'manager' => 'Manager Panel',
                                'cashier' => 'Kasir Panel',
                                'courier' => 'Kurir Panel',
                                default   => 'Staff Hub',
                            };
                        @endphp
                            <li><a href="{{ route('be.dashboard') }}" class="text-secondary hover:text-secondary-focus transition-colors py-2 px-3.5 rounded-xl flex items-center gap-1.5"><i class="bi bi-speedometer2 text-sm"></i> {{ $panelLabel }}</a></li>
                        @else
                            <li><a href="{{ route('dashboard') }}" class="hover:text-primary transition-colors py-2 px-3.5 rounded-xl flex items-center gap-1.5"><i class="bi bi-grid-fill text-sm"></i> Dashboard</a></li>
                            <li><a href="{{ route('profile') }}" class="hover:text-primary transition-colors py-2 px-3.5 rounded-xl flex items-center gap-1.5"><i class="bi bi-person-circle text-sm"></i> Profile</a></li>
                        @endif
                    @endauth
                </ul>
            </div>

            <div class="navbar-end gap-3 font-alt">
                @auth
                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline border-red-500/30 hover:border-red-500 hover:bg-red-500/10 text-red-400 hover:text-white rounded-xl font-bold px-4 transition-all duration-300 flex items-center gap-1.5 text-xs">
                            <i class="bi bi-box-arrow-right"></i> LOGOUT
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="btn btn-sm btn-primary rounded-xl font-bold px-5 text-xs tracking-wider flex items-center gap-1.5"><i class="bi bi-box-arrow-in-right"></i> LOGIN</a>
                    <a href="{{ route('register') }}" class="btn btn-sm btn-outline border-slate-700 hover:border-slate-500 rounded-xl font-bold px-5 text-slate-100 text-xs tracking-wider">REGISTER</a>
                @endauth

                <!-- Mobile Hamburger Dropdown -->
                <div class="dropdown dropdown-end lg:hidden">
                    <button tabindex="0" aria-label="Menu" class="btn btn-ghost btn-circle text-slate-200">
                        <i class="bi bi-list text-xl"></i>
                    </button>
                    <ul tabindex="0" class="menu menu-sm dropdown-content mt-3 z-[1] p-3 shadow-2xl glass-panel border-white/5 rounded-2xl w-56 gap-2 text-xs font-bold uppercase font-alt">
                        <li><a href="/" class="py-2.5 flex items-center gap-2"><i class="bi bi-house text-sm"></i> Beranda</a></li>
                        <li><a href="{{ route('track.show') }}" class="py-2.5 flex items-center gap-2"><i class="bi bi-geo-alt text-sm"></i> Tracking</a></li>
                        @auth
                            @if(!in_array(auth()->user()->role, ['admin', 'manager', 'cashier', 'courier']))
                                <li><a href="{{ route('dashboard') }}" class="py-2.5 flex items-center gap-2"><i class="bi bi-grid-fill text-sm"></i> Dashboard</a></li>
                                <li><a href="{{ route('profile') }}" class="py-2.5 flex items-center gap-2"><i class="bi bi-person-circle text-sm"></i> Profile</a></li>
                            @endif
                        @endauth
                    </ul>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content Shell -->
    <main class="max-w-7xl mx-auto px-4 py-8 relative z-10 min-h-[calc(100vh-250px)]">
        @if (session('success'))
            <div class="alert alert-success glass-panel border-success/20 text-success-content shadow-xl rounded-2xl p-4 mb-6">
                <div class="flex flex-col text-left">
                    <span class="font-bold text-sm">Berhasil</span>
                    <span class="text-xs opacity-90 mt-1">{{ session('success') }}</span>
                </div>
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

    <!-- Floating Back to Top Button -->
    <button type="button" id="back-to-top" class="fixed bottom-6 right-6 z-50 btn btn-circle btn-primary shadow-2xl transition-all duration-300 opacity-0 invisible scale-75 hover:scale-110 no-print cursor-pointer" onclick="window.scrollTo({top: 0, behavior: 'smooth'})" title="Kembali ke atas">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-black" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
        </svg>
    </button>
</body>
</html>
