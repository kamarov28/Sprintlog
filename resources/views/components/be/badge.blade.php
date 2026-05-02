@props([
    'variant' => 'neutral',
])

<span {{ $attributes->merge(['class' => "be-badge be-badge--{$variant}"]) }}>
    {{ $slot }}
</span>
