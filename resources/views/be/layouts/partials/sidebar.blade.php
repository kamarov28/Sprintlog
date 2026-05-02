@php
    $roleProfile = \App\Support\RoleProfile::for(auth()->user()?->loadMissing('branch'));
    $role = auth()->user()->role;
@endphp

<aside class="sidebar" id="be-sidebar">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <a href="{{ route('home') }}" class="sidebar-brand-link" aria-label="Kembali ke landing page">
            <div class="logo-text" style="font-size: 2rem; color: var(--color-text-main);">
                SPRINT<span style="color: var(--color-accent);">LOG</span>
            </div>
        </a>
        <button class="mobile-toggle sidebar-close" onclick="document.getElementById('be-sidebar').classList.toggle('active')" style="margin: 0; font-size: 1.5rem; border: 1px solid var(--color-panel-border); padding: 0 10px;" aria-label="Close sidebar">&times;</button>
    </div>

    <div class="sidebar-role-card">
        <div class="font-ui text-gray" style="font-size: 0.72rem;">{{ str_replace('_', ' ', $roleProfile['code']) }}</div>
        <div class="font-bank text-main" style="font-size: 1.05rem; margin-top: 0.35rem;">{{ $roleProfile['label'] }}</div>
        <div class="font-ui text-gray" style="font-size: 0.72rem; line-height: 1.45; margin-top: 0.45rem;">{{ $roleProfile['scope'] }}</div>
    </div>

    <nav class="sidebar-links" style="flex-grow: 1;">
        <div class="sidebar-section-label">Utama</div>
        <a href="{{ route('be.dashboard') }}" class="{{ request()->routeIs('be.dashboard') ? 'active' : '' }}">Dashboard</a>

        @if ($role === 'admin')
            <div class="sidebar-section-label">Kontrol Platform</div>
            <a href="{{ route('be.financial-reports.index') }}" class="{{ request()->routeIs('be.financial-reports.*') ? 'active' : '' }}">Ekonomi Hub</a>
            <a href="{{ route('be.landing-sections.index') }}" class="{{ request()->routeIs('be.landing-sections.*') ? 'active' : '' }}">Landing CMS</a>
            <a href="{{ route('be.branches.index') }}" class="{{ request()->routeIs('be.branches.*') ? 'active' : '' }}">Jaringan Hub</a>
            <a href="{{ route('be.users.index') }}" class="{{ request()->routeIs('be.users.*') ? 'active' : '' }}">Roster Manager</a>
        @endif

        @if ($role === 'manager')
            <div class="sidebar-section-label">Operasi Hub</div>
            <a href="{{ route('be.shipments.index', ['preset' => 'ready_dispatch']) }}" class="{{ request()->routeIs('be.shipments.*') && ! request()->routeIs('be.shipments.create') ? 'active' : '' }}">Operasi Paket</a>
            <a href="{{ route('be.pickups.index') }}" class="{{ request()->routeIs('be.pickups.*') ? 'active' : '' }}">Pickup Hub</a>
            <a href="{{ route('be.finance.index') }}" class="{{ request()->routeIs('be.finance.*') ? 'active' : '' }}">Rekap Finance</a>
            <a href="{{ route('be.users.index') }}" class="{{ request()->routeIs('be.users.*') ? 'active' : '' }}">Tim Hub</a>
        @endif

        @if ($role === 'cashier')
            <div class="sidebar-section-label">Counter</div>
            <a href="{{ route('be.shipments.create') }}" class="{{ request()->routeIs('be.shipments.create') ? 'active' : '' }}">Kasir POS</a>
            <a href="{{ route('be.pickups.index') }}" class="{{ request()->routeIs('be.pickups.*') ? 'active' : '' }}">Pickup Intake</a>
            <a href="{{ route('be.shipments.index') }}" class="{{ request()->routeIs('be.shipments.*') && ! request()->routeIs('be.shipments.create') ? 'active' : '' }}">Cari Paket</a>
            <a href="{{ route('be.users.index') }}" class="{{ request()->routeIs('be.users.*') ? 'active' : '' }}">Direktori Kurir</a>
        @endif

        @if ($role === 'courier')
            <div class="sidebar-section-label">Lapangan</div>
            <a href="{{ route('be.pickups.index') }}" class="{{ request()->routeIs('be.pickups.*') ? 'active' : '' }}">Pickup Saya</a>
            <a href="{{ route('be.shipments.index') }}" class="{{ request()->routeIs('be.shipments.*') ? 'active' : '' }}">Paket Saya</a>
        @endif
    </nav>

    <div style="border-top: 1px solid var(--color-panel-border); padding-top: 2rem; margin-top: 2rem;">
        <div class="font-ui text-gray mb-2" style="font-size: 0.8rem;">Masuk sebagai</div>
        <div class="font-bank text-accent" style="font-size: 1.1rem; margin-bottom: 1rem;">{{ auth()->user()->name }}</div>
        <div class="font-ui" style="font-size: 0.8rem; background: var(--color-text-main); color: var(--color-bg); padding: 5px; display: inline-block; margin-bottom: 0.6rem;">
            Role: {{ $roleProfile['label'] }}
        </div>
        <div class="font-ui text-gray" style="font-size: 0.72rem; line-height: 1.45; margin-bottom: 1.5rem;">
            Hub: {{ $roleProfile['hub'] }}
        </div>

        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <x-fe.button type="submit" variant="secondary" style="width: 100%;">Logout</x-fe.button>
        </form>
    </div>
</aside>
