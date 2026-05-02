@props([
    'minWidth' => '720px',
])

<div {{ $attributes->merge(['class' => 'table-responsive be-table-wrap']) }} style="--be-table-min-width: {{ $minWidth }};">
    {{ $slot }}
</div>
