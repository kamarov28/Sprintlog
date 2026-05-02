@props([
    'min' => '230px',
])

<div {{ $attributes->merge(['class' => 'be-form-grid']) }} style="--be-form-grid-min: {{ $min }};">
    {{ $slot }}
</div>
