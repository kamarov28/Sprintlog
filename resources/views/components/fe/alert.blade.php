@props([
    'title' => null,
    'tone' => 'info',
])

@php
    $toneClass = match ($tone) {
        'success' => 'toy-alert--success',
        'danger' => 'toy-alert--danger',
        'warning' => 'toy-alert--warning',
        default => 'toy-alert--info',
    };
@endphp

<div {{ $attributes->merge(['class' => 'toy-alert ' . $toneClass]) }}>
    @if($title)
        <h4>{{ $title }}</h4>
    @endif
    <div class="toy-alert__body">
        {{ $slot }}
    </div>
</div>
