@extends('fe.layouts.main')

@section('body_class', 'landing-glass-page')

@section('title', 'SprintLog | Expedition Logistics')

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
        (object) [
            'title' => '01 · Pickup',
            'body' => 'Kurir jemput di lokasi sesuai jadwal yang dipilih.',
            'line1' => 'KURIR',
            'line2' => 'JEMPUT',
            'sub' => 'di lokasi',
            'chip' => 'Sesuai jadwal',
        ],
        (object) [
            'title' => '02 · Intake',
            'body' => 'Hub cek paket dan pembayaran sebelum resi aktif.',
            'line1' => 'HUB',
            'line2' => 'CEK',
            'sub' => 'paket & bayar',
            'chip' => 'Kasir approve',
        ],
        (object) [
            'title' => '03 · Status paket',
            'body' => 'Lihat posisi terakhir. Update dari setiap hub.',
            'line1' => 'LIHAT',
            'line2' => 'POSISI',
            'sub' => 'terakhir',
            'chip' => 'Update tiap hub',
        ],
        (object) [
            'title' => '04 · Selesai',
            'body' => 'Paket sampai, resi selesai, masih bisa dilacak.',
            'line1' => 'PAKET',
            'line2' => 'SAMPAI',
            'sub' => 'resi selesai',
            'chip' => 'Bisa dilacak lagi',
        ],
    ]);
    $serviceCards = collect([
        (object) ['title' => 'BEST', 'subtitle' => 'Paling cepat', 'body' => "Estimasi 1 hari.\nCocok untuk paket prioritas.", 'settings' => ['variant' => 'primary']],
        (object) ['title' => 'REGULAR', 'subtitle' => 'Paling stabil', 'body' => "Estimasi 2-4 hari.\nRute harian antar kota.", 'settings' => ['variant' => 'primary']],
        (object) ['title' => 'KARGO', 'subtitle' => 'Paling hemat', 'body' => "Untuk paket berat.\nMinimum 10 kg.", 'settings' => ['variant' => 'primary']],
    ]);
    $featurePanels = collect();
    $ctas = collect();
    $shouldAutoQuote = request()->filled(['origin_prov', 'origin_kota_id', 'destination_prov', 'destination_kota_id', 'weight']);
@endphp

<div class="space-y-32 md:space-y-44 py-8 md:py-16">

    <!-- PROLOGUE: THE HERO HOOK -->
    <section class="relative min-h-[75vh] flex flex-col items-center justify-center text-center px-4 pt-12 pb-20 overflow-hidden">
        <!-- Interactive Particle Canvas -->
        <canvas id="hero-particles" class="absolute inset-0 w-full h-full pointer-events-none z-0 opacity-70"></canvas>

        <div class="max-w-4xl mx-auto flex flex-col items-center relative z-10">

            <!-- Story Badge -->
            <div data-gsap="badge" class="story-badge mb-8">
                <span class="w-2 h-2 rounded-full bg-primary animate-ping"></span>
                Logistik Lokal Masa Depan
            </div>

            <!-- Main Headline -->
            <h1 data-gsap="title" class="font-display font-black text-5xl sm:text-7xl md:text-8xl tracking-tight uppercase leading-[0.95] mb-8">
                Setiap Paket<br>
                <span class="text-gradient-primary">Punya Cerita.</span>
            </h1>

            <!-- Subtitle with Generous Spacing -->
            <p data-gsap="subtitle" class="text-slate-400 text-lg md:text-2xl font-light leading-relaxed max-w-2xl mb-12">
                {{ $heroBody }} Tanpa antrean, tanpa keraguan. Hanya pengalaman pengiriman yang presisi dan transparan.
            </p>

            <!-- Action Buttons -->
            <div data-gsap="cta" class="flex flex-col sm:flex-row items-center gap-5 w-full sm:w-auto mb-16">
                <x-fe.button href="#interactive-hub" variant="primary" class="w-full sm:w-auto px-10 py-4 text-base font-bold uppercase tracking-wider rounded-2xl shadow-[0_0_30px_rgba(166,216,0,0.25)] hover:scale-105 transition-all">
                    Mulai Lacak / Cek Ongkir
                </x-fe.button>
                <x-fe.button href="#story-journey" variant="outline" class="w-full sm:w-auto px-10 py-4 text-base font-bold uppercase tracking-wider rounded-2xl border-slate-700 hover:border-slate-500 text-slate-300">
                    Pelajari Alur
                </x-fe.button>
            </div>

            <!-- Ticker Bar -->
            <div data-gsap="ticker" class="w-full max-w-3xl overflow-hidden glass-panel border-white/5 rounded-2xl py-3 px-6 shadow-2xl">
                <div class="animate-marquee-wrapper">
                    <span class="whitespace-nowrap text-xs font-bold tracking-widest text-primary/80 uppercase mr-8 flex items-center gap-6">
                        <span><i class="bi bi-lightning-charge-fill mr-1"></i> Pickup Instant</span>
                        <span>•</span>
                        <span><i class="bi bi-geo-alt-fill mr-1"></i> Live Tracking GPS</span>
                        <span>•</span>
                        <span><i class="bi bi-shield-check mr-1"></i> Garansi Tepat Waktu</span>
                        <span>•</span>
                        <span><i class="bi bi-credit-card-2-front-fill mr-1"></i> Pembayaran Transparan</span>
                        <span>•</span>
                    </span>
                    <span class="whitespace-nowrap text-xs font-bold tracking-widest text-primary/80 uppercase mr-8 flex items-center gap-6" aria-hidden="true">
                        <span><i class="bi bi-lightning-charge-fill mr-1"></i> Pickup Instant</span>
                        <span>•</span>
                        <span><i class="bi bi-geo-alt-fill mr-1"></i> Live Tracking GPS</span>
                        <span>•</span>
                        <span><i class="bi bi-shield-check mr-1"></i> Garansi Tepat Waktu</span>
                        <span>•</span>
                        <span><i class="bi bi-credit-card-2-front-fill mr-1"></i> Pembayaran Transparan</span>
                        <span>•</span>
                    </span>
                </div>
            </div>
        </div>
    </section>

    <!-- ACT I: THE NARRATIVE PROMISE -->
    <section class="max-w-5xl mx-auto px-4">
        <div class="glass-panel border-white/5 rounded-3xl p-10 md:p-16 relative overflow-hidden">
            <div class="absolute -top-24 -right-24 w-72 h-72 bg-primary/10 rounded-full blur-3xl pointer-events-none"></div>

            <div class="grid grid-cols-1 md:grid-cols-12 gap-10 items-center">
                <div class="md:col-span-4 text-left">
                    <span class="text-xs font-bold uppercase tracking-widest text-primary block mb-3">ACT I — VISI KAMI</span>
                    <h2 class="font-display font-extrabold text-3xl md:text-4xl text-slate-100 uppercase leading-tight">
                        Mengubah Kerumitan Menjadi Kepastian.
                    </h2>
                </div>
                <div class="md:col-span-8 text-slate-300 text-base md:text-lg leading-relaxed font-light pl-0 md:pl-6 border-l-0 md:border-l border-slate-800">
                    Logistik konvensional membuat Anda bertanya-tanya kapan paket diambil dan di mana kurir berada. <strong class="font-bold text-slate-100">SprintLog</strong> diciptakan untuk menghapus semua ketidakpastian itu melalui otomatisasi pintar, penjemputan langsung di lokasi Anda, dan pelacakan <em class="italic text-primary font-medium">real-time</em>.
                </div>
            </div>
        </div>
    </section>

    <!-- ACT II: THE STORY JOURNEY (SCROLLYTELLING 4 STEPS) -->
    <section id="story-journey" class="max-w-6xl mx-auto px-4">

        <!-- Section Header -->
        <div class="text-center max-w-2xl mx-auto mb-20">
            <span class="text-xs font-bold uppercase tracking-widest text-primary block mb-3">ACT II — ALUR PERJALANAN</span>
            <h2 class="font-display font-extrabold text-4xl md:text-5xl uppercase text-slate-100">
                Bagaimana Cerita Paket Anda Dimulai
            </h2>
            <p class="text-slate-400 text-sm md:text-base mt-4 font-light">
                Empat langkah sederhana dari pintu pengirim hingga tangan penerima.
            </p>
        </div>

        <!-- 4 Step Cards with Generous Spacing -->
        <div class="space-y-12 md:space-y-16">
            @foreach($routeSteps as $index => $step)
                @php
                    $isEven = $index % 2 === 1;
                    $stepNum = sprintf('%02d', $index + 1);
                @endphp
                <div class="story-card rounded-3xl p-8 md:p-14 relative overflow-hidden">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                        <div class="lg:col-span-3 text-left">
                            <span class="story-step-number block" data-count="{{ $index + 1 }}">{{ $stepNum }}</span>
                            <span class="text-xs font-bold uppercase tracking-widest text-primary mt-2 block">{{ $step->title }}</span>
                        </div>
                        <div class="lg:col-span-9 flex flex-col justify-center text-left">
                            <h3 class="font-display font-bold text-2xl md:text-3xl text-slate-100 mb-4">
                                @if($index === 0)
                                    Penentuan Alamat & Permintaan Pickup Instant
                                @elseif($index === 1)
                                    Penjemputan Kurir Tepat Waktu
                                @elseif($index === 2)
                                    Verifikasi Hub & Pemrosesan Otomatis
                                @else
                                    Pelacakan Real-time & Konfirmasi Diterima
                                @endif
                            </h3>
                            <p class="text-slate-300 text-base md:text-lg font-light leading-relaxed">
                                {{ $step->body }}
                            </p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <!-- HORIZONTAL PINNED NARRATIVE MANIFESTO (ALTERNATIVE SLIDES) -->
    <section id="horizontal-pin-section" class="relative min-h-screen w-full flex flex-col justify-center overflow-hidden bg-[#06070a] border-y border-slate-800/80 my-20">

        <!-- Floating Guide Badge -->
        <div class="absolute top-8 left-1/2 -translate-x-1/2 text-xs font-bold uppercase tracking-widest text-primary flex items-center gap-2.5 bg-slate-900/90 border border-primary/30 rounded-full px-6 py-2.5 shadow-2xl backdrop-blur-md z-20">
            <i class="bi bi-arrow-down text-primary animate-bounce text-sm"></i>
            <span class="font-alt tracking-wider">SCROLL KE BAWAH UNTUK MANIFESTO</span>
        </div>

        <div id="horizontal-text-track" class="flex items-center gap-10 md:gap-16 pl-[12vw] pr-[25vw] py-12">

            <!-- SLIDE 1 -->
            <div class="horizontal-slide w-[75vw] md:w-[60vw] shrink-0 flex flex-col justify-center items-start p-8 md:p-14 glass-panel border-l-4 border-l-primary border-white/10 rounded-3xl shadow-2xl relative overflow-hidden">
                <div class="absolute -right-12 -bottom-12 w-64 h-64 bg-primary/10 rounded-full blur-3xl pointer-events-none"></div>
                <span class="text-xs font-bold uppercase tracking-widest text-primary font-alt mb-4">01 · Pickup</span>
                <h2 class="text-4xl sm:text-6xl md:text-7xl font-black uppercase text-slate-100 font-unbounded leading-[1.1] mb-2">
                    KURIR<br>JEMPUT
                </h2>
                <p class="text-slate-400 text-base md:text-lg font-light mb-6">di lokasi</p>
                <div data-parallax="fast" class="glass-panel border-primary/40 px-5 py-3 rounded-2xl inline-flex items-center gap-3 text-xs md:text-sm text-primary font-bold shadow-xl font-alt tracking-wider">
                    <i class="bi bi-geo-alt-fill text-base"></i>
                    <span>Sesuai jadwal</span>
                </div>
            </div>

            <!-- SLIDE 2 -->
            <div class="horizontal-slide w-[75vw] md:w-[60vw] shrink-0 flex flex-col justify-center items-start p-8 md:p-14 glass-panel border-l-4 border-l-secondary border-white/10 rounded-3xl shadow-2xl relative overflow-hidden">
                <div class="absolute -right-12 -bottom-12 w-64 h-64 bg-secondary/10 rounded-full blur-3xl pointer-events-none"></div>
                <span class="text-xs font-bold uppercase tracking-widest text-secondary font-alt mb-4">02 · Intake</span>
                <h2 class="text-4xl sm:text-6xl md:text-7xl font-black uppercase text-slate-100 font-unbounded leading-[1.1] mb-2">
                    HUB<br>CEK
                </h2>
                <p class="text-slate-400 text-base md:text-lg font-light mb-6">paket &amp; bayar</p>
                <div data-parallax="fast" class="glass-panel border-secondary/40 px-5 py-3 rounded-2xl inline-flex items-center gap-3 text-xs md:text-sm text-secondary font-bold shadow-xl font-alt tracking-wider">
                    <i class="bi bi-lightning-charge-fill text-base"></i>
                    <span>Kasir approve</span>
                </div>
            </div>

            <!-- SLIDE 3 (featured / Opsi A) -->
            <div class="horizontal-slide w-[75vw] md:w-[60vw] shrink-0 flex flex-col justify-center items-start p-8 md:p-14 glass-panel border-l-4 border-l-primary border-white/10 rounded-3xl shadow-2xl relative overflow-hidden">
                <div class="absolute -right-12 -bottom-12 w-64 h-64 bg-primary/15 rounded-full blur-3xl pointer-events-none"></div>
                <span class="text-xs font-bold uppercase tracking-widest text-primary font-alt mb-4">03 · Status paket</span>
                <h2 class="text-4xl sm:text-6xl md:text-7xl font-black uppercase text-slate-100 font-unbounded leading-[1.1] mb-2">
                    LIHAT<br><span class="text-primary">POSISI</span>
                </h2>
                <p class="text-slate-400 text-base md:text-lg font-light mb-6">terakhir</p>
                <div data-parallax="fast" class="glass-panel border-primary/40 px-5 py-3 rounded-2xl inline-flex items-center gap-3 text-xs md:text-sm text-primary font-bold shadow-xl font-alt tracking-wider">
                    <i class="bi bi-radar text-base"></i>
                    <span>Update tiap hub</span>
                </div>
            </div>

            <!-- SLIDE 4 -->
            <div class="horizontal-slide w-[75vw] md:w-[60vw] shrink-0 flex flex-col justify-center items-start p-8 md:p-14 glass-panel border-l-4 border-l-primary border-white/10 rounded-3xl shadow-2xl relative overflow-hidden">
                <div class="absolute -right-12 -bottom-12 w-64 h-64 bg-primary/10 rounded-full blur-3xl pointer-events-none"></div>
                <span class="text-xs font-bold uppercase tracking-widest text-primary font-alt mb-4">04 · Selesai</span>
                <h2 class="text-4xl sm:text-6xl md:text-7xl font-black uppercase text-slate-100 font-unbounded leading-[1.1] mb-2">
                    PAKET<br>SAMPAI
                </h2>
                <p class="text-slate-400 text-base md:text-lg font-light mb-6">resi selesai</p>
                <div data-parallax="fast" class="glass-panel border-primary/50 px-5 py-3 rounded-2xl inline-flex items-center gap-3 text-xs md:text-sm text-primary font-bold shadow-xl font-alt tracking-wider">
                    <i class="bi bi-check-circle-fill text-base"></i>
                    <span>Bisa dilacak lagi</span>
                </div>
            </div>

        </div>
    </section>

    <!-- ACT III: INTERACTIVE COMMAND HUB (CEK RESI & ONGKIR) -->
    <section id="interactive-hub" class="max-w-5xl mx-auto px-4 landing-tools-section pt-12">

        <div class="text-center max-w-2xl mx-auto mb-12">
            <span class="text-xs font-bold uppercase tracking-widest text-primary block mb-3">ACT III — COMMAND HUB</span>
            <h2 class="font-display font-extrabold text-4xl md:text-5xl uppercase text-slate-100">
                Pusat Kendali Pengiriman
            </h2>
            <p class="text-slate-400 text-sm md:text-base mt-3 font-light">
                Pantau perjalanan paket Anda atau hitung estimasi ongkos kirim dalam hitungan detik.
            </p>
        </div>

        <!-- Segmented Glass Tabs -->
        <div class="flex justify-center mb-10">
            <div class="tabs tabs-boxed bg-slate-950/60 border border-slate-800 p-1.5 rounded-2xl landing-tool-tabs shadow-2xl" role="tablist" aria-label="Pilih alat cepat">
                <button type="button" class="tab rounded-xl text-xs font-bold tracking-wider uppercase px-8 py-3 transition-all landing-tool-tab is-active flex items-center gap-2" data-tool-tab="tracer" role="tab" aria-controls="tracer" aria-selected="true">
                    <i class="bi bi-search text-sm"></i> Cek Resi Paket
                </button>
                <button type="button" class="tab rounded-xl text-xs font-bold tracking-wider uppercase px-8 py-3 transition-all landing-tool-tab flex items-center gap-2" data-tool-tab="rates" role="tab" aria-controls="rates" aria-selected="false">
                    <i class="bi bi-calculator-fill text-sm"></i> Hitung Ongkir
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-8">
            <!-- Cek Resi Form -->
            <x-fe.panel id="tracer" title="Cek Status Pengiriman" subtitle="{{ $shipment ? 'Data paket ditemukan' : 'Masukkan nomor resi SprintLog Anda' }}" variant="primary" class="landing-tool-panel is-active story-card rounded-3xl p-6 md:p-10">
                <form action="{{ route('home') }}" method="GET" class="space-y-6">
                    <x-fe.input label="Nomor Resi" name="receipt" placeholder="Contoh: SPRINT-100234" value="{{ request('receipt') }}" required />

                    @if($shipment || request('receipt'))
                        @if($shipment)
                            @php
                                $currentStatus = $shipment->status;
                                $stepIndex = 1;
                                if ($currentStatus === 'picked_up') $stepIndex = 2;
                                elseif (in_array($currentStatus, ['in_transit', 'arrived_at_branch'])) $stepIndex = 3;
                                elseif ($currentStatus === 'out_for_delivery') $stepIndex = 4;
                                elseif ($currentStatus === 'delivered') $stepIndex = 5;

                                $steps = [
                                    ['label' => 'Order', 'desc' => 'Dibuat'],
                                    ['label' => 'Pickup', 'desc' => 'Diambil'],
                                    ['label' => 'Transit', 'desc' => 'Di Hub'],
                                    ['label' => 'Kurir', 'desc' => 'Diantar'],
                                    ['label' => 'Selesai', 'desc' => 'Diterima']
                                ];
                            @endphp

                            @if($currentStatus === 'cancelled')
                                <div class="alert alert-error bg-error/10 border-error/20 text-red-400 rounded-xl p-4 mb-6 text-xs font-semibold">
                                    Pengiriman ini telah dibatalkan.
                                </div>
                            @else
                                <div class="mb-12 px-2 pt-4">
                                    <div class="relative flex items-center justify-between w-full">
                                        <div class="absolute left-0 right-0 top-1/2 -translate-y-1/2 h-0.5 bg-slate-800 -z-10"></div>

                                        @php
                                            $progressPct = (($stepIndex - 1) / 4) * 100;
                                        @endphp
                                        <div class="absolute left-0 top-1/2 -translate-y-1/2 h-0.5 bg-primary transition-all duration-500 -z-10" style="width: {{ $progressPct }}%;"></div>

                                        @foreach($steps as $idx => $step)
                                            @php
                                                $stepNum = $idx + 1;
                                                $isPassed = $stepNum < $stepIndex;
                                                $isActive = $stepNum === $stepIndex;

                                                $dotClass = $isActive
                                                    ? 'bg-primary text-slate-950 ring-4 ring-primary/20 scale-110 shadow-[0_0_15px_rgba(166,216,0,0.5)]'
                                                    : ($isPassed ? 'bg-primary text-slate-950' : 'bg-slate-900 text-slate-500 border border-slate-800');
                                            @endphp
                                            <div class="flex flex-col items-center relative z-10">
                                                <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-all duration-300 {{ $dotClass }}">
                                                    @if($isPassed)
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                        </svg>
                                                    @else
                                                        {{ $stepNum }}
                                                    @endif
                                                </div>
                                                <div class="absolute top-10 flex flex-col items-center w-24 text-center">
                                                    <span class="text-[10px] font-bold tracking-wider uppercase {{ $isActive ? 'text-primary' : ($isPassed ? 'text-slate-200' : 'text-slate-500') }}">{{ $step['label'] }}</span>
                                                    <span class="text-[8px] text-slate-400 font-medium hidden sm:inline">{{ $step['desc'] }}</span>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endif

                        <div class="bg-slate-950/60 border border-slate-800/80 rounded-2xl p-4 md:p-6 mb-4 space-y-4 max-h-[320px] overflow-y-auto custom-scrollbar">
                            @if($shipment)
                                <div class="text-secondary font-bold text-xs mb-2 flex items-center gap-2">
                                    <span>Resi {{ $shipment->tracking_number }}</span>
                                    <button type="button" onclick="copyToClipboard('{{ $shipment->tracking_number }}', this)" class="btn btn-ghost btn-circle btn-xs hover:bg-white/10 text-slate-400 hover:text-slate-200 cursor-pointer flex items-center justify-center p-0.5" title="Salin nomor resi">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                                        </svg>
                                    </button>
                                </div>
                                <div class="relative border-l-2 border-primary/30 pl-6 ml-2 space-y-6">
                                    @foreach($shipment->trackings as $log)
                                        <div class="relative">
                                            <div class="absolute -left-[31px] top-1.5 w-3 h-3 rounded-full bg-primary border-2 border-slate-950"></div>
                                            <div class="flex flex-col text-left">
                                                <span class="text-[10px] text-slate-400 font-semibold">{{ $log->tracked_at->format('H:i') }}</span>
                                                <span class="text-sm text-slate-100 font-bold mt-0.5">{{ strtoupper($log->location) }}</span>
                                                <span class="text-xs text-primary font-semibold mt-1">{{ $log->description }}</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-error font-bold text-sm">Resi belum ditemukan.</div>
                                <div class="text-slate-400 text-xs mt-1">Cek kembali nomor resi lalu coba lagi.</div>
                            @endif
                        </div>
                    @endif
                    <x-fe.button type="submit" variant="primary" class="w-full py-3 font-bold uppercase tracking-wider">Cari Status Paket</x-fe.button>
                </form>
            </x-fe.panel>

            <!-- Cek Ongkir Form -->
            <x-fe.panel id="rates" title="Estimasi Ongkos Kirim" subtitle="Cek rute dan simulasi biaya secara instan" variant="primary" class="landing-tool-panel story-card rounded-3xl p-6 md:p-10">
                <form action="{{ route('home') }}" method="GET" class="space-y-5" id="ongkir-form">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-fe.input type="select" label="Provinsi Asal" name="origin_prov" id="origin_prov" required onchange="loadKota('origin_prov', 'origin_kota_id', {{ request('origin_kota_id') ?? 'null' }})">
                            <option value="" disabled selected>Pilih Provinsi Asal...</option>
                            @foreach($provinces as $prov)
                                <option value="{{ $prov->id }}" {{ request('origin_prov') == $prov->id ? 'selected' : '' }}>{{ $prov->name }}</option>
                            @endforeach
                        </x-fe.input>

                        <x-fe.input type="select" label="Kota/Kab. Asal" name="origin_kota_id" id="origin_kota_id" required>
                            <option value="">Pilih Provinsi dahulu...</option>
                        </x-fe.input>
                    </div>

                    <!-- Swap Button Row -->
                    <div class="relative flex justify-center items-center my-2">
                        <div class="absolute inset-0 flex items-center" aria-hidden="true">
                            <div class="w-full border-t border-slate-800"></div>
                        </div>
                        <button type="button" onclick="swapOriginDestination()" class="relative z-10 btn btn-circle btn-sm bg-slate-900 border-slate-700 text-slate-300 hover:text-primary hover:border-primary transition-all duration-300 flex items-center justify-center cursor-pointer shadow-lg" title="Tukar Asal dan Tujuan">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4m13 4v12m0 0l-4-4m4 4l4-4" />
                            </svg>
                        </button>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-fe.input type="select" label="Provinsi Tujuan" name="destination_prov" id="destination_prov" required onchange="loadKota('destination_prov', 'destination_kota_id', {{ request('destination_kota_id') ?? 'null' }})">
                            <option value="" disabled selected>Pilih Provinsi Tujuan...</option>
                            @foreach($provinces as $prov)
                                <option value="{{ $prov->id }}" {{ request('destination_prov') == $prov->id ? 'selected' : '' }}>{{ $prov->name }}</option>
                            @endforeach
                        </x-fe.input>

                        <x-fe.input type="select" label="Kota/Kab. Tujuan" name="destination_kota_id" id="destination_kota_id" required>
                            <option value="">Pilih Provinsi dahulu...</option>
                        </x-fe.input>
                    </div>

                    <x-fe.input type="number" label="Berat Paket (kg)" name="weight" id="calc_weight" placeholder="1" min="0.1" step="0.1" value="{{ request('weight', 1) }}" required />

                    <!-- Segmented Service Selection Cards -->
                    <div class="form-control w-full mb-4">
                        <label class="label py-1">
                            <span class="label-text text-slate-400 font-medium text-xs uppercase tracking-wider">Kategori Layanan</span>
                        </label>
                        <div class="grid grid-cols-3 gap-3">
                            <button type="button" onclick="selectService('REGULAR')" id="btn-service-REGULAR" class="service-card-btn p-3 rounded-2xl text-left cursor-pointer flex flex-col justify-between h-20 active:scale-98">
                                <span class="font-bold text-xs tracking-wider text-slate-100">REGULAR</span>
                                <span class="text-[10px] text-slate-400 font-medium mt-1 leading-normal">Stabil (2-4 Hari)</span>
                            </button>
                            <button type="button" onclick="selectService('BEST')" id="btn-service-BEST" class="service-card-btn p-3 rounded-2xl text-left cursor-pointer flex flex-col justify-between h-20 active:scale-98">
                                <span class="font-bold text-xs tracking-wider text-slate-100">BEST</span>
                                <span class="text-[10px] text-slate-400 font-medium mt-1 leading-normal">Express (1 Hari)</span>
                            </button>
                            <button type="button" onclick="selectService('KARGO')" id="btn-service-KARGO" class="service-card-btn p-3 rounded-2xl text-left cursor-pointer flex flex-col justify-between h-20 active:scale-98">
                                <span class="font-bold text-xs tracking-wider text-slate-100">KARGO</span>
                                <span class="text-[10px] text-slate-400 font-medium mt-1 leading-normal">Hemat (Min 10kg)</span>
                            </button>
                        </div>
                        <input type="hidden" name="service_type" id="calc_service" value="{{ request('service_type', 'REGULAR') }}">
                    </div>

                    <!-- Inline Error Alert -->
                    <div id="rate-error-alert" class="alert alert-error bg-error/10 border-error/20 text-red-400 rounded-xl p-3 text-xs font-semibold" style="display: none;">
                        <span id="rate-error-text"></span>
                    </div>

                    <div id="rate-result-card" class="flex items-center justify-between bg-slate-950/60 border border-slate-800 rounded-2xl p-6 my-4 relative overflow-hidden">
                        <div id="rate-loading-overlay" class="absolute inset-0 bg-slate-950/80 rounded-2xl flex items-center justify-center transition-all duration-200" style="display: none; opacity: 0; z-index: 20;">
                            <span class="loading loading-spinner text-primary loading-md"></span>
                        </div>

                        <div class="text-left">
                            <span class="text-slate-400 text-xs font-semibold uppercase tracking-wider">Estimasi Total Biaya</span><br>
                            <span id="res-total-price" class="text-primary text-3xl font-extrabold mt-1 inline-block">
                                {{ $rateResult ? 'Rp ' . number_format($rateResult['total_price'], 0, ',', '.') : 'Rp ---' }}
                            </span>
                        </div>
                        <div id="res-etd-container" class="text-right" style="{{ $rateResult ? '' : 'display: none;' }}">
                            <span class="text-slate-400 text-xs font-semibold uppercase tracking-wider">Estimasi Tiba (ETD)</span><br>
                            <span id="res-etd" class="text-slate-100 font-bold mt-1 inline-block text-base">{{ $rateResult ? ($rateResult['estimated_days'] ? $rateResult['estimated_days'].' HARI' : 'DARI KURIR') : '' }}</span>
                        </div>
                    </div>

                    <x-fe.button :href="route('order.create', request()->only(['origin_prov', 'origin_kota_id', 'destination_prov', 'destination_kota_id', 'weight', 'service_type']))" id="continue-order-link" variant="primary" class="w-full py-4 text-base font-bold uppercase tracking-wider rounded-2xl shadow-lg" style="display: {{ $rateResult ? 'inline-flex' : 'none' }};">Lanjut Buat Order Pickup</x-fe.button>
                </form>
            </x-fe.panel>
        </div>
    </section>

    <!-- ACT IV: SERVICE TIERS -->
    <section id="services" class="max-w-6xl mx-auto px-4">
        <div class="text-center max-w-2xl mx-auto mb-16">
            <span class="text-xs font-bold uppercase tracking-widest text-primary block mb-3">ACT IV — KATEGORI PENGIRIMAN</span>
            <h2 class="font-display font-extrabold text-4xl md:text-5xl uppercase text-slate-100">
                Pilihan Pacing Untuk Setiap Kebutuhan
            </h2>
            <p class="text-slate-400 text-sm md:text-base mt-3 font-light">
                Dari kecepatan instan hingga opsi kargo super hemat.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            @foreach($serviceCards as $card)
                @php
                    $variant = $card->settings['variant'] ?? null;
                    $bodyLines = preg_split('/\r\n|\r|\n/', (string) $card->body);
                @endphp
                <div class="story-card rounded-3xl p-8 flex flex-col justify-between h-full relative overflow-hidden">
                    <div>
                        <span class="story-badge mb-6">{{ $card->subtitle ?? 'Layanan' }}</span>
                        <h3 class="font-display font-extrabold text-3xl tracking-wide uppercase text-slate-100 mb-4">{{ $card->title }}</h3>
                        <div class="h-0.5 w-12 bg-primary mb-6"></div>
                        <p class="text-slate-300 text-base leading-relaxed font-light mb-8">
                            @foreach($bodyLines as $line)
                                {{ $line }}@if(!$loop->last)<br>@endif
                            @endforeach
                        </p>
                    </div>
                    <div class="pt-4 border-t border-slate-800/60">
                        <a href="{{ route('order.create', ['service_type' => $card->title]) }}" class="inline-flex items-center text-xs font-bold uppercase tracking-widest text-primary hover:underline">
                            Pilih Layanan Ini &rarr;
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <!-- ACT V: EPILOGUE & CTA -->
    <section class="max-w-5xl mx-auto px-4">
        <div class="glass-panel-primary border-primary/20 rounded-3xl p-12 md:p-20 text-center relative overflow-hidden shadow-[0_0_60px_rgba(166,216,0,0.08)]">
            <div class="max-w-2xl mx-auto">
                <span class="story-badge mb-6">Mulai Sekarang</span>
                <h2 class="font-display font-black text-4xl sm:text-5xl uppercase leading-tight text-slate-100 mb-6">
                    Siap Memulai Cerita Pengiriman Anda?
                </h2>
                <p class="text-slate-300 text-base md:text-lg font-light leading-relaxed mb-10">
                    Jadwalkan penjemputan paket pertama Anda hari ini. Kurir profesional kami siap datang langsung ke alamat Anda.
                </p>
                <div class="flex flex-col sm:flex-row justify-center gap-4">
                    <x-fe.button href="{{ route('order.create') }}" variant="primary" class="px-10 py-4 font-bold uppercase tracking-wider text-base rounded-2xl shadow-xl">
                        Buat Order Pickup
                    </x-fe.button>
                </div>
            </div>
        </div>
    </section>

</div>

@endsection

@push('scripts')
<script>
    window.shouldAutoQuote = @json($shouldAutoQuote);
</script>
@endpush
