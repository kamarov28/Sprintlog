@props([
    'columns' => 3,
])

@php
    $classes = trim("be-grid be-grid--{$columns}");
@endphp

<div {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</div>
