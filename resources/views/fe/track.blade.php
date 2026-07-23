@extends('fe.layouts.main')

@section('title', 'SprintLog | Public Tracking')

@section('content')
    <div class="max-w-4xl mx-auto space-y-8">
        <x-fe.page-header title="Tracking Paket" subtitle="Pantau perjalanan paket dari hub asal sampai alamat penerima.">
            <x-fe.button href="{{ route('home') }}" variant="outline" class="btn-sm font-bold px-4 flex items-center gap-1.5 font-alt"><i class="bi bi-house"></i> Beranda</x-fe.button>
        </x-fe.page-header>

        <x-fe.panel title="Cek Status Resi" subtitle="{{ $shipment ? 'Data paket ditemukan' : 'Masukkan nomor resi SprintLog Anda' }}" variant="primary">
            <form action="{{ route('track.show') }}" method="GET" class="space-y-4 font-alt">
                <x-fe.input
                    label="Nomor Resi"
                    name="receipt"
                    placeholder="Contoh: SPRINT-100234"
                    value="{{ request('receipt') }}"
                    required
                />

                <x-fe.button type="submit" variant="primary" class="w-full font-bold uppercase tracking-wider py-3 flex items-center justify-center gap-2">
                    <i class="bi bi-search"></i> Cek Resi Paket
                </x-fe.button>
            </form>

            @if($shipment && $trackingFlow)
                <!-- Shipment Summary -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-8 pt-6 border-t border-slate-800/80">
                    <div class="space-y-4 text-left font-alt">
                        <span class="text-slate-400 text-xs font-bold uppercase tracking-wider block">Nomor Resi</span>
                        <div class="flex items-center gap-2">
                            <h3 class="text-2xl md:text-3xl font-unbounded font-black text-secondary tracking-tight">{{ $shipment->tracking_number }}</h3>
                            <button type="button" onclick="copyToClipboard('{{ $shipment->tracking_number }}', this)" class="btn btn-ghost btn-circle btn-sm hover:bg-white/10 text-slate-400 hover:text-slate-200 cursor-pointer flex items-center justify-center p-0.5" title="Salin nomor resi">
                                <i class="bi bi-clipboard text-base"></i>
                            </button>
                        </div>
                        
                        <div class="flex items-center gap-3 mt-4 flex-wrap">
                            <div class="glass-panel border-white/5 px-4 py-2 rounded-xl text-left">
                                <span class="text-[9px] text-slate-500 font-bold uppercase tracking-wide block">Asal</span>
                                <div class="text-slate-200 text-xs font-bold mt-0.5 flex items-center gap-1.5"><i class="bi bi-geo-alt-fill text-primary"></i> {{ optional($shipment->originBranch)->name ?? '-' }}</div>
                            </div>
                            <span class="text-primary font-bold text-sm">&rarr;</span>
                            <div class="glass-panel border-white/5 px-4 py-2 rounded-xl text-left">
                                <span class="text-[9px] text-slate-500 font-bold uppercase tracking-wide block">Tujuan</span>
                                <div class="text-slate-200 text-xs font-bold mt-0.5 flex items-center gap-1.5"><i class="bi bi-geo-alt-fill text-secondary"></i> {{ optional($shipment->destinationBranch)->name ?? '-' }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="glass-panel border-white/5 p-5 rounded-2xl text-left border-l-4 border-primary bg-primary/5 font-alt">
                        <span class="text-slate-400 text-xs font-bold uppercase tracking-wider">Status Sekarang</span>
                        <h4 class="text-lg font-bold text-slate-100 mt-1.5 flex items-center gap-2"><i class="bi bi-clock-history text-primary"></i> {{ strtoupper($trackingFlow['status_label']) }}</h4>
                        <p class="text-slate-300 text-xs mt-2 leading-relaxed font-light">{{ $trackingFlow['status_message'] }}</p>
                        @if($trackingFlow['latest_tracking'])
                            <p class="text-primary text-[10px] font-bold mt-3 uppercase tracking-wider flex items-center gap-1">
                                <i class="bi bi-geo"></i> Last scan: {{ $trackingFlow['latest_tracking']->location }} / {{ optional($trackingFlow['latest_tracking']->tracked_at)->format('d M Y H:i') }}
                            </p>
                        @endif
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="mt-8 text-left">
                    <span class="text-slate-400 text-xs font-bold uppercase tracking-wider block mb-3">Progres Pengiriman</span>
                    <div class="w-full bg-slate-950/40 border border-slate-800/80 rounded-full h-2.5 relative overflow-hidden mb-6">
                        <div class="bg-gradient-to-r from-primary to-secondary h-full rounded-full transition-all duration-500" style="width: {{ $trackingFlow['progress'] }}%"></div>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-6 gap-3">
                        @foreach($trackingFlow['steps'] as $step)
                            <div class="glass-panel border-white/5 p-4 rounded-xl text-center relative flex flex-col justify-center items-center gap-2 {{ $step['is_current'] ? 'border-secondary/30 bg-secondary/5' : ($step['is_done'] ? 'border-primary/20 bg-primary/5' : '') }}">
                                <div class="w-3.5 h-3.5 rounded-full border-2 {{ $step['is_current'] ? 'bg-secondary border-secondary ring-4 ring-secondary/20' : ($step['is_done'] ? 'bg-primary border-primary ring-4 ring-primary/10' : 'bg-slate-900 border-slate-700') }}"></div>
                                <span class="text-[10px] font-bold leading-tight mt-1 {{ $step['is_current'] ? 'text-secondary' : ($step['is_done'] ? 'text-primary' : 'text-slate-400') }}">{{ $step['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Route Legs -->
                @if($shipment->legs->isNotEmpty())
                    <div class="mt-10 text-left">
                        <span class="text-slate-400 text-xs font-bold uppercase tracking-wider block mb-4">Rute Rencana</span>
                        <div class="space-y-3">
                            @foreach($shipment->legs as $leg)
                                <div class="glass-panel border-white/5 p-4 rounded-xl flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                                    <div class="flex items-center gap-3">
                                        <span class="badge badge-sm badge-neutral font-bold tracking-wider">LEG {{ str_pad($leg->sequence, 2, '0', STR_PAD_LEFT) }}</span>
                                        <span class="font-bold text-slate-200 uppercase">{{ optional($leg->originBranch)->name ?? '-' }} &rarr; {{ optional($leg->destinationBranch)->name ?? '-' }}</span>
                                    </div>
                                    <div class="text-slate-400 font-medium">
                                        @if($leg->arrived_at)
                                            Tiba: {{ $leg->arrived_at->format('d M Y H:i') }}.
                                        @elseif($leg->departed_at)
                                            Berangkat: {{ $leg->departed_at->format('d M Y H:i') }}.
                                        @else
                                            Menunggu scan keberangkatan.
                                        @endif
                                        @if($leg->planned_arrival_at)
                                            | ETA: {{ $leg->planned_arrival_at->format('d M Y H:i') }}.
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Scan Timeline -->
                <div class="mt-10 text-left pt-6 border-t border-slate-800/80">
                    <span class="text-slate-400 text-xs font-bold uppercase tracking-wider block mb-6">Timeline Scan</span>
                    <div class="relative border-l border-slate-800/80 ml-3 pl-6 space-y-6">
                        @forelse($shipment->trackings->sortByDesc('tracked_at') as $log)
                            @php
                                $meta = [
                                    'pending' => 'Paket terdaftar',
                                    'picked_up' => 'Diproses hub asal',
                                    'in_transit' => 'Dalam perjalanan',
                                    'arrived_at_branch' => 'Tiba di hub',
                                    'out_for_delivery' => 'Diantar kurir',
                                    'delivered' => 'Terkirim',
                                    'delivery_failed' => 'Pengantaran tertunda',
                                    'rescheduled' => 'Dijadwalkan ulang',
                                    'returned_to_hub' => 'Kembali ke hub',
                                    'held' => 'Ditahan sementara',
                                    'damaged' => 'Perlu pemeriksaan',
                                    'lost' => 'Dalam investigasi',
                                    'exception' => 'Ada kendala rute',
                                    'cancelled' => 'Dibatalkan',
                                ][$log->status] ?? strtoupper($log->status);
                            @endphp
                            <div class="relative">
                                <!-- Timeline Dot -->
                                <div class="absolute -left-[30px] top-1.5 w-2 h-2 rounded-full bg-primary ring-4 ring-[#070a13]"></div>
                                
                                <div class="grid grid-cols-1 sm:grid-cols-4 gap-2 text-xs">
                                    <div class="text-slate-400 font-semibold">
                                        {{ optional($log->tracked_at)->format('d M Y') ?? '-' }}<br>
                                        <span class="text-[10px] text-slate-500 font-medium">{{ optional($log->tracked_at)->format('H:i') ?? '' }}</span>
                                    </div>
                                    <div class="sm:col-span-3 text-left">
                                        <h5 class="font-bold text-slate-100 uppercase tracking-wide">{{ $meta }} <span class="text-slate-500 font-normal">at</span> {{ $log->location }}</h5>
                                        <p class="text-slate-400 mt-1 leading-relaxed">{{ $log->description ?: 'Scan tercatat oleh hub.' }}</p>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-slate-500 text-xs font-semibold">Belum ada event tracking.</p>
                        @endforelse
                    </div>
                </div>
            @elseif(request('receipt'))
                <!-- Error not found resi -->
                <div class="mt-6">
                    <x-fe.alert tone="danger" title="Resi belum ditemukan">
                        Nomor resi belum aktif atau belum terdaftar.
                    </x-fe.alert>
                    
                    <div class="bg-red-500/5 border border-red-500/10 p-5 rounded-2xl text-left mt-4 space-y-4">
                        <span class="text-red-400 font-bold text-xs uppercase tracking-wider block">Penjelasan</span>
                        <div class="text-slate-300 text-xs leading-relaxed">
                            Nomor SPRINT baru bisa dilacak setelah hub menerima paket, memverifikasi pembayaran, dan mengaktifkan shipment. Jika Anda baru melakukan request order/pickup, cek dashboard pelanggan untuk memantau status persetujuan request.
                        </div>
                        @auth
                            <div>
                                <x-fe.button href="{{ route('dashboard') }}" variant="outline" class="btn-xs py-1.5 px-4 font-bold border-red-500/20 text-red-400 hover:bg-red-500/10 hover:border-red-500">Cek Dashboard</x-fe.button>
                            </div>
                        @endauth
                    </div>
                </div>
            @else
                <!-- Empty Search tip -->
                <div class="bg-slate-950/30 border border-slate-800/80 p-5 rounded-2xl text-left mt-6">
                    <span class="text-primary font-bold text-xs uppercase tracking-wider block">Tips Tracking</span>
                    <div class="text-slate-300 text-xs leading-relaxed mt-2">
                        Gunakan nomor resi unik yang diawali dengan kata SPRINT. Untuk layanan pickup pengiriman, nomor resi ini akan aktif setelah barang dijemput oleh kurir, diantarkan ke hub terkait, dan status verifikasi pembayaran dikonfirmasi.
                    </div>
                </div>
            @endif
        </x-fe.panel>
    </div>
@endsection
