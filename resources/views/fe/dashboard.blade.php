@extends('fe.layouts.main')

@section('title', 'SprintLog | Customer Dashboard')

@section('content')
<div class="space-y-8">
    <x-fe.page-header title="Dashboard Pengiriman" subtitle="Halo {{ $user->name }}, kelola semua order pickup dan riwayat paket Anda di sini.">
        <x-fe.button href="{{ route('order.create') }}" variant="primary" class="btn-sm font-bold px-4 py-2 flex items-center gap-1.5 font-alt text-xs"><i class="bi bi-plus-circle-fill"></i> Buat Order Pickup</x-fe.button>
        <x-fe.button href="{{ route('profile') }}" variant="outline" class="btn-sm font-bold px-4 py-2 flex items-center gap-1.5 font-alt text-xs"><i class="bi bi-person-circle"></i> Profil</x-fe.button>
    </x-fe.page-header>

    @if($errors->any())
        <x-fe.alert tone="danger" title="Ada yang perlu dicek">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </x-fe.alert>
    @endif

    @if($summary['total_orders'] === 0)
        <x-fe.panel title="Mulai Pengiriman Pertama" subtitle="Pilih jalur yang paling cepat buat Anda" variant="primary">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 font-alt">
                <div class="glass-panel border-white/5 p-6 rounded-2xl flex flex-col justify-between text-left">
                    <div>
                        <span class="text-xs text-primary font-bold uppercase tracking-wider flex items-center gap-1.5"><i class="bi bi-box-seam-fill"></i> Langsung Order Pickup</span>
                        <p class="text-xs text-slate-300 mt-3 leading-relaxed font-light">Isi data pickup, alamat tujuan, detail berat, jenis layanan, dan upload bukti pembayaran transfer.</p>
                    </div>
                    <div class="mt-6">
                        <x-fe.button href="{{ route('order.create') }}" variant="primary" class="btn-sm font-bold flex items-center gap-1.5"><i class="bi bi-plus-lg"></i> Buat Order</x-fe.button>
                    </div>
                </div>
                <div class="glass-panel border-white/5 p-6 rounded-2xl flex flex-col justify-between text-left">
                    <div>
                        <span class="text-xs text-secondary font-bold uppercase tracking-wider flex items-center gap-1.5"><i class="bi bi-calculator-fill"></i> Cek Estimasi Dahulu</span>
                        <p class="text-xs text-slate-300 mt-3 leading-relaxed font-light">Gunakan kalkulator cek ongkir dahulu, lalu klik tombol lanjut untuk mengisi form order secara otomatis.</p>
                    </div>
                    <div class="mt-6">
                        <x-fe.button href="{{ route('home') }}#rates" variant="outline" class="btn-sm font-bold flex items-center gap-1.5"><i class="bi bi-search"></i> Cek Ongkir</x-fe.button>
                    </div>
                </div>
            </div>
        </x-fe.panel>
    @endif

    <!-- Metrics Grid -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 font-alt">
        <div class="glass-panel border-white/5 p-5 rounded-2xl flex flex-col justify-between text-left">
            <span class="text-xs text-slate-400 font-bold tracking-wide uppercase flex items-center gap-1.5"><i class="bi bi-box-seam text-slate-500"></i> Total Order</span>
            <span class="text-3xl font-unbounded font-black text-slate-100 mt-3">{{ $summary['total_orders'] }}</span>
        </div>
        <div class="glass-panel border-white/5 p-5 rounded-2xl flex flex-col justify-between text-left">
            <span class="text-xs text-slate-400 font-bold tracking-wide uppercase flex items-center gap-1.5"><i class="bi bi-hourglass-split text-secondary"></i> Menunggu Bayar</span>
            <span class="text-3xl font-unbounded font-black text-secondary mt-3">{{ $summary['waiting_payment'] }}</span>
        </div>
        <div class="glass-panel border-white/5 p-5 rounded-2xl flex flex-col justify-between text-left">
            <span class="text-xs text-slate-400 font-bold tracking-wide uppercase flex items-center gap-1.5"><i class="bi bi-truck text-primary"></i> Paket Aktif</span>
            <span class="text-3xl font-unbounded font-black text-primary mt-3">{{ $summary['active_shipments'] }}</span>
        </div>
        <div class="glass-panel border-white/5 p-5 rounded-2xl flex flex-col justify-between text-left">
            <span class="text-xs text-slate-400 font-bold tracking-wide uppercase flex items-center gap-1.5"><i class="bi bi-check-circle-fill text-slate-300"></i> Terkirim</span>
            <span class="text-3xl font-unbounded font-black text-slate-100 mt-3">{{ $summary['delivered'] }}</span>
        </div>
    </div>

    <!-- Notifications Section -->
    @if($notifications->isNotEmpty())
        <x-fe.panel title="Update Paket" subtitle="{{ $summary['unread_notifications'] }} belum dibaca" variant="primary">
            <div class="space-y-4">
                @foreach($notifications as $notification)
                    <div class="glass-panel border-white/5 p-5 rounded-2xl flex flex-col md:flex-row md:items-center md:justify-between gap-4 {{ $notification['is_unread'] ? 'border-primary/20 bg-primary/5' : '' }}">
                        <div class="text-left flex-grow">
                            <div class="flex items-center gap-2 mb-2 flex-wrap">
                                <span class="text-[10px] text-slate-400 font-semibold">{{ $notification['sent_at_label'] }}</span>
                                @if($notification['is_unread'])
                                    <span class="badge badge-accent badge-xs font-bold text-[8px] tracking-wide rounded px-1.5 py-0.5">BARU</span>
                                @endif
                            </div>
                            <h3 class="text-sm font-bold text-slate-100 leading-snug">{{ $notification['title'] }}</h3>
                            <p class="text-xs text-slate-300 mt-1 leading-relaxed">{{ $notification['message'] }}</p>
                            @if($notification['tracking_url'])
                                <a href="{{ $notification['tracking_url'] }}" class="text-primary hover:text-primary-focus transition-colors font-bold text-xs mt-2 inline-block">
                                    Lacak {{ $notification['tracking_number'] }} &rarr;
                                </a>
                            @endif
                        </div>
                        <div class="shrink-0 text-right">
                            @if($notification['is_unread'])
                                <form action="{{ route('dashboard.notifications.read', $notification['id']) }}" method="POST">
                                    @csrf
                                    <x-fe.button type="submit" variant="outline" class="btn-xs py-1.5 px-3 font-bold text-[10px]">Dibaca</x-fe.button>
                                </form>
                            @else
                                <span class="text-slate-500 text-xs font-semibold">Sudah dibaca</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </x-fe.panel>
    @endif

    <!-- Orders Lifecycle Tracking -->
    <x-fe.panel title="Perjalanan Order" subtitle="Pickup, pembayaran, dan shipment dalam satu alur" variant="accent">
        <!-- Live search bar -->
        <div class="mb-6 relative">
            <input type="text" id="order-search-input" placeholder="Cari berdasarkan No. Resi, nama penerima, kota, atau status..." class="w-full bg-slate-950/40 border border-slate-800 focus:border-primary/60 text-slate-100 placeholder-slate-500 rounded-xl px-4 py-2.5 pl-10 text-xs transition-all focus:outline-none">
            <div class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
        </div>

        <div class="space-y-6">
            @forelse($orderLifecycles as $order)
                <div class="order-lifecycle-card glass-panel border-white/5 p-6 rounded-2xl text-left space-y-6 relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-secondary/5 rounded-full blur-3xl"></div>
                    
                    <div class="flex items-center justify-between gap-4 flex-wrap pb-4 border-b border-slate-800/80">
                        <span class="font-display font-black text-xl tracking-wider {{ $order['has_shipment'] ? 'text-secondary' : 'text-primary' }} flex items-center gap-1.5">
                            <span>#{{ $order['reference'] }}</span>
                            <button type="button" onclick="copyToClipboard('{{ $order['reference'] }}', this)" class="btn btn-ghost btn-circle btn-xs hover:bg-white/10 text-slate-400 hover:text-slate-200 cursor-pointer flex items-center justify-center p-0.5" title="Salin kode booking/resi">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                                </svg>
                            </button>
                        </span>
                        <span class="badge badge-outline border-slate-700 text-slate-300 text-xs font-bold px-3 py-2.5 uppercase">{{ $order['main_status'] }}</span>
                    </div>

                    <!-- Details -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
                        <div class="space-y-4">
                            <div>
                                <span class="text-slate-400 text-xs font-bold uppercase tracking-wider">Tujuan Penerima</span>
                                <p class="text-slate-100 font-semibold mt-1">
                                    {{ $order['receiver_name'] ?: 'Penerima belum diisi' }} / {{ $order['destination'] ?: 'Tujuan belum diisi' }}
                                </p>
                            </div>
                            @if($order['pickup_address'])
                                <div>
                                    <span class="text-slate-400 text-xs font-bold uppercase tracking-wider">Alamat Pickup</span>
                                    <p class="text-slate-100 font-semibold mt-1">{{ $order['pickup_address'] }}</p>
                                </div>
                            @endif
                        </div>
                        
                        <div class="bg-slate-950/30 p-4 rounded-xl border border-slate-800/50 flex flex-col justify-between">
                            <div>
                                <span class="text-slate-400 text-xs font-bold uppercase tracking-wider">Info Pembayaran</span>
                                <div class="flex items-center gap-2 mt-1.5 flex-wrap">
                                    <span class="badge badge-sm font-semibold {{ $order['payment_status'] === 'paid' ? 'badge-success' : 'badge-warning' }}">{{ strtoupper((string) $order['payment_status'] ?: 'UNSET') }}</span>
                                    <span class="text-xs text-slate-400 uppercase font-bold">{{ $order['payment_method'] ?: 'Belum dipilih' }}</span>
                                </div>
                            </div>
                            @if($order['total_price'])
                                <div class="mt-4 border-t border-slate-800/50 pt-3">
                                    <span class="text-[10px] text-slate-400 uppercase">Tarif Pengiriman</span>
                                    <div class="text-lg font-bold text-primary mt-0.5">Rp {{ number_format((float) $order['total_price'], 0, ',', '.') }}</div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Timeline Steps -->
                    <div class="pt-4 border-t border-slate-800/80">
                        <span class="text-slate-400 text-xs font-bold uppercase tracking-wider mb-4 block">Alur Progres</span>
                        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-2">
                            @foreach($stepLabels as $key => $label)
                                <div class="glass-panel border-white/5 p-3 rounded-xl text-center flex flex-col justify-center items-center relative {{ $order['steps'][$key] ? 'border-primary/20 bg-primary/5' : '' }}">
                                    <div class="font-bold text-sm {{ $order['steps'][$key] ? 'text-primary' : 'text-slate-500' }}">
                                        {{ $order['steps'][$key] ? 'OK' : '-' }}
                                    </div>
                                    <div class="text-slate-400 text-[10px] mt-1 font-medium">{{ $label }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Tracking Footer info -->
                    <div class="pt-4 border-t border-slate-800/80 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="text-left">
                            @if($order['latest_tracking'])
                                <span class="text-slate-400 text-xs font-bold uppercase tracking-wider">Tracking Terakhir</span>
                                <p class="text-xs text-slate-100 mt-1">
                                    <span class="font-bold text-primary">{{ strtoupper($order['latest_tracking']->status) }}</span> / {{ $order['latest_tracking']->location }} / <span class="text-slate-400 font-medium">{{ $order['latest_tracking']->tracked_at->format('d M H:i') }}</span>
                                </p>
                            @else
                                <span class="text-slate-400 text-xs font-bold uppercase tracking-wider">Update Terakhir</span>
                                <p class="text-xs text-slate-300 mt-1">
                                    {{ $order['has_shipment'] ? 'Shipment aktif, menunggu update tracking berikutnya.' : 'Hub akan mengaktifkan shipment setelah pickup dan payment diverifikasi.' }}
                                </p>
                            @endif
                        </div>

                        <div class="shrink-0 text-right">
                            @if($order['has_shipment'])
                                <x-fe.button href="{{ route('track.show') }}?receipt={{ urlencode($order['reference']) }}" variant="outline" class="btn-sm text-xs px-4">Lacak Paket</x-fe.button>
                            @else
                                <span class="text-slate-500 text-xs font-medium">Nomor resi belum aktif</span>
                            @endif
                        </div>
                    </div>

                    <!-- Next Action suggestion -->
                    <div class="bg-slate-950/40 p-4 rounded-xl border border-slate-800/80 text-left">
                        <span class="text-primary font-bold text-xs uppercase tracking-wider">Langkah Berikutnya</span>
                        <div class="text-slate-200 text-sm mt-1 leading-relaxed">{{ $order['next_action'] }}</div>
                    </div>

                    <!-- Customer Action Forms -->
                    @if($order['can_reschedule'] || $order['can_cancel'] || $order['can_reupload_payment'])
                        <div class="border-t border-slate-800/80 pt-6 space-y-4">
                            <span class="text-slate-400 text-xs font-bold uppercase tracking-wider block">Aksi Pelanggan</span>

                            @if($order['can_reupload_payment'])
                                <form action="{{ route('order.payment-proof', $order['id']) }}" method="POST" enctype="multipart/form-data" class="flex flex-col sm:flex-row items-start sm:items-center gap-3 bg-slate-950/20 border border-slate-800/60 p-4 rounded-xl">
                                    @csrf
                                    <label class="text-xs text-slate-400 font-semibold sm:w-40 shrink-0" for="payment_proof_{{ $order['id'] }}">Upload ulang bukti transfer</label>
                                    <input type="file" id="payment_proof_{{ $order['id'] }}" name="payment_proof" accept="image/*" required class="file-input file-input-bordered file-input-sm bg-slate-900/50 border-slate-800 text-xs rounded-xl focus:outline-none w-full sm:w-auto text-slate-100">
                                    <x-fe.button type="submit" variant="primary" class="btn-sm py-1.5 px-4 font-bold text-xs w-full sm:w-auto">Kirim Review</x-fe.button>
                                </form>
                            @endif

                            @if($order['can_reschedule'])
                                <form action="{{ route('order.reschedule', $order['id']) }}" method="POST" class="flex flex-col sm:flex-row items-start sm:items-center gap-3 bg-slate-950/20 border border-slate-800/60 p-4 rounded-xl">
                                    @csrf
                                    <label class="text-xs text-slate-400 font-semibold sm:w-40 shrink-0" for="pickup_date_{{ $order['id'] }}">Jadwal ulang pickup</label>
                                    <input type="date" id="pickup_date_{{ $order['id'] }}" name="pickup_date" value="{{ $order['pickup_date'] }}" min="{{ now()->format('Y-m-d') }}" required class="input input-bordered input-sm bg-slate-900/50 border-slate-800 text-xs rounded-xl focus:outline-none w-full sm:w-auto text-slate-100">
                                    <x-fe.button type="submit" variant="primary" class="btn-sm py-1.5 px-4 font-bold text-xs w-full sm:w-auto">Update Tanggal</x-fe.button>
                                </form>
                            @endif

                            @if($order['can_cancel'])
                                <form action="{{ route('order.cancel', $order['id']) }}" method="POST" onsubmit="return confirm('Cancel this pickup request before courier assignment?');" class="flex flex-col sm:flex-row items-start sm:items-center gap-3 bg-slate-950/20 border border-slate-800/60 p-4 rounded-xl">
                                    @csrf
                                    <x-fe.button type="submit" variant="outline" class="btn-sm py-1.5 px-4 font-bold text-xs text-red-400 border-red-500/20 hover:bg-red-500/10 hover:border-red-500 w-full sm:w-auto">Batalkan Request</x-fe.button>
                                    <span class="text-[10px] text-slate-500 font-semibold">Bisa dibatalkan sebelum ada kurir yang ditugaskan.</span>
                                </form>
                            @endif
                        </div>
                    @endif
                </div>
            @empty
                <div class="text-center py-12 glass-panel border-white/5 rounded-2xl">
                    <p class="text-slate-400 text-sm leading-relaxed">Belum ada pengiriman aktif.</p>
                    <p class="text-slate-500 text-xs mt-1 leading-relaxed">Buat pickup pertama, lalu progres pembayaran dan pengiriman akan tampil di sini.</p>
                    <div class="mt-6">
                        <x-fe.button href="{{ route('order.create') }}" variant="primary" class="btn-sm">Buat Order Pertama</x-fe.button>
                    </div>
                </div>
            @endforelse
        </div>
    </x-fe.panel>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('order-search-input');
    if (searchInput) {
        searchInput.addEventListener('input', function (e) {
            const query = e.target.value.toLowerCase().trim();
            document.querySelectorAll('.order-lifecycle-card').forEach(card => {
                const text = card.textContent.toLowerCase();
                if (text.includes(query)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    }
});
</script>
@endpush
