@extends('fe.layouts.main')

@push('head_assets')
    <style>
        @media print {
            /* Hide the entire website content */
            body * {
                visibility: hidden;
            }
            /* Show only the raw thermal receipt container */
            #thermal-receipt, #thermal-receipt * {
                visibility: visible;
            }
            #thermal-receipt {
                position: absolute;
                left: 0;
                top: 0;
                width: 80mm;
                padding: 0;
                margin: 0;
                display: block !important;
                color: #000000 !important;
                background: #ffffff !important;
            }
            /* Force plain black text on white backgrounds */
            body {
                background: #ffffff !important;
                color: #000000 !important;
            }
        }
    </style>
@endpush

@section('content')
<div class="space-y-8">
    <x-fe.page-header title="Request Diterima" subtitle="Pickup sudah masuk antrean hub.">
        <x-fe.button href="{{ route('dashboard') }}" variant="primary" class="btn-sm font-bold px-4 no-print">Dashboard</x-fe.button>
        <x-fe.button href="{{ route('order.create') }}" variant="outline" class="btn-sm font-bold px-4 no-print">Buat Lagi</x-fe.button>
        <x-fe.button type="button" onclick="window.print()" variant="outline" class="btn-sm font-bold px-4 flex items-center gap-1.5 no-print">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h8z" />
            </svg>
            <span>Cetak Struk</span>
        </x-fe.button>
    </x-fe.page-header>

    @if(session('success'))
        <x-fe.alert tone="success" title="Request diterima">
            <p>{{ session('success') }}</p>
        </x-fe.alert>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
        <!-- Request Summary -->
        <x-fe.panel title="Ringkasan Request" subtitle="Simpan kode ini sampai shipment aktif" variant="primary" class="text-left">
            <div class="bg-slate-950/40 border border-slate-800/80 rounded-xl p-5 text-center font-display font-black text-2xl tracking-widest text-primary my-4 select-all flex items-center justify-center gap-2">
                <span>REQ_UNIT_{{ str_pad($pickup->id, 5, '0', STR_PAD_LEFT) }}</span>
                <button type="button" onclick="copyToClipboard('REQ_UNIT_{{ str_pad($pickup->id, 5, '0', STR_PAD_LEFT) }}', this)" class="btn btn-ghost btn-circle btn-xs hover:bg-white/10 text-slate-400 hover:text-slate-200 cursor-pointer flex items-center justify-center p-0.5 no-print" title="Salin kode request">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                    </svg>
                </button>
            </div>

            <div class="space-y-3 mt-6">
                <div class="flex justify-between text-xs pb-2.5 border-b border-dashed border-slate-800/50 text-slate-400"><span>Tanggal Pickup</span><strong class="text-slate-200 font-semibold">{{ $pickup->pickup_date }}</strong></div>
                <div class="flex justify-between text-xs pb-2.5 border-b border-dashed border-slate-800/50 text-slate-400"><span>Pengirim</span><strong class="text-slate-200 font-semibold">{{ $pickup->sender_name ?: $pickup->customer_name }}</strong></div>
                <div class="flex justify-between text-xs pb-2.5 border-b border-dashed border-slate-800/50 text-slate-400"><span>Kota Asal</span><strong class="text-slate-200 font-semibold">{{ $pickup->senderCity?->name ?: '-' }}</strong></div>
                <div class="flex justify-between text-xs pb-2.5 border-b border-dashed border-slate-800/50 text-slate-400"><span>Hub Pickup</span><strong class="text-slate-200 font-semibold">{{ $pickup->branch?->name ?: '-' }}</strong></div>
                <div class="flex justify-between text-xs pb-2.5 border-b border-dashed border-slate-800/50 text-slate-400"><span>Penerima</span><strong class="text-slate-200 font-semibold">{{ $pickup->receiver_name ?: '-' }}</strong></div>
                <div class="flex justify-between text-xs pb-2.5 border-b border-dashed border-slate-800/50 text-slate-400"><span>Kota Tujuan</span><strong class="text-slate-200 font-semibold">{{ $pickup->receiverCity?->name ?: '-' }}</strong></div>
                <div class="flex justify-between text-xs pb-2.5 border-b border-dashed border-slate-800/50 text-slate-400"><span>Layanan</span><strong class="text-slate-200 font-semibold">{{ strtoupper((string) $pickup->service_type) }}</strong></div>
                <div class="flex justify-between text-xs pb-2.5 border-b border-dashed border-slate-800/50 text-slate-400"><span>Pembayaran</span><strong class="text-slate-200 font-semibold">{{ strtoupper((string) $pickup->payment_method) }}</strong></div>
                
                <div class="bg-slate-950/30 p-4 border border-slate-800/85 rounded-xl flex justify-between items-center mt-6">
                    <span class="text-slate-400 text-xs font-semibold">Estimasi Total</span>
                    <div class="text-primary text-xl font-bold">Rp {{ number_format((float) $pickup->total_price, 0, ',', '.') }}</div>
                </div>
            </div>
        </x-fe.panel>

        <!-- Timeline steps -->
        <x-fe.panel title="Langkah Berikutnya" subtitle="Alur setelah request dibuat" variant="accent" class="text-left">
            <div class="space-y-3">
                <div class="glass-panel border-white/5 p-4 rounded-xl text-left relative border-l-4 border-l-primary bg-primary/5">
                    <strong class="text-xs text-slate-100 block">01 / Request Masuk</strong>
                    <span class="text-slate-400 text-xs mt-1 block">Hub menerima request dan akan menugaskan kurir.</span>
                </div>
                <div class="glass-panel border-white/5 p-4 rounded-xl text-left relative border-l-4 border-l-secondary">
                    <strong class="text-xs text-slate-200 block">02 / Pickup Kurir</strong>
                    <span class="text-slate-400 text-xs mt-1 block">Kurir mengambil paket sesuai jadwal. Untuk tunai, bayar ke kurir saat pickup.</span>
                </div>
                <div class="glass-panel border-white/5 p-4 rounded-xl text-left relative border-l-4 border-l-accent">
                    <strong class="text-xs text-slate-200 block">03 / Pembayaran Beres</strong>
                    <span class="text-slate-400 text-xs mt-1 block">Kasir hub memverifikasi transfer bank atau uang setoran kurir.</span>
                </div>
                <div class="glass-panel border-white/5 p-4 rounded-xl text-left relative border-l-4 border-l-primary">
                    <strong class="text-xs text-slate-200 block">04 / Shipment Aktif</strong>
                    <span class="text-slate-400 text-xs mt-1 block">Nomor resi SPRINT akan aktif di dashboard setelah pembayaran sukses.</span>
                </div>
            </div>

            <div class="bg-slate-950/40 p-4 border border-slate-800/80 rounded-xl text-left mt-6">
                <span class="text-secondary font-bold text-xs uppercase tracking-wider block">Yang Perlu Anda Lakukan</span>
                <div class="text-slate-300 text-xs mt-2 leading-relaxed">
                    @if($pickup->payment_method === 'cash_on_pickup')
                        Siapkan uang tunai sejumlah estimasi di atas. Kurir akan melaporkan uang diterima, lalu kasir hub memverifikasi setoran.
                    @else
                        Bukti transfer sudah masuk antrean review kasir hub. Jika ditolak, dashboard akan membuka tombol upload ulang.
                    @endif
                </div>
            </div>
        </x-fe.panel>
    </div>
</div>

<!-- Dedicated Raw Thermal Receipt (Printed Only) -->
<div id="thermal-receipt" class="hidden font-mono text-[10px] leading-relaxed max-w-[80mm] mx-auto p-4 bg-white text-black border border-black/10">
    <div class="text-center font-bold text-sm mb-0.5">SPRINTLOG</div>
    <div class="text-center text-[8px] uppercase mb-4">EXPEDITION LOGISTICS</div>
    <div>--------------------------------</div>
    <div class="flex justify-between">
        <span>BOOKING:</span>
        <span class="font-bold">REQ_UNIT_{{ str_pad($pickup->id, 5, '0', STR_PAD_LEFT) }}</span>
    </div>
    <div class="flex justify-between">
        <span>TANGGAL:</span>
        <span>{{ $pickup->pickup_date }}</span>
    </div>
    <div>--------------------------------</div>
    <div class="flex justify-between">
        <span>PENGIRIM:</span>
        <span class="text-right truncate max-w-[150px]">{{ $pickup->sender_name ?: $pickup->customer_name }}</span>
    </div>
    <div class="flex justify-between">
        <span>KOTA ASAL:</span>
        <span>{{ $pickup->senderCity?->name ?: '-' }}</span>
    </div>
    <div class="flex justify-between">
        <span>HUB ASAL:</span>
        <span class="text-right truncate max-w-[150px]">{{ $pickup->branch?->name ?: '-' }}</span>
    </div>
    <div>--------------------------------</div>
    <div class="flex justify-between">
        <span>PENERIMA:</span>
        <span class="text-right truncate max-w-[150px]">{{ $pickup->receiver_name ?: '-' }}</span>
    </div>
    <div class="flex justify-between">
        <span>TUJUAN:</span>
        <span>{{ $pickup->receiverCity?->name ?: '-' }}</span>
    </div>
    <div class="flex justify-between">
        <span>LAYANAN:</span>
        <span>{{ strtoupper((string) $pickup->service_type) }}</span>
    </div>
    <div class="flex justify-between">
        <span>BAYAR:</span>
        <span>{{ strtoupper((string) $pickup->payment_method) }}</span>
    </div>
    <div>--------------------------------</div>
    <div class="flex justify-between font-bold text-xs">
        <span>TOTAL TARIF:</span>
        <span>Rp {{ number_format((float) $pickup->total_price, 0, ',', '.') }}</span>
    </div>
    <div>--------------------------------</div>
    <div class="text-center text-[8px] mt-4 font-semibold">TERIMA KASIH TELAH MENGGUNAKAN</div>
    <div class="text-center font-bold text-[10px]">SPRINTLOG</div>
    <div>--------------------------------</div>
</div>
@endsection
