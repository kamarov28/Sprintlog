@props([
    'variant' => 'primary', // primary, secondary, outline, neon
    'href' => null,
    'type' => 'button',
    'class' => '',
])

@php
    $variantClasses = match ($variant) {
        'primary' => 'btn btn-primary font-semibold shadow-lg shadow-primary/10 hover:shadow-primary/25 transition-all duration-300 rounded-xl border-none',
        'secondary' => 'btn btn-secondary font-semibold shadow-lg shadow-secondary/10 hover:shadow-secondary/25 transition-all duration-300 rounded-xl border-none',
        'outline' => 'btn btn-outline border-slate-700 hover:border-slate-500 hover:bg-slate-800/50 backdrop-blur-sm transition-all duration-300 rounded-xl text-slate-200',
        'neon' => 'btn btn-accent font-semibold shadow-lg shadow-accent/10 hover:shadow-accent/25 transition-all duration-300 rounded-xl border-none',
        default => 'btn btn-primary font-semibold rounded-xl border-none',
    };

    $buttonClasses = trim($variantClasses . ' ' . $class);
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $buttonClasses]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $buttonClasses]) }}>
        {{ $slot }}
    </button>
@endif
