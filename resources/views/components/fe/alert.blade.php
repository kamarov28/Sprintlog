@props([
    'title' => null,
    'tone' => 'info',
    'class' => '',
])

@php
    $toneClasses = match ($tone) {
        'success' => 'alert-success bg-success/15 border-success/30 text-success-content',
        'danger' => 'alert-error bg-error/15 border-error/30 text-error-content',
        'warning' => 'alert-warning bg-warning/15 border-warning/30 text-warning-content',
        default => 'alert-info bg-info/15 border-info/30 text-info-content',
    };
@endphp

<div {{ $attributes->merge(['class' => 'alert border backdrop-blur-sm rounded-xl p-4 ' . $toneClasses . ' ' . $class]) }}>
    <div class="flex flex-col gap-1 w-full text-left">
        @if($title)
            <span class="font-bold text-sm">{{ $title }}</span>
        @endif
        <div class="text-xs opacity-90 leading-relaxed">
            {{ $slot }}
        </div>
    </div>
</div>
