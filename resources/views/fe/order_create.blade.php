@extends('fe.layouts.main')

@section('title', 'SprintLog | Buat Order')

@push('head_assets')
    <link rel="preconnect" href="https://unpkg.com">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    <style>
        [data-payment-card].is-active {
            border-color: #a6d800 !important;
            background-color: rgba(166, 216, 0, 0.06) !important;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.75rem;
            color: #94a3b8;
            padding-bottom: 0.5rem;
            border-bottom: 1px dashed rgba(255, 255, 255, 0.05);
        }
        .summary-row strong {
            color: #f1f5f9;
        }
        /* Loading pulse */
        .loading-pulse {
            animation: pulse 1.5s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }
    </style>
@endpush

@section('content')
@php
    $prefillOriginProv = old('s_prov', request('origin_prov'));
    $prefillOriginCity = old('sender_city_id', request('origin_kota_id'));
    $prefillDestinationProv = old('r_prov', request('destination_prov'));
    $prefillDestinationCity = old('receiver_city_id', request('destination_kota_id'));
    $prefillWeight = old('weight', request('weight', '1.0'));
    $prefillService = old('service_type', request('service_type', 'REGULAR'));
@endphp

<div class="space-y-8">
    <x-fe.page-header title="Buat Order" subtitle="Isi pickup, tujuan, paket, dan pembayaran dalam satu alur.">
        <x-fe.button href="{{ route('dashboard') }}" variant="outline" class="btn-sm font-bold px-4">Dashboard</x-fe.button>
    </x-fe.page-header>

    @if ($errors->any())
        <x-fe.alert tone="danger" title="Data belum lengkap">
            Mohon cek kembali field yang wajib diisi.
        </x-fe.alert>
    @endif

    <form action="{{ route('order.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        
        <!-- Flow steps -->
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-8">
            <div class="glass-panel border-white/5 p-4 rounded-xl text-center relative border-l-4 border-l-primary bg-primary/5">
                <span class="text-[10px] text-primary font-bold uppercase tracking-wider">01 / Pengirim</span>
                <div class="text-slate-200 text-xs font-semibold mt-1">Identitas & asal</div>
            </div>
            <div class="glass-panel border-white/5 p-4 rounded-xl text-center relative border-l-4 border-l-secondary bg-secondary/5">
                <span class="text-[10px] text-secondary font-bold uppercase tracking-wider">02 / Penerima</span>
                <div class="text-slate-200 text-xs font-semibold mt-1">Kontak & tujuan</div>
            </div>
            <div class="glass-panel border-white/5 p-4 rounded-xl text-center relative border-l-4 border-l-accent bg-accent/5">
                <span class="text-[10px] text-accent font-bold uppercase tracking-wider">03 / Paket</span>
                <div class="text-slate-200 text-xs font-semibold mt-1">Berat & layanan</div>
            </div>
            <div class="glass-panel border-white/5 p-4 rounded-xl text-center relative border-l-4 border-l-primary bg-primary/5">
                <span class="text-[10px] text-primary font-bold uppercase tracking-wider">04 / Bayar</span>
                <div class="text-slate-200 text-xs font-semibold mt-1">Metode bayar</div>
            </div>
            <div class="glass-panel border-white/5 p-4 rounded-xl text-center relative border-l-4 border-l-secondary bg-secondary/5 col-span-2 sm:col-span-1">
                <span class="text-[10px] text-secondary font-bold uppercase tracking-wider">05 / Review</span>
                <div class="text-slate-200 text-xs font-semibold mt-1">Cek order</div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            <!-- Form body columns -->
            <div class="lg:col-span-8 space-y-8">
                
                <!-- SENDER & RECEIVER -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- SENDER PANEL -->
                    <x-fe.panel title="Pengirim" variant="primary" class="h-full">
                        <div class="flex gap-2 mb-6 text-left">
                            <x-fe.button type="button" id="btn-use-profile" variant="primary" class="btn-xs py-2 px-3 font-bold text-[10px]">Pakai Profil</x-fe.button>
                            <x-fe.button type="button" id="btn-manual-sender" variant="outline" class="btn-xs py-2 px-3 font-bold text-[10px]">Input Manual</x-fe.button>
                        </div>

                        <div id="sender-fields" class="text-left space-y-2">
                            <x-fe.input label="Nama Pengirim" name="sender_name" id="s_name" required value="{{ old('sender_name', $user->name) }}" />
                            <x-fe.input label="Telepon Pengirim" name="sender_phone" id="s_phone" required value="{{ old('sender_phone', $user->phone) }}" />

                            <x-fe.input type="select" label="Provinsi Pengirim" name="s_prov" id="s_prov" onchange="loadKota('s_prov', 's_city_id', '{{ $prefillOriginCity }}')" required>
                                <option value="" disabled {{ $prefillOriginProv ? '' : 'selected' }}>Pilih Provinsi...</option>
                                @foreach($provinces as $prov)
                                    <option value="{{ $prov->id }}" {{ (string) $prefillOriginProv === (string) $prov->id ? 'selected' : '' }}>{{ $prov->name }}</option>
                                @endforeach
                            </x-fe.input>

                            <x-fe.input type="select" label="Kota/Kab. Pengirim" name="sender_city_id" id="s_city_id" required>
                                <option value="">Pilih Provinsi dahulu...</option>
                            </x-fe.input>
                            
                            <div class="pt-2">
                                <x-fe.map-picker 
                                    id="s" 
                                    addressName="sender_address" 
                                    latName="sender_latitude" 
                                    lngName="sender_longitude" 
                                    defaultLat="{{ old('sender_latitude', $user->latitude ?: '-6.2088') }}" 
                                    defaultLng="{{ old('sender_longitude', $user->longitude ?: '106.8456') }}" 
                                    labelText="Alamat Pickup" 
                                    buttonText="Pilih di Map" 
                                    infoText="Map ini untuk titik pickup, bukan tujuan paket.">{{ old('sender_address', $user->address) }}</x-fe.map-picker>
                            </div>
                        </div>
                    </x-fe.panel>

                    <!-- RECEIVER PANEL -->
                    <x-fe.panel title="Penerima" variant="accent" class="h-full text-left">
                        <x-fe.input label="Nama Penerima" name="receiver_name" required value="{{ old('receiver_name') }}" />
                        <x-fe.input label="Telepon Penerima" name="receiver_phone" required value="{{ old('receiver_phone') }}" />
                        
                        <x-fe.input type="select" label="Provinsi Penerima" name="r_prov" id="r_prov" onchange="loadKota('r_prov', 'r_city_id', '{{ $prefillDestinationCity }}')" required>
                            <option value="" disabled {{ $prefillDestinationProv ? '' : 'selected' }}>Pilih Provinsi...</option>
                            @foreach($provinces as $prov)
                                <option value="{{ $prov->id }}" {{ (string) $prefillDestinationProv === (string) $prov->id ? 'selected' : '' }}>{{ $prov->name }}</option>
                            @endforeach
                        </x-fe.input>
                        
                        <x-fe.input type="select" label="Kota/Kab. Penerima" name="receiver_city_id" id="r_city_id" required>
                            <option value="">Pilih Provinsi dahulu...</option>
                        </x-fe.input>

                        <div class="pt-2">
                            <x-fe.map-picker 
                                id="r" 
                                addressName="receiver_address" 
                                latName="receiver_latitude" 
                                lngName="receiver_longitude" 
                                defaultLat="{{ old('receiver_latitude', '-6.2088') }}" 
                                defaultLng="{{ old('receiver_longitude', '106.8456') }}" 
                                labelText="Alamat Tujuan" 
                                buttonText="Pilih di Map" 
                                infoText="Map ini untuk lokasi tepat pengantaran paket.">{{ old('receiver_address') }}</x-fe.map-picker>
                        </div>
                    </x-fe.panel>
                </div>

                <!-- CARGO DETAILS & PAYMENT METHODS -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- PACKAGE DETAILS -->
                    <x-fe.panel title="Detail Paket" variant="primary" class="h-full text-left">
                        <x-fe.input type="date" label="Tanggal Pickup" name="pickup_date" id="pickup_date" value="{{ old('pickup_date', now()->format('Y-m-d')) }}" min="{{ now()->format('Y-m-d') }}" required />

                        <x-fe.input type="number" label="Berat Paket (kg)" name="weight" id="payload_weight" step="0.1" min="0.1" value="{{ $prefillWeight }}" required class="text-lg font-bold" />

                        <x-fe.input type="select" label="Layanan" name="service_type" id="service_type" required>
                            <option value="REGULAR" {{ $prefillService === 'REGULAR' ? 'selected' : '' }}>REGULAR (2-3 hari)</option>
                            <option value="BEST" {{ $prefillService === 'BEST' ? 'selected' : '' }}>BEST Priority (1 hari)</option>
                            <option value="KARGO" {{ $prefillService === 'KARGO' ? 'selected' : '' }}>KARGO (min 10kg, hemat 30%)</option>
                        </x-fe.input>
                        
                        <div id="service-helper" class="badge badge-success badge-outline text-[10px] py-2.5 px-3 rounded-lg font-semibold my-2 w-full justify-start text-left border-slate-800 bg-slate-950/20">REGULAR cocok untuk kebanyakan rute.</div>

                        <div class="flex items-center justify-between bg-slate-950/40 border border-slate-800/80 rounded-xl p-4 md:p-6 my-4">
                            <div>
                                <span class="text-slate-400 text-xs">Estimasi Total</span><br>
                                <span id="display-price" class="text-primary text-3xl font-display font-black mt-1 inline-block">{{ old('total_price') ? 'Rp ' . number_format((float) old('total_price'), 0, ',', '.') : 'Rp 0' }}</span>
                            </div>
                        </div>

                        <p class="text-slate-500 text-[10px] text-center font-medium mt-1">Estimasi tarif final akan diverifikasi ulang oleh petugas hub asal.</p>
                    </x-fe.panel>

                    <!-- PAYMENT METHODS -->
                    <x-fe.panel title="Pembayaran" variant="accent" class="h-full text-left">
                        <!-- Choices grid -->
                        <div class="grid grid-cols-2 gap-3 mb-4">
                            <div class="glass-panel border-white/5 p-4 rounded-xl cursor-pointer hover:border-slate-700 transition-all text-left relative overflow-hidden" data-payment-card="transfer">
                                <strong class="text-secondary text-xs uppercase tracking-wide">Transfer</strong>
                                <p class="text-slate-400 text-[10px] mt-1 leading-normal">Verified by cashier before shipping.</p>
                            </div>
                            <div class="glass-panel border-white/5 p-4 rounded-xl cursor-pointer hover:border-slate-700 transition-all text-left relative overflow-hidden" data-payment-card="cash_on_pickup">
                                <strong class="text-primary text-xs uppercase tracking-wide">Cash Pickup</strong>
                                <p class="text-slate-400 text-[10px] mt-1 leading-normal">Pay courier at pickup time.</p>
                            </div>
                        </div>

                        <x-fe.input type="select" label="Metode Pembayaran" name="payment_method" id="payment_method" required class="hidden">
                            <option value="transfer" {{ old('payment_method', 'transfer') === 'transfer' ? 'selected' : '' }}>Transfer ke rekening hub</option>
                            <option value="cash_on_pickup" {{ old('payment_method') === 'cash_on_pickup' ? 'selected' : '' }}>Bayar tunai saat pickup</option>
                        </x-fe.input>

                        <!-- Panels -->
                        <div id="transfer-payment-panel" class="bg-slate-950/20 border border-slate-800/80 rounded-xl p-4 mt-4 space-y-4">
                            <div class="text-xs text-slate-300 leading-relaxed">
                                Transfer sesuai tarif estimasi ke salah satu rekening bank resmi hub:<br>
                                <br>
                                @forelse($bankAccounts as $bank)
                                    <div class="mb-3">
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-primary font-bold text-sm">{{ $bank->bank_name }} {{ $bank->account_number }}</span>
                                            <button type="button" onclick="copyToClipboard('{{ $bank->account_number }}', this)" class="btn btn-ghost btn-circle btn-xs hover:bg-white/10 text-slate-400 hover:text-slate-200 cursor-pointer flex items-center justify-center p-0.5" title="Salin nomor rekening">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                                                </svg>
                                            </button>
                                        </div>
                                        <span class="text-[10px] text-slate-400">A.N. {{ $bank->account_holder }}</span>
                                    </div>
                                @empty
                                    <div class="mb-3">
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-primary font-bold text-sm">BCA 8820-991-223</span>
                                            <button type="button" onclick="copyToClipboard('8820991223', this)" class="btn btn-ghost btn-circle btn-xs hover:bg-white/10 text-slate-400 hover:text-slate-200 cursor-pointer flex items-center justify-center p-0.5" title="Salin nomor rekening">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                                                </svg>
                                            </button>
                                        </div>
                                        <span class="text-[10px] text-slate-400">A.N. SPRINTLOG EXPEDITION</span>
                                    </div>
                                @endforelse
                            </div>
                            
                            <div class="border-t border-slate-800/50 pt-3">
                                <x-fe.input type="file" label="Upload Bukti Transfer" name="payment_proof" id="payment_proof" accept="image/*" />
                                <span class="text-[9px] text-slate-500 font-semibold block -mt-2">Review dilakukan maksimal 1x24 jam.</span>
                            </div>
                        </div>

                        <div id="cash-payment-panel" class="bg-slate-950/20 border border-slate-800/80 rounded-xl p-4 mt-4 text-xs text-slate-300 leading-relaxed" style="display: none;">
                            <p class="mb-3">
                                Anda dapat melakukan pembayaran tunai (cash) langsung kepada kurir SPRINTLOG pada saat pengambilan barang (pickup).
                            </p>
                            <p class="text-slate-400 font-medium">Status order akan berubah otomatis menjadi lunas setelah kurir menyerahkan uang setoran ke kasir hub.</p>
                        </div>

                        <x-fe.button type="submit" variant="primary" class="w-full mt-6 py-4 font-bold uppercase tracking-wider">Submit Order</x-fe.button>
                    </x-fe.panel>
                </div>

            </div>

            <!-- RIGHT SIDEBAR SUMMARY -->
            <div class="lg:col-span-4 lg:sticky lg:top-24 space-y-4">
                <x-fe.panel title="Ringkasan Order" subtitle="Cek detail pesanan Anda" variant="primary" class="text-left">
                    <div class="space-y-3 mb-6">
                        <div class="summary-row"><span>Pengirim</span><strong id="summary-sender">-</strong></div>
                        <div class="summary-row"><span>Kota Asal</span><strong id="summary-origin">-</strong></div>
                        <div class="summary-row"><span>Penerima</span><strong id="summary-receiver">-</strong></div>
                        <div class="summary-row"><span>Kota Tujuan</span><strong id="summary-destination">-</strong></div>
                        <div class="summary-row"><span>Berat</span><strong id="summary-weight">1.0 KG</strong></div>
                        <div class="summary-row"><span>Tanggal Pickup</span><strong id="summary-date">{{ old('pickup_date', now()->format('Y-m-d')) }}</strong></div>
                        <div class="summary-row"><span>Layanan</span><strong id="summary-service">REGULAR</strong></div>
                        <div class="summary-row"><span>Pembayaran</span><strong id="summary-payment">TRANSFER</strong></div>
                        <div class="summary-row"><span>Titik Map</span><strong id="summary-map">Area siap</strong></div>
                    </div>

                    <div class="bg-slate-950/30 p-4 border border-slate-800/80 rounded-xl mb-4 text-left">
                        <span class="text-[10px] text-slate-400 uppercase font-bold tracking-wide">Estimasi Terverifikasi</span>
                        <div id="summary-total" class="text-primary text-3xl font-display font-black mt-1">Rp 0</div>
                    </div>

                    <div id="summary-next" class="bg-slate-950/30 p-4 border border-slate-800/80 rounded-xl text-left">
                        <span class="text-secondary font-bold text-xs uppercase tracking-wider">Langkah Berikutnya</span>
                        <div class="text-slate-300 text-xs mt-1 leading-relaxed">Lengkapi kota asal dan tujuan untuk menghitung tarif pengiriman.</div>
                    </div>
                </x-fe.panel>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
const AREA_LOOKUP_CACHE = new Map();
const ADDRESS_REFINE_TIMERS = {};
const MAP_LOCKS = { s: false, r: false };
let rateRequestSerial = 0;
let verifiedRateReady = false;

function loadKota(provSelectId, kotaSelectId, selectedValue = null) {
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
            kotaSelect.innerHTML = '<option value="" disabled selected>Pilih Kota...</option>';
            kota.forEach(k => {
                const opt = document.createElement('option');
                opt.value = k.id;
                opt.textContent = k.name.toUpperCase();
                if (selectedValue && String(k.id) === String(selectedValue)) {
                    opt.selected = true;
                }
                kotaSelect.appendChild(opt);
            });

            if (selectedValue) {
                syncMapToSelection(provSelectId, kotaSelectId);
            }
            calculateRate();
            updateLiveSummary();
        });
}

function syncServiceHelper() {
    const service = document.getElementById('service_type').value;
    const weight = parseFloat(document.getElementById('payload_weight').value || 0);
    const box = document.getElementById('service-helper');
    if (!box) return;

    box.className = 'badge text-[10px] py-2.5 px-3 rounded-lg font-semibold my-2 w-full justify-start text-left border-slate-800 bg-slate-950/20';

    if (service === 'REGULAR') {
        box.classList.add('badge-success', 'badge-outline');
        box.textContent = 'REGULAR cocok untuk kebanyakan rute pengiriman harian.';
    } else if (service === 'BEST') {
        box.classList.add('badge-secondary', 'badge-outline');
        box.textContent = 'BEST Priority untuk paket mendesak. Jaminan tiba 1 hari.';
    } else if (service === 'KARGO') {
        if (weight < 10) {
            box.classList.remove('badge-success', 'badge-secondary', 'badge-outline');
            box.classList.add('badge-error');
            box.textContent = 'Minimum berat KARGO adalah 10 kg. Tambahkan berat paket Anda.';
        } else {
            box.classList.add('badge-accent', 'badge-outline');
            box.textContent = 'KARGO hemat untuk paket berat. Diskon tarif s/d 30%.';
        }
    }
}

function updateLiveSummary() {
    document.getElementById('summary-sender').textContent = document.getElementById('s_name').value || '-';
    document.getElementById('summary-receiver').textContent = document.getElementsByName('receiver_name')[0]?.value || '-';
    document.getElementById('summary-weight').textContent = (document.getElementById('payload_weight').value || '1.0') + ' KG';
    document.getElementById('summary-date').textContent = document.getElementById('pickup_date').value || '-';

    const originText = getSelectedText('s_city_id');
    document.getElementById('summary-origin').textContent = originText && !originText.includes('Pilih') ? originText : '-';

    const destText = getSelectedText('r_city_id');
    document.getElementById('summary-destination').textContent = destText && !destText.includes('Pilih') ? destText : '-';

    const service = document.getElementById('service_type').value;
    document.getElementById('summary-service').textContent = service;

    const payment = document.getElementById('payment_method').value;
    document.getElementById('summary-payment').textContent = payment === 'cash_on_pickup' ? 'CASH ON PICKUP' : 'TRANSFER BANK';

    const sLocked = MAP_LOCKS.s;
    const rLocked = MAP_LOCKS.r;
    const mapBadge = document.getElementById('summary-map');
    if (sLocked && rLocked) {
        mapBadge.textContent = 'LOCKED (S+R)';
        mapBadge.className = 'text-green-400 font-bold';
    } else if (sLocked || rLocked) {
        mapBadge.textContent = `HALF LOCK (${sLocked ? 'ORIGIN' : 'DEST'})`;
        mapBadge.className = 'text-yellow-400 font-bold';
    } else {
        mapBadge.textContent = 'ESTIMATED REGION';
        mapBadge.className = 'text-slate-400';
    }

    syncServiceHelper();
}

function getSelectedText(selectId) {
    const el = document.getElementById(selectId);
    if (!el || el.selectedIndex < 0) return '';
    return el.options[el.selectedIndex].text;
}

function calculateRate() {
    const originKotaId = document.getElementById('s_city_id').value;
    const destKotaId   = document.getElementById('r_city_id').value;
    const weight       = document.getElementById('payload_weight').value;
    const service      = document.getElementById('service_type').value;

    const displayPrice = document.getElementById('display-price');
    const summaryTotal = document.getElementById('summary-total');

    if (!originKotaId || !destKotaId || !weight) {
        updateRateResult(0, null, 'Lengkapi alamat kota asal dan tujuan.');
        return;
    }

    if (service === 'KARGO' && parseFloat(weight) < 10) {
        updateRateResult(0, null, 'Minimum berat layanan KARGO adalah 10 kg.');
        return;
    }

    const serial = ++rateRequestSerial;
    
    if (displayPrice) displayPrice.classList.add('loading-pulse');
    if (summaryTotal) summaryTotal.classList.add('loading-pulse');
    
    updateRateResult(0, 'Rp ...', 'Menghitung tarif verifikasi...');

    fetch(`/api/calculate-rate?origin_kota_id=${originKotaId}&destination_kota_id=${destKotaId}&weight=${weight}&service_type=${service}`)
        .then(response => response.json())
        .then(data => {
            if (displayPrice) displayPrice.classList.remove('loading-pulse');
            if (summaryTotal) summaryTotal.classList.remove('loading-pulse');

            if (serial !== rateRequestSerial) return;
            if (data.error) {
                let cleanMsg = data.error;
                if (data.error.includes('MINIMAL_BERAT_10KG')) {
                    cleanMsg = 'Layanan KARGO membutuhkan berat minimal 10 kg.';
                } else if (data.error.includes('Rute tidak tersedia')) {
                    cleanMsg = 'Rute pengiriman tidak didukung saat ini.';
                }
                updateRateResult(0, null, cleanMsg);
            } else {
                updateRateResult(data.total_price, data.total_price_fmt, 'Estimasi siap. Silakan kirim order.');
                verifiedRateReady = true;
            }
        })
        .catch(() => {
            if (displayPrice) displayPrice.classList.remove('loading-pulse');
            if (summaryTotal) summaryTotal.classList.remove('loading-pulse');

            if (serial !== rateRequestSerial) return;
            updateRateResult(0, null, 'Gagal terhubung ke API server.');
        });
}

function updateRateResult(price, formatted, statusMessage) {
    verifiedRateReady = false;
    const dispVal = formatted || 'Rp ---';
    document.getElementById('display-price').textContent = dispVal;
    document.getElementById('summary-total').textContent = dispVal;
    
    // Hidden inputs in form for server validation
    let priceInput = document.getElementById('hidden_total_price');
    if (!priceInput) {
        priceInput = document.createElement('input');
        priceInput.type = 'hidden';
        priceInput.name = 'total_price';
        priceInput.id = 'hidden_total_price';
        document.querySelector('form').appendChild(priceInput);
    }
    priceInput.value = price;

    updateNextAction(statusMessage);
}

function updateNextAction(text) {
    const box = document.getElementById('summary-next');
    if (!box) return;
    box.querySelector('div').textContent = text;
}

function geocodeQuery(query) {
    const cached = AREA_LOOKUP_CACHE.get(query);
    if (cached) return Promise.resolve(cached);

    return fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=1`)
        .then(r => r.json())
        .then(results => {
            const first = results[0] ? {
                lat: Number(results[0].lat),
                lng: Number(results[0].lon),
            } : null;

            AREA_LOOKUP_CACHE.set(query, first);
            return first;
        });
}

function geocodeProvince(provinceName) {
    return geocodeQuery(`${provinceName}, Indonesia`);
}

function geocodeArea(cityName, provinceName) {
    return geocodeQuery(`${cityName}, ${provinceName}, Indonesia`);
}

function geocodeAddress(address, cityName, provinceName) {
    return geocodeQuery(`${address}, ${cityName}, ${provinceName}, Indonesia`);
}

function focusMapById(mapId, lat, lng, zoom = 13) {
    const focusFn = window[`focusMap_${mapId}`];
    if (typeof focusFn === 'function') {
        focusFn(lat, lng, zoom, false);
    }
}

function syncMapToProvince(provSelectId, citySelectId) {
    const provinceName = getSelectedText(provSelectId);
    if (!provinceName || provinceName.includes('Pilih')) {
        return;
    }

    const citySelect = document.getElementById(citySelectId);
    if (citySelect && citySelect.value) {
        return;
    }

    const mapId = citySelectId === 's_city_id' ? 's' : 'r';
    geocodeProvince(provinceName)
        .then(point => {
            if (!point) return;
            focusMapById(mapId, point.lat, point.lng, 8);
        })
        .catch(() => {});
}

function syncMapToSelection(provSelectId, citySelectId) {
    const provinceName = getSelectedText(provSelectId);
    const cityName = getSelectedText(citySelectId);

    if (!provinceName || !cityName || cityName.includes('Pilih')) {
        return;
    }

    const mapId = citySelectId === 's_city_id' ? 's' : 'r';
    geocodeArea(cityName, provinceName)
        .then(point => {
            if (!point) return;
            focusMapById(mapId, point.lat, point.lng, 12);
        })
        .catch(() => {});
}

function scheduleAddressRefine(mapId, provSelectId, citySelectId, addressInputId) {
    const provinceName = getSelectedText(provSelectId);
    const cityName = getSelectedText(citySelectId);
    const address = document.getElementById(addressInputId)?.value?.trim();

    if (!provinceName || !cityName || !address || cityName.includes('Pilih')) {
        return;
    }

    clearTimeout(ADDRESS_REFINE_TIMERS[mapId]);
    ADDRESS_REFINE_TIMERS[mapId] = setTimeout(() => {
        geocodeAddress(address, cityName, provinceName)
            .then(point => {
                if (!point) return;
                focusMapById(mapId, point.lat, point.lng, 15);
            })
            .catch(() => {});
    }, 650);
}

document.addEventListener('DOMContentLoaded', () => {
    const senderProv = document.getElementById('s_prov');
    const receiverProv = document.getElementById('r_prov');

    if (senderProv.value) {
        loadKota('s_prov', 's_city_id', @json($prefillOriginCity));
    }
    if (receiverProv.value) {
        loadKota('r_prov', 'r_city_id', @json($prefillDestinationCity));
    }

    document.getElementById('s_prov').addEventListener('change', () => {
        syncMapToProvince('s_prov', 's_city_id');
    });
    document.getElementById('r_prov').addEventListener('change', () => {
        syncMapToProvince('r_prov', 'r_city_id');
    });

    document.getElementById('s_address_display').addEventListener('input', () => {
        scheduleAddressRefine('s', 's_prov', 's_city_id', 's_address_display');
    });
    document.getElementById('r_address_display').addEventListener('input', () => {
        scheduleAddressRefine('r', 'r_prov', 'r_city_id', 'r_address_display');
    });

    document.querySelectorAll('input, select, textarea').forEach(input => {
        input.addEventListener('input', updateLiveSummary);
        input.addEventListener('change', updateLiveSummary);
    });

    document.addEventListener('map-point-updated', event => {
        if (event.detail?.id === 's' || event.detail?.id === 'r') {
            MAP_LOCKS[event.detail.id] = event.detail.status === 'POINT_LOCKED';
            updateLiveSummary();
        }
    });

    updateLiveSummary();
});

document.getElementById('r_city_id').addEventListener('change', () => {
    calculateRate();
    syncMapToSelection('r_prov', 'r_city_id');
});
document.getElementById('s_city_id').addEventListener('change', () => {
    calculateRate();
    syncMapToSelection('s_prov', 's_city_id');
});
document.getElementById('payload_weight').addEventListener('input', calculateRate);
document.getElementById('service_type').addEventListener('change', calculateRate);

function syncPaymentMethodUI() {
    const paymentMethod = document.getElementById('payment_method').value;
    const transferPanel = document.getElementById('transfer-payment-panel');
    const cashPanel = document.getElementById('cash-payment-panel');
    const proofInput = document.getElementById('payment_proof');
    const paymentCards = document.querySelectorAll('[data-payment-card]');

    if (paymentMethod === 'cash_on_pickup') {
        transferPanel.style.display = 'none';
        cashPanel.style.display = 'block';
        proofInput.required = false;
        proofInput.value = '';
    } else {
        transferPanel.style.display = 'block';
        cashPanel.style.display = 'none';
        proofInput.required = true;
    }

    paymentCards.forEach(card => {
        card.classList.toggle('is-active', card.dataset.paymentCard === paymentMethod);
    });
    updateLiveSummary();
}

document.getElementById('btn-use-profile').addEventListener('click', () => {
    document.getElementById('s_name').value = "{{ $user->name }}";
    document.getElementById('s_phone').value = "{{ $user->phone }}";
    document.getElementById('s_address_display').value = @json($user->address);
    document.getElementById('s_lat').value = @json($user->latitude);
    document.getElementById('s_lng').value = @json($user->longitude);

    const userProvId = @json($userOriginProvinceId);
    const userCityId = @json($userOriginCityId);
    if (userProvId) {
        document.getElementById('s_prov').value = userProvId;
        loadKota('s_prov', 's_city_id', userCityId);
    }

    if (@json((bool) $user->latitude) && @json((bool) $user->longitude) && typeof window.focusMap_s === 'function') {
        window.focusMap_s(@json($user->latitude), @json($user->longitude), 14, false);
    }
});

document.getElementById('btn-manual-sender').addEventListener('click', () => {
    document.getElementById('s_address_display').focus();
});

document.getElementById('payment_method').addEventListener('change', syncPaymentMethodUI);
document.getElementById('payment_proof').addEventListener('change', updateLiveSummary);

document.querySelector('form[action="{{ route('order.store') }}"]').addEventListener('submit', (event) => {
    const service = document.getElementById('service_type').value;
    const weight = parseFloat(document.getElementById('payload_weight').value || 0);
    if (service === 'KARGO' && weight < 10) {
        event.preventDefault();
        syncServiceHelper();
        updateNextAction('Perbaiki minimum berat KARGO sebelum submit.');
        document.getElementById('payload_weight').focus();
        return;
    }

    if (!verifiedRateReady) {
        event.preventDefault();
        calculateRate();
        updateNextAction('Tunggu estimasi terverifikasi sebelum submit.');
        document.getElementById('payload_weight').focus();
    }
});

// Setup click hooks on visual cards
document.querySelectorAll('[data-payment-card]').forEach(card => {
    card.addEventListener('click', () => {
        document.getElementById('payment_method').value = card.dataset.paymentCard;
        syncPaymentMethodUI();
    });
});

syncPaymentMethodUI();
syncServiceHelper();
</script>
@endpush

@endsection
