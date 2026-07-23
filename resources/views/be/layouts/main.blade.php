<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="sprintlog-dark">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SprintLog | Backend Control</title>

    @include('fe.layouts.partials.theme-head')

    <!-- Connect Custom Style -->
    <link rel="stylesheet" href="/css/style.css?v={{ config('app.asset_version') }}">
    <link rel="stylesheet" href="/css/be-toy.css?v={{ config('app.asset_version') }}">

    @stack('head_assets')
</head>

<body class="antialiased app-shell app-shell--be theme-toy theme-toy-be">

    <div class="be-container">

        @include('be.layouts.partials.sidebar')

        <div class="sidebar-overlay" onclick="document.getElementById('be-sidebar').classList.remove('active')"></div>

        <!-- Main Content Area -->
        <main class="main-content">
            @include('be.layouts.partials.top-nav')

            @if (session('success'))
                <div class="inline-alert inline-alert--success be-flash" role="status">
                    <span>{{ session('success') }}</span>
                    <button type="button" class="be-flash__close" onclick="this.closest('.be-flash').remove()" aria-label="Dismiss notification">x</button>
                </div>
            @endif

            @if (session('error'))
                <div class="inline-alert be-flash" role="alert">
                    <span>{{ session('error') }}</span>
                    <button type="button" class="be-flash__close" onclick="this.closest('.be-flash').remove()" aria-label="Dismiss notification">x</button>
                </div>
            @endif

            @if ($errors->any())
                <div class="inline-alert be-flash" role="alert">
                    <span>{{ $errors->first() }}</span>
                    <button type="button" class="be-flash__close" onclick="this.closest('.be-flash').remove()" aria-label="Dismiss notification">x</button>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    @stack('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const globalSearch = document.getElementById('be-global-search');

            document.addEventListener('keydown', (event) => {
                const target = event.target;
                const isTyping = target && ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName);

                if (event.key === '/' && !isTyping && globalSearch) {
                    event.preventDefault();
                    globalSearch.focus();
                }

                if (event.altKey && event.key.toLowerCase() === 'a') {
                    const boxes = document.querySelectorAll('#manifest-dispatch-form input[type="checkbox"]:not(:disabled)');
                    if (!boxes.length) {
                        return;
                    }

                    event.preventDefault();
                    const shouldCheck = Array.from(boxes).some((box) => !box.checked);
                    boxes.forEach((box) => {
                        box.checked = shouldCheck;
                    });
                }

                if (event.key === 'Escape') {
                    const flash = document.querySelector('.be-flash');
                    if (flash) {
                        flash.remove();
                    }
                }
            });

            // ── Sidebar collapse / expand ─────────────────────────────────
            const sidebar      = document.getElementById('be-sidebar');
            const collapseBtn  = document.getElementById('sidebar-collapse-btn');
            const openTab      = document.getElementById('sidebar-open-tab');
            const chevron      = document.getElementById('sidebar-chevron');
            const STORAGE_KEY  = 'sprintlog_sidebar_collapsed';

            const CHEVRON_LEFT  = 'M15 19l-7-7 7-7';  // «  collapse
            const CHEVRON_RIGHT = 'M9 5l7 7-7 7';      // »  expand

            function setSidebarCollapsed(collapsed) {
                if (!sidebar) return;
                if (collapsed) {
                    sidebar.classList.add('sidebar--collapsed');
                    document.body.classList.add('sidebar-is-collapsed');
                    if (chevron) chevron.querySelector('path').setAttribute('d', CHEVRON_RIGHT);
                } else {
                    sidebar.classList.remove('sidebar--collapsed');
                    document.body.classList.remove('sidebar-is-collapsed');
                    if (chevron) chevron.querySelector('path').setAttribute('d', CHEVRON_LEFT);
                }
                try { localStorage.setItem(STORAGE_KEY, collapsed ? '1' : '0'); } catch (_) {}
            }

            // Restore saved preference immediately (before paint)
            try {
                if (localStorage.getItem(STORAGE_KEY) === '1') {
                    setSidebarCollapsed(true);
                }
            } catch (_) {}

            if (collapseBtn) {
                collapseBtn.addEventListener('click', () => {
                    const isCollapsed = sidebar.classList.contains('sidebar--collapsed');
                    setSidebarCollapsed(!isCollapsed);
                });
            }

            if (openTab) {
                openTab.addEventListener('click', () => setSidebarCollapsed(false));
            }
        });
    </script>
</body>

</html>
