@props([
    'title' => 'NO DATA',
    'body' => 'Belum ada data yang cocok untuk konteks ini.',
    'actionHref' => null,
    'actionLabel' => null,
])

<div {{ $attributes->merge(['class' => 'be-empty']) }}>
    <div class="font-bank text-main be-empty__title">{{ $title }}</div>
    <div class="font-ui text-gray be-empty__body">{{ $body }}</div>
    @if($actionHref && $actionLabel)
        <a href="{{ $actionHref }}" class="be-btn be-btn--neutral be-btn--sm">{{ $actionLabel }}</a>
    @endif
</div>
