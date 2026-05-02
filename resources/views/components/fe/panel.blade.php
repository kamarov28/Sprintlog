@props([
    'title' => null,
    'subtitle' => null,
    'variant' => 'primary', // primary (lime), accent (purple)
    'class' => '',
    'headerClass' => '',
    'compact' => false,
])

@php
    $surfaceClasses = trim(implode(' ', [
        'hud-panel',
        'ui-surface',
        'ui-surface--' . $variant,
        $compact ? 'ui-surface--compact' : '',
        $class,
    ]));
@endphp

<div {{ $attributes->merge(['class' => $surfaceClasses]) }}>
    
    @if($title)
        <div class="ui-surface__header mb-4 {{ $headerClass }}">
            <h2 class="ui-surface__title {{ $variant === 'accent' ? 'text-accent' : 'text-main' }}" style="font-size: 1.2rem;">
                {{ $title }}
            </h2>
            @if($subtitle)
                <p class="text-gray ui-surface__subtitle" style="font-size: 0.8rem;">{{ $subtitle }}</p>
            @endif
        </div>
    @endif

    {{ $slot }}
</div>
