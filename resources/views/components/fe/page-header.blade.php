@props([
    'title',
    'subtitle' => null,
    'tone' => 'primary',
])

<div {{ $attributes->merge(['class' => 'page-header toy-page-header']) }}>
    <div>
        <h1 class="toy-page-title {{ $tone === 'accent' ? 'text-accent' : 'text-primary' }}">
            {{ $title }}
        </h1>
        @if($subtitle)
            <p class="toy-page-subtitle text-gray">{{ $subtitle }}</p>
        @endif
    </div>

    @if(!$slot->isEmpty())
        <div class="toy-page-actions">
            {{ $slot }}
        </div>
    @endif
</div>
