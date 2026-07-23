@props([
    'label' => null,
    'id' => null,
    'type' => 'text',
    'placeholder' => null,
    'value' => null,
    'required' => false,
    'class' => '',
])

@php
    $inputId = $id ?: 'input_' . Str::random(8);
    $controlClass = "w-full font-sans font-normal tracking-normal text-sm bg-slate-950/40 border-slate-800 focus:border-primary/60 text-slate-100 placeholder:text-slate-500 rounded-xl backdrop-blur-sm transition-all focus:outline-none";
@endphp

<div class="form-control w-full mb-4 {{ $class }}">
    @if($label)
        <label for="{{ $inputId }}" class="label py-1">
            <span class="label-text text-slate-300 font-sans font-semibold text-xs tracking-normal">{{ $label }}</span>
        </label>
    @endif

    @if($type === 'textarea')
        <textarea id="{{ $inputId }}" 
                  {{ $attributes->merge(['class' => 'textarea textarea-bordered ' . $controlClass, 'rows' => 3]) }}
                  @if($required) required @endif>{{ $slot->isEmpty() ? $value : $slot }}</textarea>
    @elseif($type === 'select')
        <select id="{{ $inputId }}" 
                {{ $attributes->merge(['class' => 'select select-bordered ' . $controlClass]) }}
                @if($required) required @endif>
            {{ $slot }}
        </select>
    @else
        <div class="relative w-full">
            <input type="{{ $type }}" id="{{ $inputId }}" value="{{ $value }}" placeholder="{{ $placeholder }}"
                {{ $attributes->merge(['class' => 'input input-bordered ' . $controlClass . ($type === 'password' ? ' pr-20' : '')]) }}
                @if($required) required @endif>
            
            @if($type === 'password')
                <button type="button" 
                        onclick="const input = document.getElementById('{{ $inputId }}'); input.type = input.type === 'password' ? 'text' : 'password'; this.textContent = input.type === 'password' ? 'SHOW' : 'HIDE';"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400 hover:text-primary transition-colors cursor-pointer select-none">
                    SHOW
                </button>
            @endif
        </div>
    @endif
</div>
