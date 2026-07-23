@php
    $roleProfile = \App\Support\RoleProfile::for(auth()->user()?->loadMissing('branch'));
    $role = auth()->user()->role;
@endphp

<aside class="sidebar" id="be-sidebar">
    <div class="sidebar-header-row">
        <a href="{{ route('home') }}" class="sidebar-brand-link" aria-label="Kembali ke landing page" style="text-decoration: none;">
            <div class="logo-text" style="font-size: 1.9rem; color: var(--color-text-main); white-space: nowrap;">
                SPRINT<span style="color: var(--color-accent);">LOG</span>
            </div>
        </a>
        <button
            id="sidebar-collapse-btn"
            class="sidebar-collapse-btn mobile-hidden"
            aria-label="Toggle sidebar"
            title="Sembunyikan / tampilkan sidebar"
        >
            {{-- Icon: left-pointing chevrons when expanded, right-pointing when collapsed --}}
            <svg id="sidebar-chevron" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
        </button>
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

    <div class="sidebar-user-footer" style="border-top: 1px solid var(--color-panel-border); padding-top: 2rem; margin-top: 2rem;">
        <div class="font-ui text-gray mb-2" style="font-size: 0.8rem;">Masuk sebagai</div>
        <div class="font-bank text-accent" style="font-size: 1.1rem; margin-bottom: 1rem;">{{ auth()->user()->name }}</div>
        <div class="font-ui" style="font-size: 0.78rem; background: rgba(167, 139, 250, 0.15); color: #a78bfa; border: 1px solid rgba(167, 139, 250, 0.3); padding: 4px 10px; display: inline-block; border-radius: 8px; margin-bottom: 0.6rem; font-weight: 700;">
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

{{-- Floating tab: visible only when sidebar is collapsed, clicking it re-opens the sidebar --}}
<button
    id="sidebar-open-tab"
    class="sidebar-open-tab"
    aria-label="Buka sidebar"
    title="Tampilkan sidebar"
>
    <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
    </svg>
</button>
