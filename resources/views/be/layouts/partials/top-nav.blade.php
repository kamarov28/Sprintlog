@php
    $pageContext = \App\Support\BackendPageContext::for(request(), auth()->user());
    $pageTitle = trim($__env->yieldContent('header_title', $pageContext['title'])) ?: $pageContext['title'];
@endphp

<header class="top-nav top-nav--context">
    <div class="top-nav__identity">
        <button class="mobile-toggle" onclick="document.getElementById('be-sidebar').classList.toggle('active')" aria-label="Open sidebar">
            <span aria-hidden="true">&#9776;</span>
            <span class="mobile-toggle__text">Menu</span>
        </button>
        <div>
            <div class="font-ui text-gray top-nav__section">{{ str_replace('_', ' ', $pageContext['section']) }}</div>
            <h2 class="font-bank text-main" style="font-size: 1.8rem; margin: 0;">{{ $pageTitle }}</h2>
            <div class="font-ui text-gray top-nav__description">{{ $pageContext['description'] }}</div>
            @if(! empty($pageContext['actions']))
                <div class="top-nav__actions">
                    @foreach($pageContext['actions'] as $action)
                        <a href="{{ $action['href'] }}" class="be-btn be-btn--{{ $action['tone'] ?? 'neutral' }} be-btn--sm">{{ $action['label'] }}</a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
    <div class="top-nav__tools">
        <form action="{{ route('be.search') }}" method="GET" class="be-quick-search" role="search">
            <label for="be-global-search" class="sr-only">Global search</label>
            <input id="be-global-search" type="search" name="q" value="{{ request('q') }}" placeholder="search resi, customer, hub..." autocomplete="off">
            <button type="submit" class="be-btn be-btn--sm">Cari</button>
        </form>
        <div class="font-ui text-gray top-nav__date">{{ now()->translatedFormat('d M Y') }}</div>
    </div>
</header>

@if(! empty($pageContext['hints']))
    <div class="be-context-strip">
        @foreach($pageContext['hints'] as $hint)
            <span>{{ $hint }}</span>
        @endforeach
    </div>
@endif
