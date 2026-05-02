@extends('be.layouts.main')

@section('header_title', 'LANDING CMS')

@push('head_assets')
    <style>
        .landing-cms-workspace {
            display: grid;
            gap: clamp(1.5rem, 2.6vw, 2.25rem);
        }

        .landing-cms-hero {
            display: grid;
            grid-template-columns: minmax(0, 1.25fr) minmax(260px, 0.75fr);
            gap: clamp(1.25rem, 3vw, 2rem);
            align-items: stretch;
        }

        .landing-cms-status {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0.85rem;
            margin-top: 1.35rem;
        }

        .landing-cms-stat {
            border: 1px solid rgba(10, 10, 10, 0.08);
            border-radius: var(--radius-md);
            padding: 0.9rem 1rem;
            background: rgba(255, 255, 255, 0.46);
            min-width: 0;
        }

        .landing-cms-stat strong {
            display: block;
            margin-top: 0.35rem;
            font-size: 1.35rem;
        }

        .landing-cms-actions {
            display: grid;
            gap: 0.85rem;
            align-content: center;
        }

        .landing-cms-actions .btn-neon {
            width: 100%;
            min-width: 0;
            max-width: 100%;
            padding-inline: 1rem;
            box-sizing: border-box;
            text-align: center;
        }

        .landing-cms-filter {
            display: grid;
            grid-template-columns: minmax(220px, 1fr) 180px 160px auto;
            gap: 0.85rem;
            align-items: end;
        }

        .landing-cms-filter input,
        .landing-cms-filter select {
            width: 100%;
            padding: 0.65rem 0.75rem;
            font-family: var(--font-ui);
        }

        .landing-cms-type-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 0.85rem;
        }

        .landing-cms-type {
            border: 1px solid rgba(10, 10, 10, 0.08);
            border-radius: var(--radius-md);
            padding: 0.85rem;
            background: rgba(255, 255, 255, 0.38);
        }

        .landing-cms-module {
            min-width: 1080px;
        }

        .landing-cms-module__title {
            display: flex;
            align-items: flex-start;
            gap: 0.85rem;
        }

        .landing-cms-module__order {
            width: 2.15rem;
            height: 2.15rem;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--color-primary);
            color: var(--color-primary);
            flex: 0 0 auto;
        }

        .landing-cms-preview {
            max-width: 360px;
            white-space: normal;
            line-height: 1.55;
        }

        .landing-cms-health {
            display: flex;
            flex-wrap: wrap;
            gap: 0.35rem;
        }

        .landing-cms-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            border: 1px solid rgba(10, 10, 10, 0.18);
            border-radius: 999px;
            padding: 0.22rem 0.55rem;
            font-family: var(--font-ui);
            font-size: 0.67rem;
            color: var(--color-gray);
            background: rgba(255, 255, 255, 0.45);
        }

        .landing-cms-chip.is-ok {
            border-color: rgba(35, 126, 48, 0.55);
            color: #237e30;
        }

        .landing-cms-chip.is-warn {
            border-color: rgba(255, 68, 68, 0.58);
            color: #ff4444;
        }

        .landing-cms-row-actions {
            display: flex;
            justify-content: center;
            gap: 0.65rem;
            flex-wrap: wrap;
        }

        @media (max-width: 1100px) {
            .landing-cms-hero,
            .landing-cms-filter {
                grid-template-columns: 1fr;
            }

            .landing-cms-status,
            .landing-cms-type-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
    </style>
@endpush

@section('content')
    @php
        $activePercent = $totalCount > 0 ? round(($activeCount / $totalCount) * 100) : 0;
    @endphp

    <div class="landing-cms-workspace">
        <x-be.panel class="landing-cms-hero">
            <div>
                <div class="font-ui text-gray" style="font-size: 0.78rem;">HOMEPAGE_CONTENT_CONTROL</div>
                <h3 class="font-bank text-main" style="font-size: clamp(1.6rem, 3vw, 2.4rem); margin: 0.45rem 0 0;">
                    {{ $activeCount }} ACTIVE MODULES
                </h3>
                <p class="font-ui text-gray" style="font-size: 0.82rem; line-height: 1.65; max-width: 680px; margin: 0.75rem 0 0;">
                    Kelola urutan, copy, CTA, visibility, dan konfigurasi homepage tanpa buka file Blade.
                </p>

                <div class="landing-cms-status">
                    <div class="landing-cms-stat">
                        <span class="data-label">LIVE_RATIO</span>
                        <strong class="font-bank text-primary">{{ $activePercent }}%</strong>
                    </div>
                    <div class="landing-cms-stat">
                        <span class="data-label">HIDDEN</span>
                        <strong class="font-bank text-main">{{ $hiddenCount }}</strong>
                    </div>
                    <div class="landing-cms-stat">
                        <span class="data-label">COPY_READY</span>
                        <strong class="font-bank text-accent">{{ $copyReadyCount }}</strong>
                    </div>
                    <div class="landing-cms-stat">
                        <span class="data-label">CTA_READY</span>
                        <strong class="font-bank text-main">{{ $ctaCount }}</strong>
                    </div>
                </div>
            </div>

            <div class="landing-cms-actions">
                <form action="{{ route('be.landing-sections.seed-defaults') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn-neon" style="background: transparent;">FILL DEFAULTS</button>
                </form>
                <a href="{{ route('be.landing-sections.create') }}" class="btn-neon" style="text-decoration: none;">ADD MODULE</a>
                <a href="{{ route('home') }}" target="_blank" class="btn-neon" style="text-decoration: none; color: var(--color-gray); border-color: var(--color-gray);">VIEW HOME</a>

                <div class="font-ui text-gray" style="font-size: 0.74rem; line-height: 1.6; margin-top: 0.25rem;">
                    LAST UPDATE:
                    <span class="text-main">{{ $lastUpdated ? $lastUpdated->format('d M Y H:i') : 'NO DATA' }}</span>
                </div>
            </div>
        </x-be.panel>

        <x-be.panel>
            <form method="GET" class="landing-cms-filter">
                <x-be.field label="SEARCH_MODULE">
                    <input type="search" name="search" value="{{ $filters['search'] }}" placeholder="hero_main, tracking, service...">
                </x-be.field>
                <x-be.field label="TYPE">
                    <select name="type">
                        <option value="">ALL TYPES</option>
                        @foreach($typeOptions as $type => $label)
                            <option value="{{ $type }}" {{ $filters['type'] === $type ? 'selected' : '' }}>{{ strtoupper($label) }}</option>
                        @endforeach
                    </select>
                </x-be.field>
                <x-be.field label="STATUS">
                    <select name="status">
                        <option value="">ALL STATUS</option>
                        <option value="active" {{ $filters['status'] === 'active' ? 'selected' : '' }}>ACTIVE</option>
                        <option value="hidden" {{ $filters['status'] === 'hidden' ? 'selected' : '' }}>HIDDEN</option>
                    </select>
                </x-be.field>
                <button type="submit" class="btn-neon" style="background: transparent;">FILTER</button>
            </form>

            <div class="landing-cms-type-grid" style="margin-top: 1.25rem;">
                @foreach($typeOptions as $type => $label)
                    <a href="{{ route('be.landing-sections.index', array_filter(['type' => $type, 'status' => $filters['status']])) }}" class="landing-cms-type" style="text-decoration: none;">
                        <span class="data-label">{{ strtoupper($label) }}</span>
                        <div class="font-bank text-main" style="font-size: 1.35rem; margin-top: 0.3rem;">{{ $typeCounts[$type] ?? 0 }}</div>
                    </a>
                @endforeach
            </div>
        </x-be.panel>

        <x-be.panel>
            <x-be.table min-width="1080px">
                <table class="app-table landing-cms-module">
                    <thead>
                        <tr>
                            <th style="padding: 1rem 0.5rem;">MODULE</th>
                            <th style="padding: 1rem 0.5rem;">TYPE</th>
                            <th style="padding: 1rem 0.5rem;">CONTENT PREVIEW</th>
                            <th style="padding: 1rem 0.5rem;">HEALTH</th>
                            <th style="padding: 1rem 0.5rem;">STATUS</th>
                            <th style="padding: 1rem 0.5rem; text-align: center;">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sections as $section)
                            @php
                                $hasBody = filled($section->body);
                                $hasCta = filled($section->button_text) && filled($section->button_url);
                                $hasSettings = filled($section->settings);
                            @endphp
                            <tr>
                                <td style="padding: 1.15rem 0.5rem;">
                                    <div class="landing-cms-module__title">
                                        <span class="landing-cms-module__order font-ui">{{ $section->sort_order }}</span>
                                        <div>
                                            <div class="font-ui text-primary" style="font-weight: bold;">{{ $section->key }}</div>
                                            <div class="font-ui text-main" style="margin-top: 0.2rem;">{{ $section->title }}</div>
                                            @if($section->subtitle)
                                                <div class="font-ui text-gray" style="font-size: 0.75rem; margin-top: 0.18rem;">{{ $section->subtitle }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td style="padding: 1.15rem 0.5rem;">
                                    <span class="status-chip status-chip--accent">{{ strtoupper($section->type) }}</span>
                                </td>
                                <td style="padding: 1.15rem 0.5rem;">
                                    <div class="landing-cms-preview font-ui text-gray" style="font-size: 0.76rem;">
                                        {{ $hasBody ? \Illuminate\Support\Str::limit($section->body, 120) : 'No body copy configured.' }}
                                    </div>
                                    @if($hasCta)
                                        <div class="font-ui text-accent" style="font-size: 0.72rem; margin-top: 0.55rem;">
                                            CTA: {{ $section->button_text }} / {{ $section->button_url }}
                                        </div>
                                    @endif
                                </td>
                                <td style="padding: 1.15rem 0.5rem;">
                                    <div class="landing-cms-health">
                                        <span class="landing-cms-chip {{ $hasBody ? 'is-ok' : 'is-warn' }}">{{ $hasBody ? 'COPY' : 'NO_COPY' }}</span>
                                        <span class="landing-cms-chip {{ $hasCta ? 'is-ok' : '' }}">{{ $hasCta ? 'CTA' : 'NO_CTA' }}</span>
                                        <span class="landing-cms-chip {{ $hasSettings ? 'is-ok' : '' }}">{{ $hasSettings ? 'SETTINGS' : 'DEFAULTS' }}</span>
                                    </div>
                                </td>
                                <td style="padding: 1.15rem 0.5rem;">
                                    <span class="status-chip {{ $section->is_active ? 'status-chip--success' : 'status-chip--neutral' }}">
                                        {{ $section->is_active ? 'ACTIVE' : 'HIDDEN' }}
                                    </span>
                                </td>
                                <td style="padding: 1.15rem 0.5rem; text-align: center;">
                                    <div class="landing-cms-row-actions">
                                        <a href="{{ route('be.landing-sections.edit', $section) }}" class="btn-neon" style="text-decoration: none;">EDIT</a>
                                        <form action="{{ route('be.landing-sections.destroy', $section) }}" method="POST" onsubmit="return confirm('Delete this landing module?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-neon" style="border-color: red; color: red; background: transparent;">DELETE</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="table-empty">NO MODULES MATCH CURRENT FILTER.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </x-be.table>

            <x-be.pagination :paginator="$sections" />
        </x-be.panel>
    </div>
@endsection
