@extends('fe.layouts.main')

@section('body_class', 'landing-macaron-page')

@push('head_assets')
    <link rel="preconnect" href="https://unpkg.com">
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
@endpush

@section('content')
@php
    $hero = $landing['hero'] ?? null;
    $heroSettings = $hero?->settings ?? [];
    $heroTitle = 'SPRINTLOG';
    $heroSubtitle = 'Antar paket tanpa ribet';
    $heroBody = 'Pickup cepat, cek ongkir, dan tracking paket. Satu alur ringan.';
    $heroPrimaryText = 'Cek Tracking';
    $heroPrimaryUrl = route('track.show');
    $heroSecondaryText = 'Cek Ongkir';
    $heroSecondaryUrl = '#rates';
    $routeSteps = collect([
            (object) ['title' => 'Step 01', 'body' => 'Isi detail pickup dan tujuan paket.'],
            (object) ['title' => 'Step 02', 'body' => 'Kurir mengambil paket sesuai jadwal.'],
            (object) ['title' => 'Step 03', 'body' => 'Hub memverifikasi pembayaran dan paket.'],
            (object) ['title' => 'Step 04', 'body' => 'Paket bergerak dan bisa dilacak.'],
        ]);
    $serviceCards = collect([
            (object) ['title' => 'BEST', 'subtitle' => 'Paling cepat', 'body' => "Estimasi 1 hari.\nCocok untuk paket prioritas.", 'settings' => ['variant' => 'primary']],
            (object) ['title' => 'REGULAR', 'subtitle' => 'Paling stabil', 'body' => "Estimasi 2-4 hari.\nRute harian antar kota.", 'settings' => ['variant' => 'neutral']],
            (object) ['title' => 'KARGO', 'subtitle' => 'Paling hemat', 'body' => "Untuk paket berat.\nMinimum 10 kg.", 'settings' => ['variant' => 'accent']],
        ]);
    $featurePanels = collect();
    $ctas = collect();
    $shouldAutoQuote = request()->filled(['origin_prov', 'origin_kota_id', 'destination_prov', 'destination_kota_id', 'weight']);
@endphp

<div class="macaron-shell">
    <section class="macaron-workspace">
        <div class="macaron-document">
    <!-- Hero Section -->
    <section class="kinetic-hero">
        <div>
            <div class="hero-kicker">Logistik lokal yang gampang dipakai</div>

            <h1 class="hero-title">
                <span class="hero-line">{!! str_replace('LOG', '<span style="color: var(--color-accent);">LOG</span>', e($heroTitle)) !!}</span>
                <span class="hero-subline">{{ $heroSubtitle }}</span>
            </h1>

            <p class="text-gray hero-subtitle">
                {{ $heroBody }}
            </p>

            <div class="hero-actions">
                <x-fe.button href="{{ $heroPrimaryUrl }}" variant="primary">{{ $heroPrimaryText }}</x-fe.button>
                <x-fe.button href="{{ $heroSecondaryUrl }}" variant="secondary">{{ $heroSecondaryText }}</x-fe.button>
            </div>
        </div>

        <aside class="type-stack" aria-label="Operational highlights">
            <div class="toy-route-card" aria-hidden="true">
                <div class="toy-route-card__label">SprintLog</div>
                <div class="toy-route-card__road">
                    <span></span>
                    <strong></strong>
                    <i></i>
                </div>
                <div class="toy-parcels">
                    <span>BEST</span>
                    <span>REG</span>
                    <span>KARGO</span>
                </div>
            </div>
            <div class="type-stack__row">
                <span>Layanan</span>
                <strong>BEST / REGULAR / KARGO</strong>
            </div>
            <div class="type-stack__row">
                <span>Pembayaran</span>
                <strong>Transfer + Cash Pickup</strong>
            </div>
            <div class="type-stack__row">
                <span>Hub</span>
                <strong>Kurir ke Kasir</strong>
            </div>
            <div class="data-marquee">
                <span>Tracking siap / Ongkir cepat / Pickup tersedia / Verifikasi hub aktif / Tracking siap / Ongkir cepat / </span>
            </div>
        </aside>
    </section>

    <div class="route-strip section-animate" aria-label="SprintLog operating flow">
        @foreach($routeSteps as $step)
            <div class="route-strip__item">
                <span class="route-strip__code">{{ $step->title }}</span>
                <span class="route-strip__label">{{ $step->body }}</span>
            </div>
        @endforeach
    </div>

    <!-- Interface Section -->
    <div class="section-animate landing-tools-section" style="margin-top: 8rem;">
        <div class="landing-tool-tabs" role="tablist" aria-label="Pilih alat cepat">
            <button type="button" class="landing-tool-tab is-active" data-tool-tab="tracer" role="tab" aria-controls="tracer" aria-selected="true">Cek Resi</button>
            <button type="button" class="landing-tool-tab" data-tool-tab="rates" role="tab" aria-controls="rates" aria-selected="false">Cek Ongkir</button>
        </div>

        <div class="grid-2 landing-tools-grid" style="gap: 4rem;">
        
        <!-- Tracking Module -->
        <x-fe.panel id="tracer" title="Cek Resi" subtitle="{{ $shipment ? 'Data paket ditemukan' : 'Masukkan nomor resi' }}" variant="primary" class="landing-tool-panel is-active">
            <form action="{{ route('home') }}" method="GET" style="margin-top: 1.5rem;">
                <x-fe.input label="Nomor Resi" name="receipt" placeholder="SPRINT-___" value="{{ request('receipt') }}" required />

                <div class="toy-result-box">
                    @if($shipment)
                        <span class="text-accent" style="font-size: 0.8rem; margin-bottom: 1rem;">Resi {{ $shipment->tracking_number }}</span>
                        @foreach($shipment->trackings as $log)
                            <div style="margin-bottom: 0.8rem; border-bottom: 1px dashed rgba(0,0,0,0.1); padding-bottom: 5px;">
                                <span style="font-size: 0.75rem; color: var(--color-gray);">{{ $log->tracked_at->format('H:i') }}</span>
                                <span style="font-size: 0.85rem; color: var(--color-text-main); font-weight: bold;"> {{ strtoupper($log->location) }}</span><br>
                                <span style="font-size: 0.8rem; color: var(--color-accent);">{{ $log->description }}</span>
                            </div>
                        @endforeach
                    @elseif(request('receipt'))
                        <span style="color: #c72c41; font-size: 0.85rem;">Resi belum ditemukan.</span>
                        <span class="text-gray" style="font-size: 0.75rem; margin-top: 5px;">Cek kembali nomor resi lalu coba lagi.</span>
                    @else
                        <span class="text-gray" style="font-size: 0.8rem;">Status paket akan tampil di sini.</span>
                    @endif
                </div>
                <x-fe.button type="submit" variant="primary" style="width: 100%;">Cek Resi</x-fe.button>
            </form>
        </x-fe.panel>

        <!-- Quote/Rates Module -->
        <x-fe.panel id="rates" title="Cek Ongkir" subtitle="Estimasi cepat antar kota" variant="accent" class="landing-tool-panel">
            <form action="{{ route('home') }}" method="GET" style="margin-top: 1.5rem;" id="ongkir-form">
                <x-fe.input type="select" label="Provinsi Asal" name="origin_prov" id="origin_prov" required onchange="loadKota('origin_prov', 'origin_kota_id', {{ request('origin_kota_id') ?? 'null' }})">
                    <option value="" disabled selected>Pilih Provinsi Asal...</option>
                    @foreach($provinces as $prov)
                        <option value="{{ $prov->id }}" {{ request('origin_prov') == $prov->id ? 'selected' : '' }}>{{ $prov->name }}</option>
                    @endforeach
                </x-fe.input>

                <x-fe.input type="select" label="Kota/Kab. Asal" name="origin_kota_id" id="origin_kota_id" required>
                    <option value="">Pilih Provinsi dahulu...</option>
                </x-fe.input>

                <x-fe.input type="select" label="Provinsi Tujuan" name="destination_prov" id="destination_prov" required onchange="loadKota('destination_prov', 'destination_kota_id', {{ request('destination_kota_id') ?? 'null' }})">
                    <option value="" disabled selected>Pilih Provinsi Tujuan...</option>
                    @foreach($provinces as $prov)
                        <option value="{{ $prov->id }}" {{ request('destination_prov') == $prov->id ? 'selected' : '' }}>{{ $prov->name }}</option>
                    @endforeach
                </x-fe.input>

                <x-fe.input type="select" label="Kota/Kab. Tujuan" name="destination_kota_id" id="destination_kota_id" required>
                    <option value="">Pilih Provinsi dahulu...</option>
                </x-fe.input>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <x-fe.input type="number" label="Berat (kg)" name="weight" id="calc_weight" placeholder="1" min="0.1" step="0.1" value="{{ request('weight', 1) }}" required />
                    
                    <x-fe.input type="select" label="Layanan" name="service_type" id="calc_service">
                        <option value="REGULAR" style="background: var(--color-bg);" {{ request('service_type', 'REGULAR') === 'REGULAR' ? 'selected' : '' }}>REGULAR</option>
                        <option value="BEST" style="background: var(--color-bg);" {{ request('service_type') === 'BEST' ? 'selected' : '' }}>BEST</option>
                        <option value="KARGO" style="background: var(--color-bg);" {{ request('service_type') === 'KARGO' ? 'selected' : '' }}>KARGO</option>
                    </x-fe.input>
                </div>

                <div class="toy-estimate-box">
                    <div>
                        <span class="text-gray" style="font-size: 0.75rem;">Estimasi Total</span><br>
                        <span id="res-total-price" class="text-accent" style="font-size: 1.5rem; font-weight: bold;">
                            {{ $rateResult ? 'Rp ' . number_format($rateResult['total_price'], 0, ',', '.') : 'Rp ---' }}
                        </span>
                    </div>
                    <div id="res-etd-container" style="text-align: right; {{ $rateResult ? '' : 'display: none;' }}">
                        <span class="text-gray" style="font-size: 0.75rem;">ETD</span><br>
                        <span id="res-etd" class="text-main" style="font-size: 0.9rem; font-weight: bold;">{{ $rateResult ? ($rateResult['estimated_days'] ? $rateResult['estimated_days'].' HARI' : 'DARI KURIR') : '' }}</span>
                    </div>
                </div>
                <x-fe.button type="button" onclick="calculateOngkir()" variant="secondary" style="width: 100%;">Hitung Ongkir</x-fe.button>
                <x-fe.button :href="route('order.create', request()->only(['origin_prov', 'origin_kota_id', 'destination_prov', 'destination_kota_id', 'weight', 'service_type']))" id="continue-order-link" variant="primary" style="width: 100%; margin-top: 0.85rem; display: {{ $rateResult ? 'inline-flex' : 'none' }};">Lanjut Buat Order</x-fe.button>
            </form>
        </x-fe.panel>

        </div>
    </div>

        <!-- Services -->

    <div id="services" class="section-animate" style="margin-top: 6rem;">
        <div class="section-title">
            <h2 class="text-primary">Pilihan Layanan</h2>
            <p class="text-gray">Pilih cepat, stabil, atau hemat untuk paket berat.</p>
        </div>

        <div class="grid-3 service-rail">
            @foreach($serviceCards as $card)
                @php
                    $variant = $card->settings['variant'] ?? null;
                    $titleColor = 'text-main';
                    $ruleColor = $variant === 'primary' ? 'var(--color-primary)' : ($variant === 'accent' ? 'var(--color-gray)' : 'var(--color-accent)');
                    $panelVariant = in_array($variant, ['primary', 'accent'], true) ? $variant : null;
                    $bodyLines = preg_split('/\r\n|\r|\n/', (string) $card->body);
                @endphp
                <x-fe.panel :variant="$panelVariant" class="service-card service-card--{{ $variant ?? 'neutral' }}" style="text-align: center;">
                    <div class="service-card-title {{ $titleColor }}">{{ $card->title }}</div>
                    @if($card->subtitle)
                        <div class="text-gray mb-2" style="font-size: 0.7rem;">{{ $card->subtitle }}</div>
                    @endif
                    <div style="height: 2px; width: 50px; background-color: {{ $ruleColor }}; margin: 0 auto 1rem auto;"></div>
                    <p class="text-gray" style="font-size: 0.8rem; line-height: 1.4;">
                        @foreach($bodyLines as $line)
                            {{ $line }}@if(!$loop->last)<br>@endif
                        @endforeach
                    </p>
                </x-fe.panel>
            @endforeach
        </div>
    </div>

    @if($featurePanels->isNotEmpty())
        <div class="grid-2 section-animate" style="margin-top: 6rem; gap: 2rem;">
            @foreach($featurePanels as $panel)
                @php
                    $variant = $panel->settings['variant'] ?? 'primary';
                    $panelVariant = in_array($variant, ['primary', 'accent'], true) ? $variant : null;
                    $bodyLines = preg_split('/\r\n|\r|\n/', (string) $panel->body);
                @endphp
                <x-fe.panel :title="$panel->title" :subtitle="$panel->subtitle" :variant="$panelVariant">
                    @if($panel->body)
                        <p class="text-gray" style="font-size: 0.9rem; line-height: 1.7; margin-bottom: {{ $panel->button_text ? '1.5rem' : '0' }};">
                            @foreach($bodyLines as $line)
                                {{ $line }}@if(!$loop->last)<br>@endif
                            @endforeach
                        </p>
                    @endif
                    @if($panel->button_text && $panel->button_url)
                        <x-fe.button href="{{ $panel->button_url }}" variant="secondary">{{ $panel->button_text }}</x-fe.button>
                    @endif
                </x-fe.panel>
            @endforeach
        </div>
    @endif

    @foreach($ctas as $cta)
        <x-fe.panel :title="$cta->title" :subtitle="$cta->subtitle" variant="accent" class="section-animate" style="margin-top: 4rem; text-align: center;">
            @if($cta->body)
                <p class="text-gray" style="font-size: 0.95rem; line-height: 1.7; max-width: 720px; margin: 0 auto 1.5rem;">
                    {{ $cta->body }}
                </p>
            @endif
            @if($cta->button_text && $cta->button_url)
                <x-fe.button href="{{ $cta->button_url }}" variant="primary">{{ $cta->button_text }}</x-fe.button>
            @endif
        </x-fe.panel>
    @endforeach
        </div>
    </section>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    window.SPRINTLOG_GSAP_PAGE = true;
    document.querySelectorAll('.landing-tool-tabs').forEach((tabs) => {
        const section = tabs.closest('.landing-tools-section');
        const buttons = [...tabs.querySelectorAll('[data-tool-tab]')];
        const panels = section ? [...section.querySelectorAll('.landing-tool-panel')] : [];

        const activateTool = (targetId) => {
            const targetButton = buttons.find((item) => item.dataset.toolTab === targetId);
            if (!targetButton) {
                return;
            }

            buttons.forEach((item) => {
                const isActive = item === targetButton;
                item.classList.toggle('is-active', isActive);
                item.setAttribute('aria-selected', isActive ? 'true' : 'false');
            });
            panels.forEach((panel) => panel.classList.toggle('is-active', panel.id === targetId));
        };

        buttons.forEach((button) => {
            button.addEventListener('click', () => {
                activateTool(button.dataset.toolTab);
            });
        });

        const activateFromHash = () => activateTool(window.location.hash.replace('#', ''));
        activateFromHash();
        window.addEventListener('hashchange', activateFromHash);
    });
    document
        .querySelectorAll('.section-animate, .hud-panel, .type-stack, .route-strip__item')
        .forEach((el) => el.classList.add('is-visible'));
    return;

    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const isTouchOrSmall = window.matchMedia('(pointer: coarse), (max-width: 768px)').matches;

    if (reducedMotion || isTouchOrSmall) {
        document.querySelectorAll('.section-animate, .hud-panel').forEach((el) => el.classList.add('is-visible'));
        return;
    }

    function loadMotionScript(src) {
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = src;
            script.async = true;
            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }

    loadMotionScript('https://cdn.jsdelivr.net/npm/gsap@3.12.7/dist/gsap.min.js')
        .then(() => loadMotionScript('https://cdn.jsdelivr.net/npm/gsap@3.12.7/dist/ScrollTrigger.min.js'))
        .then(initLandingMotion)
        .catch(() => {
            document.querySelectorAll('.section-animate, .hud-panel').forEach((el) => el.classList.add('is-visible'));
        });

function initLandingMotion() {
    if (!window.gsap || !window.ScrollTrigger) {
        document.querySelectorAll('.section-animate, .hud-panel').forEach((el) => el.classList.add('is-visible'));
        return;
    }

    gsap.registerPlugin(ScrollTrigger);

    gsap.defaults({
        ease: 'power3.out',
    });

    document.querySelectorAll('.kinetic-hero .hud-panel, .grid-2.section-animate .hud-panel, #pickup.hud-panel').forEach((panel) => {
        if (!panel.querySelector(':scope > .hud-panel__gsap-scan')) {
            const scan = document.createElement('span');
            scan.className = 'hud-panel__gsap-scan';
            panel.prepend(scan);
        }
    });

    const heroTimeline = gsap.timeline({
        defaults: {
            duration: 0.78,
        },
    });

    gsap.set('.hero-title .hero-line, .hero-title .hero-subline', {
        transformOrigin: 'left center',
    });

    heroTimeline
        .from('header', {
            y: -28,
            opacity: 0,
            duration: 0.62,
        })
        .from('.hero-kicker', {
            x: -34,
            opacity: 0,
            duration: 0.52,
        }, '-=0.25')
        .from('.hero-title .hero-line', {
            clipPath: 'inset(0 100% 0 0)',
            x: -42,
            opacity: 0,
            skewX: -4,
        }, '-=0.2')
        .from('.hero-title .hero-subline', {
            clipPath: 'inset(0 0 100% 0)',
            y: 30,
            opacity: 0,
        }, '-=0.5')
        .from('.hero-subtitle', {
            y: 24,
            opacity: 0,
            duration: 0.58,
        }, '-=0.28')
        .from('.hero-actions > *', {
            y: 18,
            opacity: 0,
            stagger: 0.08,
            duration: 0.48,
        }, '-=0.25')
        .from('.type-stack__row', {
            x: 42,
            opacity: 0,
            stagger: 0.08,
            duration: 0.55,
        }, '-=0.72')
        .from('.data-marquee', {
            scaleX: 0,
            opacity: 0,
            transformOrigin: 'left center',
            duration: 0.52,
        }, '-=0.35');

    if (!isTouchOrSmall) {
        gsap.timeline({
            scrollTrigger: {
                trigger: '.kinetic-hero',
                start: 'top top',
                end: 'bottom top',
                scrub: 0.45,
            },
        })
            .to('.hero-title', {
                y: -44,
                scale: 0.97,
                duration: 1,
                ease: 'none',
            }, 0)
            .to('.hero-subtitle, .hero-actions', {
                y: -22,
                opacity: 0.55,
                duration: 1,
                ease: 'none',
            }, 0)
            .to('.type-stack', {
                y: 42,
                duration: 1,
                ease: 'none',
            }, 0);
    }

    gsap.from('.route-strip__item', {
        scrollTrigger: {
            trigger: '.route-strip',
            start: 'top 78%',
            once: true,
        },
        y: 34,
        opacity: 0,
        stagger: 0.11,
        duration: 0.62,
    });

    gsap.utils.toArray('.section-animate').forEach((section) => {
        gsap.fromTo(section,
            {
                y: 30,
                opacity: 0,
            },
            {
                y: 0,
                opacity: 1,
                duration: 0.58,
                scrollTrigger: {
                    trigger: section,
                    start: 'top 82%',
                    once: true,
                    onEnter: () => section.classList.add('is-visible'),
                },
            }
        );
    });

    gsap.utils.toArray('.kinetic-hero .hud-panel, .grid-2.section-animate .hud-panel, #pickup.hud-panel').forEach((panel) => {
        const scan = panel.querySelector(':scope > .hud-panel__gsap-scan');

        if (scan) {
            gsap.fromTo(scan,
                {
                    xPercent: -130,
                    opacity: 0,
                },
                {
                    xPercent: 130,
                    opacity: 0.8,
                    duration: 0.62,
                    ease: 'power2.inOut',
                    scrollTrigger: {
                        trigger: panel,
                        start: 'top 84%',
                        once: true,
                    },
                }
            );
        }

        gsap.fromTo(panel,
            {
                y: 18,
            },
            {
                y: 0,
                duration: 0.48,
                scrollTrigger: {
                    trigger: panel,
                    start: 'top 86%',
                    once: true,
                },
            }
        );
    });

    if (!isTouchOrSmall) {
    gsap.utils.toArray('.hero-actions a, .hero-actions button').forEach((button) => {
        button.addEventListener('mouseenter', () => {
            gsap.to(button, {
                scale: 1.045,
                rotate: -0.6,
                duration: 0.22,
                ease: 'back.out(2.4)',
            });
        });

        button.addEventListener('mouseleave', () => {
            gsap.to(button, {
                x: 0,
                y: 0,
                scale: 1,
                rotate: 0,
                duration: 0.35,
                ease: 'elastic.out(1, 0.45)',
            });
        });

        button.addEventListener('mousemove', (event) => {
            const rect = button.getBoundingClientRect();
            const x = event.clientX - rect.left - rect.width / 2;
            const y = event.clientY - rect.top - rect.height / 2;
            gsap.to(button, {
                x: x * 0.12,
                y: y * 0.18,
                duration: 0.24,
                ease: 'power2.out',
            });
        });
    });
    }

    const priceTargets = document.querySelectorAll('#res-total-price, .quote-display__value');
    priceTargets.forEach((target) => {
        const observer = new MutationObserver(() => {
            gsap.fromTo(target,
                {
                    scale: 0.94,
                    color: 'var(--color-accent)',
                },
                {
                    scale: 1,
                    color: 'var(--color-primary)',
                    duration: 0.42,
                    ease: 'back.out(2.2)',
                }
            );
        });

        observer.observe(target, {
            childList: true,
            characterData: true,
            subtree: true,
        });
    });
}
});

function loadKota(provSelectId, kotaSelectId, selectedKotaId) {
    const provId = document.getElementById(provSelectId).value;
    const kotaSelect = document.getElementById(kotaSelectId);
    
    if (!provId) {
        kotaSelect.innerHTML = '<option value="">Pilih Provinsi dahulu...</option>';
        return;
    }

    kotaSelect.innerHTML = '<option value="">Loading...</option>';

    fetch(`/api/locations/kota?provinsi_id=${provId}`)
        .then(r => r.json())
        .then(kota => {
            kotaSelect.innerHTML = '<option value="" disabled selected>Pilih Kota/Kabupaten...</option>';
            kota.forEach(k => {
                const opt = document.createElement('option');
                opt.value = k.id;
                opt.textContent = k.name;
                if (selectedKotaId && k.id == selectedKotaId) opt.selected = true;
                kotaSelect.appendChild(opt);
            });
            refreshOrderLink();
            maybeAutoCalculateQuote();
        })
        .catch(() => {
            kotaSelect.innerHTML = '<option value="">Gagal memuat kota.</option>';
        });
}

const shouldAutoQuote = @json($shouldAutoQuote);
let autoQuoteDone = false;

function maybeAutoCalculateQuote() {
    if (!shouldAutoQuote || autoQuoteDone) return;

    const params = quoteParams();
    if (params.origin_kota_id && params.destination_kota_id && params.weight) {
        autoQuoteDone = true;
        calculateOngkir();
    }
}

function quoteParams() {
    return {
        origin_prov: document.getElementById('origin_prov')?.value || '',
        origin_kota_id: document.getElementById('origin_kota_id')?.value || '',
        destination_prov: document.getElementById('destination_prov')?.value || '',
        destination_kota_id: document.getElementById('destination_kota_id')?.value || '',
        weight: document.getElementById('calc_weight')?.value || '',
        service_type: document.getElementById('calc_service')?.value || 'REGULAR',
    };
}

function refreshOrderLink(show = false) {
    const link = document.getElementById('continue-order-link');
    if (!link) return;

    const params = quoteParams();
    const complete = params.origin_prov && params.origin_kota_id && params.destination_prov && params.destination_kota_id && params.weight;
    const query = new URLSearchParams(params);
    link.href = `{{ route('order.create') }}?${query.toString()}`;
    link.style.display = show && complete ? 'inline-flex' : 'none';
}

// On page load: restore city dropdowns if province was already selected (after form submission)
document.addEventListener('DOMContentLoaded', function () {
    const originProv = document.getElementById('origin_prov');
    const destProv   = document.getElementById('destination_prov');

    if (originProv && originProv.value) {
        loadKota('origin_prov', 'origin_kota_id', {{ request('origin_kota_id') ?? 'null' }});
    }
    if (destProv && destProv.value) {
        loadKota('destination_prov', 'destination_kota_id', {{ request('destination_kota_id') ?? 'null' }});
    }

    syncPickupSource();
});

let pMap, pMarker;
const defaultLat = -6.2088; // Jakarta
const defaultLng = 106.8456;
const profilePickupAddress = @json(optional(auth()->user())->address);
const profilePickupLat = @json(optional(auth()->user())->latitude);
const profilePickupLng = @json(optional(auth()->user())->longitude);

function syncPickupSource() {
    const picked = document.querySelector('input[name="pickup_source"]:checked');
    if (!picked) return;
    const mode = picked.value;
    const addressInput = document.getElementById('hp_origin_address_display');
    const mapBtn = document.querySelector('button[onclick="toggleMap_hp_origin()"]');

    if (mode === 'profile') {
        addressInput.readOnly = true;
        addressInput.required = false;
        if (profilePickupAddress) {
            addressInput.value = profilePickupAddress;
        }
        if (profilePickupLat && profilePickupLng) {
            document.getElementById('hp_origin_lat').value = profilePickupLat;
            document.getElementById('hp_origin_lng').value = profilePickupLng;
        }
        if (mapBtn) mapBtn.disabled = true;
        document.getElementById('hp_origin-map-container').style.display = 'none';
    } else {
        addressInput.readOnly = false;
        addressInput.required = true;
        if (mapBtn) mapBtn.disabled = false;
    }
}

// Removed bloated map logic since x-fe.map-picker pushes its own logic

function calculateOngkir() {
    const originKotaId = document.getElementById('origin_kota_id').value;
    const destKotaId   = document.getElementById('destination_kota_id').value;
    const weight       = document.getElementById('calc_weight').value;
    const service      = document.getElementById('calc_service').value;

    if (!originKotaId || !destKotaId || !weight) {
        alert('Lengkapi semua data terlebih dahulu.');
        refreshOrderLink(false);
        return;
    }

    const priceSpan = document.getElementById('res-total-price');
    const etdContainer = document.getElementById('res-etd-container');
    const etdSpan = document.getElementById('res-etd');

    priceSpan.textContent = 'Rp ...';
    etdContainer.style.display = 'none';

    fetch(`/api/calculate-rate?origin_kota_id=${originKotaId}&destination_kota_id=${destKotaId}&weight=${weight}&service_type=${service}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                alert(data.error);
                priceSpan.textContent = 'Rp ---';
                refreshOrderLink(false);
                return;
            }
            priceSpan.textContent = data.total_price_fmt;
            etdSpan.textContent = data.estimated_days ? `${data.estimated_days} HARI` : 'DARI KURIR';
            etdContainer.style.display = 'block';
            refreshOrderLink(true);
        })
        .catch(err => {
            console.error(err);
            priceSpan.textContent = 'Rp ---';
            refreshOrderLink(false);
            alert('Gagal mengambil data ongkir.');
        });
}

['origin_prov', 'origin_kota_id', 'destination_prov', 'destination_kota_id', 'calc_weight', 'calc_service'].forEach((id) => {
    document.getElementById(id)?.addEventListener('change', () => refreshOrderLink(false));
    document.getElementById(id)?.addEventListener('input', () => refreshOrderLink(false));
});
</script>
@endpush

