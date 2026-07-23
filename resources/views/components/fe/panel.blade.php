@props([
    'title' => null,
    'subtitle' => null,
    'variant' => 'neutral', // neutral, primary, accent
    'class' => '',
    'headerClass' => '',
    'compact' => false,
])

@php
    $bgClass = match ($variant) {
        'primary' => 'glass-panel-primary border-primary/20',
        'accent' => 'glass-panel-accent border-secondary/20',
        default => 'glass-panel',
    };

    $panelClasses = trim(implode(' ', [
        $bgClass,
        'rounded-2xl shadow-xl border',
        $compact ? 'p-4' : 'p-6 md:p-8',
        $class,
    ]));
@endphp

<div {{ $attributes->merge(['class' => $panelClasses]) }}>
    @if($title)
        <div class="mb-5 {{ $headerClass }}">
            <h2 class="font-unbounded font-bold tracking-tight text-lg md:text-xl uppercase {{ $variant === 'primary' ? 'text-primary' : ($variant === 'accent' ? 'text-secondary' : 'text-slate-100') }}">
                {{ $title }}
            </h2>
            @if($subtitle)
                <p class="text-xs text-slate-400 mt-1 font-alt font-light">{{ $subtitle }}</p>
            @endif
        </div>
    @endif

    {{ $slot }}
</div>
