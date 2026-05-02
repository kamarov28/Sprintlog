@props([
    'label',
    'value',
])

<div {{ $attributes->merge(['class' => 'be-stat']) }}>
    <div class="be-stat__label">{{ $label }}</div>
    <div class="be-stat__value">{{ $value }}</div>
</div>
