@props([
    'title' => null,
    'subtitle' => null,
    'compact' => false,
])

@php
    $classes = trim('hud-panel be-panel'.($compact ? ' be-panel--compact' : ''));
@endphp

<section {{ $attributes->merge(['class' => $classes]) }}>
    @if($title || $subtitle)
        <div class="be-panel__header">
            @if($title)
                <h3>{{ $title }}</h3>
            @endif
            @if($subtitle)
                <p>{{ $subtitle }}</p>
            @endif
        </div>
    @endif

    {{ $slot }}
</section>
