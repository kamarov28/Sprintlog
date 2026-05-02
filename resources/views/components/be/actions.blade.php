@props([
    'align' => 'end',
])

@php
    $classes = trim("be-actions be-actions--{$align}");
@endphp

<div {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</div>
