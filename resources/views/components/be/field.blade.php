@props([
    'label' => null,
    'for' => null,
])

<div {{ $attributes->merge(['class' => 'be-field']) }}>
    @if($label)
        <label @if($for) for="{{ $for }}" @endif class="font-ui text-gray">{{ $label }}</label>
    @endif

    {{ $slot }}
</div>
