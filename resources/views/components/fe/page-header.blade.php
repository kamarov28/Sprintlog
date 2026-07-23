@props([
    'title',
    'subtitle' => null,
    'tone' => 'primary',
    'class' => '',
])

<div {{ $attributes->merge(['class' => 'flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8 ' . $class]) }}>
    <div>
        <h1 class="font-unbounded font-black tracking-tight text-3xl md:text-4xl uppercase {{ $tone === 'accent' ? 'text-secondary' : 'text-primary' }}">
            {{ $title }}
        </h1>
        @if($subtitle)
            <p class="text-sm text-slate-400 mt-2 max-w-2xl font-light font-alt">{{ $subtitle }}</p>
        @endif
    </div>

    @if(!$slot->isEmpty())
        <div class="flex items-center gap-3 flex-wrap">
            {{ $slot }}
        </div>
    @endif
</div>
