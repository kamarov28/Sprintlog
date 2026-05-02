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
    $controlStyle = "width: 100%; border: 1px solid var(--glass-control-border, rgba(255,255,255,0.82)); border-radius: var(--radius-md, 16px); background: var(--glass-control-bg, rgba(255,255,255,0.58)); color: var(--color-text-main); font-size: 1.05rem; padding: 0.82rem 1rem; outline: none;";
    $textareaStyle = $controlStyle . " resize: vertical; min-height: 104px; line-height: 1.5;";
@endphp

<div class="field-group mb-4 {{ $class }}">
    @if($label)
        <label for="{{ $inputId }}" class="text-gray field-label" style="font-size: 0.8rem;">
            {{ $label }}
        </label><br>
    @endif

    @if($type === 'textarea')
        <textarea id="{{ $inputId }}" {{ $attributes->except(['style', 'class'])->merge(['class' => 'ui-control ' . $attributes->get('class'), 'rows' => 2]) }} 
                  style="{{ $textareaStyle }} {{ $attributes->get('style') }}"
                  @if($required) required @endif>{{ $slot->isEmpty() ? $value : $slot }}</textarea>
    @elseif($type === 'select')
        <select id="{{ $inputId }}" {{ $attributes->except(['style', 'class'])->merge(['class' => 'ui-control ' . $attributes->get('class')]) }}
                style="{{ $controlStyle }} {{ $attributes->get('style') }}"
                @if($required) required @endif>
            {{ $slot }}
        </select>
    @else
        <div style="position: relative;">
            <input type="{{ $type }}" id="{{ $inputId }}" value="{{ $value }}" placeholder="{{ $placeholder }}"
                {{ $attributes->except(['style', 'class'])->merge(['class' => 'ui-control ' . $attributes->get('class')]) }}
                style="{{ $controlStyle }} {{ $type === 'password' ? 'padding-right: 5.5rem;' : '' }} {{ $attributes->get('style') }}"
                @if($required) required @endif>
            
            @if($type === 'password')
                <button type="button" 
                        onclick="const input = document.getElementById('{{ $inputId }}'); input.type = input.type === 'password' ? 'text' : 'password'; this.textContent = input.type === 'password' ? 'SHOW' : 'HIDE';"
                        class="password-toggle">
                    SHOW
                </button>
            @endif
        </div>
    @endif
</div>
