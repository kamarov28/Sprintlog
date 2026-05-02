@extends('be.layouts.main')

@section('header_title', $mode === 'create' ? 'ADD LANDING MODULE' : 'EDIT LANDING MODULE')

@section('content')
    @php
        $settings = old('settings_json') ? [] : ($section->settings ?? []);
        $currentType = old('type', $section->type);
        $currentVariant = old('settings_variant', $settings['variant'] ?? 'neutral');
    @endphp

    <div class="hud-panel" style="max-width: 760px; margin: 0 auto;">
        <h3 class="font-bank text-accent mb-4">MODULE CONFIGURATION</h3>

        @if ($errors->any())
            <div class="inline-alert">
                <div class="font-ui text-gray" style="font-size: 0.8rem; margin-bottom: 0.5rem;">VALIDATION ERRORS:</div>
                <ul style="margin: 0; padding-left: 1.5rem;">
                    @foreach ($errors->all() as $error)
                        <li class="font-ui text-gray" style="font-size: 0.85rem;">{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ $mode === 'create' ? route('be.landing-sections.store') : route('be.landing-sections.update', $section) }}" method="POST">
            @csrf
            @if($mode === 'edit')
                @method('PUT')
            @endif

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                <div>
                    <label class="font-ui text-gray" style="font-size: 0.8rem;">MODULE_KEY</label>
                    <input id="module_key" type="text" name="key" value="{{ old('key', $section->key) }}" required placeholder="hero_main" style="width: 100%; padding: 0.55rem; font-family: var(--font-ui);">
                    <div class="font-ui text-gray" style="font-size: 0.68rem; margin-top: 0.35rem;">UNIQUE_ID. Auto-filled from title if empty.</div>
                </div>
                <div>
                    <label class="font-ui text-gray" style="font-size: 0.8rem;">MODULE_TYPE</label>
                    <select id="module_type" name="type" required style="width: 100%; padding: 0.55rem; font-family: var(--font-ui);">
                        @foreach(['hero', 'route_step', 'service_card', 'feature_panel', 'cta'] as $type)
                            <option value="{{ $type }}" {{ $currentType === $type ? 'selected' : '' }}>{{ strtoupper($type) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label class="font-ui text-gray" style="font-size: 0.8rem;">TITLE</label>
                <input id="module_title" type="text" name="title" value="{{ old('title', $section->title) }}" required style="width: 100%; padding: 0.55rem; font-family: var(--font-ui);">
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label class="font-ui text-gray" style="font-size: 0.8rem;">SUBTITLE</label>
                <input type="text" name="subtitle" value="{{ old('subtitle', $section->subtitle) }}" style="width: 100%; padding: 0.55rem; font-family: var(--font-ui);">
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label class="font-ui text-gray" style="font-size: 0.8rem;">BODY_COPY</label>
                <textarea name="body" rows="5" style="width: 100%; padding: 0.55rem; font-family: var(--font-ui);">{{ old('body', $section->body) }}</textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                <div>
                    <label class="font-ui text-gray" style="font-size: 0.8rem;">BUTTON_TEXT</label>
                    <input type="text" name="button_text" value="{{ old('button_text', $section->button_text) }}" style="width: 100%; padding: 0.55rem; font-family: var(--font-ui);">
                </div>
                <div>
                    <label class="font-ui text-gray" style="font-size: 0.8rem;">BUTTON_URL</label>
                    <input type="text" name="button_url" value="{{ old('button_url', $section->button_url) }}" placeholder="/track or #rates" style="width: 100%; padding: 0.55rem; font-family: var(--font-ui);">
                </div>
            </div>

            <div id="hero_settings" style="margin-bottom: 1.5rem; padding: 1rem; border: 1px dashed var(--color-panel-border);">
                <div class="font-ui text-primary" style="font-size: 0.8rem; margin-bottom: 1rem;">HERO_SETTINGS</div>
                <div style="margin-bottom: 1rem;">
                    <label class="font-ui text-gray" style="font-size: 0.8rem;">TOP_KICKER</label>
                    <input type="text" name="settings_kicker" value="{{ old('settings_kicker', $settings['kicker'] ?? '') }}" placeholder="INDONESIA ROUTING INTERFACE" style="width: 100%; padding: 0.55rem; font-family: var(--font-ui);">
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    <div>
                        <label class="font-ui text-gray" style="font-size: 0.8rem;">SECONDARY_BUTTON_TEXT</label>
                        <input type="text" name="settings_secondary_button_text" value="{{ old('settings_secondary_button_text', $settings['secondary_button_text'] ?? '') }}" placeholder="GET QUOTE" style="width: 100%; padding: 0.55rem; font-family: var(--font-ui);">
                    </div>
                    <div>
                        <label class="font-ui text-gray" style="font-size: 0.8rem;">SECONDARY_BUTTON_URL</label>
                        <input type="text" name="settings_secondary_button_url" value="{{ old('settings_secondary_button_url', $settings['secondary_button_url'] ?? '') }}" placeholder="#rates" style="width: 100%; padding: 0.55rem; font-family: var(--font-ui);">
                    </div>
                </div>
            </div>

            <div id="service_settings" style="margin-bottom: 1.5rem; padding: 1rem; border: 1px dashed var(--color-panel-border);">
                <div class="font-ui text-primary" style="font-size: 0.8rem; margin-bottom: 1rem;">SERVICE_CARD_STYLE</div>
                <label class="font-ui text-gray" style="font-size: 0.8rem;">CARD_VARIANT</label>
                <select name="settings_variant" style="width: 100%; padding: 0.55rem; font-family: var(--font-ui);">
                    @foreach(['neutral', 'primary', 'accent'] as $variant)
                        <option value="{{ $variant }}" {{ $currentVariant === $variant ? 'selected' : '' }}>{{ strtoupper($variant) }}</option>
                    @endforeach
                </select>
                <div class="font-ui text-gray" style="font-size: 0.72rem; margin-top: 0.45rem;">Neutral = standard, Primary = lime-highlight, Accent = violet-border.</div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                <div>
                    <label class="font-ui text-gray" style="font-size: 0.8rem;">SORT_ORDER</label>
                    <input type="number" name="sort_order" min="0" value="{{ old('sort_order', $section->sort_order) }}" required style="width: 100%; padding: 0.55rem; font-family: var(--font-ui);">
                </div>
                <div>
                    <label class="font-ui text-gray" style="font-size: 0.8rem;">VISIBILITY</label>
                    <label style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.75rem;" class="font-ui text-main">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $section->is_active) ? 'checked' : '' }}>
                        ACTIVE_ON_HOMEPAGE
                    </label>
                </div>
            </div>

            <div style="margin-bottom: 2rem;">
                <details>
                    <summary class="font-ui text-gray" style="font-size: 0.8rem; cursor: pointer;">ADVANCED_SETTINGS_JSON</summary>
                    <textarea name="settings_json" rows="6" placeholder='{"variant":"primary"}' style="width: 100%; padding: 0.55rem; font-family: var(--font-ui); margin-top: 0.75rem;">{{ old('settings_json') }}</textarea>
                    <div class="font-ui text-gray" style="font-size: 0.72rem; margin-top: 0.45rem;">
                        Optional. Friendly fields above will override the same keys, so most admins can leave this empty.
                    </div>
                </details>
            </div>

            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <a href="{{ route('be.landing-sections.index') }}" class="btn-neon" style="text-decoration: none; color: var(--color-gray); border-color: var(--color-gray);">ABORT</a>
                <button type="submit" class="btn-neon" style="padding: 10px 40px; background: transparent;">SAVE MODULE</button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const typeSelect = document.getElementById('module_type');
    const titleInput = document.getElementById('module_title');
    const keyInput = document.getElementById('module_key');
    const heroSettings = document.getElementById('hero_settings');
    const serviceSettings = document.getElementById('service_settings');

    function syncTypeSettings() {
        const type = typeSelect.value;
        heroSettings.style.display = type === 'hero' ? 'block' : 'none';
        serviceSettings.style.display = type === 'service_card' ? 'block' : 'none';
    }

    function slugify(value) {
        return value
            .toString()
            .trim()
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '_')
            .replace(/^_+|_+$/g, '');
    }

    titleInput.addEventListener('input', function () {
        if (!keyInput.value.trim()) {
            keyInput.value = slugify(titleInput.value);
        }
    });

    typeSelect.addEventListener('change', syncTypeSettings);
    syncTypeSettings();
});
</script>
@endpush
