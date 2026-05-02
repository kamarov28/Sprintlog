@props([
    'variant' => 'primary', // primary, secondary, outline
    'href' => null,
    'type' => 'button',
    'class' => '',
])

@php
    $variantMap = [
        'primary' => 'btn-primary ui-button ui-button--primary',
        'secondary' => 'btn-secondary ui-button ui-button--secondary',
        'outline' => 'btn-outline ui-button ui-button--outline',
        'neon' => 'btn-neon ui-button ui-button--outline',
    ];

    $baseClass = $variantMap[$variant] ?? $variantMap['primary'];
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $baseClass . ' ' . $class]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $baseClass . ' ' . $class]) }}>
        {{ $slot }}
    </button>
@endif
