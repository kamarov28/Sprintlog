@extends('fe.layouts.main')

@section('body_class', 'order-create-page')

@push('head_assets')
    <link rel="preconnect" href="https://unpkg.com">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
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
<div class="order-create-wrapper">
    <x-fe.page-header title="Buat Order" subtitle="Isi pickup, tujuan, paket, dan pembayaran dalam satu alur.">
        <x-fe.button href="{{ route('dashboard') }}" variant="secondary" style="font-size: 0.8rem;">Dashboard</x-fe.button>
    </x-fe.page-header>

    @if ($errors->any())
        <x-fe.alert tone="danger" title="Data belum lengkap">
            Mohon cek kembali field yang wajib diisi.
        </x-fe.alert>
    @endif

    <form action="{{ route('order.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="flow-stepper" aria-label="Order request progress">
            <div class="flow-step is-active" data-flow-step="sender"><span class="flow-step__code">01 / Pengirim</span><span class="flow-step__label">Identitas pickup dan titik asal.</span></div>
            <div class="flow-step" data-flow-step="receiver"><span class="flow-step__code">02 / Penerima</span><span class="flow-step__label">Kontak tujuan dan kota.</span></div>
            <div class="flow-step" data-flow-step="package"><span class="flow-step__code">03 / Paket</span><span class="flow-step__label">Berat, layanan, estimasi.</span></div>
            <div class="flow-step" data-flow-step="payment"><span class="flow-step__code">04 / Bayar</span><span class="flow-step__label">Transfer atau cash pickup.</span></div>
            <div class="flow-step" data-flow-step="review"><span class="flow-step__code">05 / Review</span><span class="flow-step__label">Cek ulang sebelum submit.</span></div>
        </div>

        <div class="checkout-shell">
            <div class="checkout-main">
        <div class="grid-2" style="gap: 4rem; align-items: flex-start;">
            
            <!-- LEFT COLUMN: SENDER & DESTINATION -->
            <div style="display: flex; flex-direction: column; gap: 3rem; min-width: 0;">
                
                <!-- SENDER SECTION -->
                <x-fe.panel title="Pengirim" variant="primary">
                    <div style="margin-bottom: 2rem; display: flex; gap: 1rem;">
                        <x-fe.button type="button" id="btn-use-profile" variant="primary" style="font-size: 0.7rem;">Pakai Profil</x-fe.button>
                        <x-fe.button type="button" id="btn-manual-sender" variant="secondary" style="font-size: 0.7rem;">Input Manual</x-fe.button>
                    </div>

                    <div id="sender-fields">
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
                        
                        <div style="margin-top: 1.5rem;">
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

                <!-- RECEIVER SECTION -->
                <x-fe.panel title="Penerima" variant="accent">
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
                </x-fe.panel>

            </div>

            <!-- RIGHT COLUMN: CARGO & PAYMENT -->
            <div style="display: flex; flex-direction: column; gap: 3rem; min-width: 0;">
                
                <x-fe.panel title="Detail Paket" variant="primary">
                    <x-fe.input type="date" label="Tanggal Pickup" name="pickup_date" id="pickup_date" value="{{ old('pickup_date', now()->format('Y-m-d')) }}" min="{{ now()->format('Y-m-d') }}" required />

                    <x-fe.input type="number" label="Berat Paket (kg)" name="weight" id="payload_weight" step="0.1" min="0.1" value="{{ $prefillWeight }}" required style="font-size: 1.5rem; font-weight: bold;" />

                    <x-fe.input type="select" label="Layanan" name="service_type" id="service_type" required>
                        <option value="REGULAR" {{ $prefillService === 'REGULAR' ? 'selected' : '' }}>REGULAR (2-3 hari)</option>
                        <option value="BEST" {{ $prefillService === 'BEST' ? 'selected' : '' }}>BEST Priority (1 hari)</option>
                        <option value="KARGO" {{ $prefillService === 'KARGO' ? 'selected' : '' }}>KARGO (min 10kg, hemat 30%)</option>
                    </x-fe.input>
                    <div id="service-helper" class="helper-chip is-ok">REGULAR cocok untuk kebanyakan rute.</div>

                    <div class="quote-display">
                        <span class="text-gray" style="font-size: 0.8rem;">Estimasi Total</span><br>
                        <span id="display-price" class="text-primary quote-display__value" style="font-size: 2.5rem; font-weight: 900;">{{ old('total_price') ? 'Rp ' . number_format((float) old('total_price'), 0, ',', '.') : 'Rp 0' }}</span>
                    </div>

                    <p class="text-gray" style="font-size: 0.7rem; text-align: center;">Estimasi akan diverifikasi ulang oleh server dari kota yang dipilih.</p>
                </x-fe.panel>

                <x-fe.panel title="Pembayaran" variant="accent">
                    <div class="payment-choice-grid">
                        <div class="payment-choice-card" data-payment-card="transfer">
                            <strong class="text-accent">TRANSFER</strong>
                            <p class="text-gray" style="font-size: 0.72rem; margin: 0.5rem 0 0;">Verified by hub cashier before shipment activation.</p>
                        </div>
                        <div class="payment-choice-card" data-payment-card="cash_on_pickup">
                            <strong class="text-primary">CASH PICKUP</strong>
                            <p class="text-gray" style="font-size: 0.72rem; margin: 0.5rem 0 0;">Pay courier at pickup; cashier verifies courier handover.</p>
                        </div>
                    </div>

                    <x-fe.input type="select" label="Metode Pembayaran" name="payment_method" id="payment_method" required>
                        <option value="transfer" {{ old('payment_method', 'transfer') === 'transfer' ? 'selected' : '' }}>Transfer ke rekening hub</option>
                        <option value="cash_on_pickup" {{ old('payment_method') === 'cash_on_pickup' ? 'selected' : '' }}>Bayar tunai saat pickup</option>
                    </x-fe.input>

                    <div id="transfer-payment-panel" class="payment-mode-panel">
                        <p class="text-main mb-4" style="font-size: 0.85rem;">
                            Transfer sesuai estimasi ke salah satu rekening berikut:<br>
                            <br>
                            @forelse($bankAccounts as $bank)
                                <span class="text-primary" style="font-weight: bold;">{{ $bank->bank_name }} {{ $bank->account_number }}</span><br>
                                AN. {{ $bank->account_holder }}<br><br>
                            @empty
                                <span class="text-primary" style="font-weight: bold;">BCA 8820-991-223</span><br>
                                AN. SPRINTLOG EXPEDITION<br><br>
                            @endforelse
                        </p>
                        <p class="text-gray" style="font-size: 0.72rem; margin-bottom: 1rem;">Upload bukti setelah cek estimasi di atas.</p>

                        <x-fe.input type="file" label="Bukti Transfer" name="payment_proof" id="payment_proof" accept="image/*" />
                    </div>

                    <div id="cash-payment-panel" class="payment-mode-panel" style="display: none;">
                        <p class="text-main mb-4" style="font-size: 0.85rem;">
                            Kamu membayar tunai ke kurir saat pickup. Kurir akan menyerahkan pembayaran ke kasir hub untuk verifikasi.
                        </p>
                        <p class="text-gray" style="font-size: 0.72rem; margin-bottom: 1rem;">Status akan otomatis berubah setelah kasir hub memverifikasi pembayaran.</p>
                    </div>

                    <x-fe.button type="submit" variant="primary" style="width: 100%; padding: 1.2rem; font-weight: 900; margin-top: 1rem;">Submit Order</x-fe.button>
                </x-fe.panel>

            </div>

        </div>
            </div>

            <aside class="checkout-aside" aria-label="Live order summary">
                <x-fe.panel title="Ringkasan Order" subtitle="Cek lagi sebelum submit" variant="primary" class="live-summary">
                    <div class="summary-row"><span>Pengirim</span><strong id="summary-sender">-</strong></div>
                    <div class="summary-row"><span>Kota Asal</span><strong id="summary-origin">-</strong></div>
                    <div class="summary-row"><span>Penerima</span><strong id="summary-receiver">-</strong></div>
                    <div class="summary-row"><span>Kota Tujuan</span><strong id="summary-destination">-</strong></div>
                    <div class="summary-row"><span>Berat</span><strong id="summary-weight">1.0 KG</strong></div>
                    <div class="summary-row"><span>Tanggal Pickup</span><strong id="summary-date">{{ old('pickup_date', now()->format('Y-m-d')) }}</strong></div>
                    <div class="summary-row"><span>Layanan</span><strong id="summary-service">REGULAR</strong></div>
                    <div class="summary-row"><span>Pembayaran</span><strong id="summary-payment">TRANSFER</strong></div>
                    <div class="summary-row"><span>Titik Map</span><strong id="summary-map">Area siap</strong></div>
                    <div class="summary-total">
                        <span class="data-label">Estimasi Terverifikasi</span>
                        <div id="summary-total" class="text-primary" style="font-size: 2rem; font-weight: 900;">Rp 0</div>
                    </div>
                    <div id="summary-next" class="next-action-box">
                        <span class="data-label">Langkah Berikutnya</span>
                        <div class="text-main" style="font-size: 0.82rem;">Lengkapi kota asal dan tujuan untuk menghitung ongkir.</div>
                    </div>
                </x-fe.panel>
            </aside>
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

function calculateRate() {
    const originId = document.getElementById('s_city_id').value;
    const destId = document.getElementById('r_city_id').value;
    const weight = document.getElementById('payload_weight').value;
    const service = document.getElementById('service_type').value;
    
    syncServiceHelper();
    updateLiveSummary();
    verifiedRateReady = false;

    if (!originId || !destId || !weight) {
        document.getElementById('display-price').textContent = 'Rp 0';
        document.getElementById('summary-total').textContent = 'Rp 0';
        return;
    }

    const serial = ++rateRequestSerial;
    document.getElementById('display-price').textContent = 'Menghitung...';
    document.getElementById('summary-total').textContent = 'Menghitung...';

    fetch(`/api/calculate-rate?origin_kota_id=${originId}&destination_kota_id=${destId}&weight=${weight}&service_type=${service}`)
        .then(r => r.json())
        .then(data => {
            if (serial !== rateRequestSerial) return;

            if (data.error) {
                document.getElementById('display-price').textContent = 'Rp 0';
                document.getElementById('summary-total').textContent = 'Rp 0';
                updateNextAction(data.error);
                return;
            }
            if (data.total_price) {
                verifiedRateReady = true;
                document.getElementById('display-price').textContent = data.total_price_fmt;
                document.getElementById('summary-total').textContent = data.total_price_fmt;
                updateNextAction('Estimasi siap. Cek pembayaran dan titik map, lalu submit request.');
            }
        })
        .catch(() => {
            if (serial !== rateRequestSerial) return;

            document.getElementById('display-price').textContent = 'Rp 0';
            document.getElementById('summary-total').textContent = 'Rp 0';
            updateNextAction('Ongkir belum bisa dimuat. Coba cek ulang kota dan koneksi.');
        });
}

function shortText(value, fallback = '-') {
    const clean = (value || '').trim();
    if (!clean) return fallback;
    return clean.length > 28 ? clean.slice(0, 25) + '...' : clean;
}

function setSummary(id, value, fallback = '-') {
    const target = document.getElementById(id);
    if (target) target.textContent = shortText(value, fallback);
}

function updateNextAction(message) {
    const box = document.querySelector('#summary-next .text-main');
    if (box) box.textContent = message;
}

function syncFlowStepper() {
    const checks = {
        sender: document.getElementById('s_name').value && document.getElementById('s_phone').value && document.getElementById('s_city_id').value,
        receiver: document.querySelector('[name="receiver_name"]').value && document.querySelector('[name="receiver_phone"]').value && document.getElementById('r_city_id').value,
        package: parseFloat(document.getElementById('payload_weight').value || 0) > 0 && document.getElementById('service_type').value,
        payment: document.getElementById('payment_method').value === 'cash_on_pickup' || document.getElementById('payment_proof').files.length > 0,
    };

    const order = ['sender', 'receiver', 'package', 'payment', 'review'];
    let activeFound = false;
    order.forEach(step => {
        const el = document.querySelector(`[data-flow-step="${step}"]`);
        if (!el) return;
        const complete = step === 'review' ? Object.values(checks).every(Boolean) : Boolean(checks[step]);
        el.classList.toggle('is-complete', complete);
        el.classList.remove('is-active');
        if (!complete && !activeFound) {
            el.classList.add('is-active');
            activeFound = true;
        }
    });
    if (!activeFound) {
        document.querySelector('[data-flow-step="review"]')?.classList.add('is-active');
    }
}

function syncServiceHelper() {
    const helper = document.getElementById('service-helper');
    const service = document.getElementById('service_type').value;
    const weight = parseFloat(document.getElementById('payload_weight').value || 0);
    if (!helper) return;

    helper.classList.remove('is-warning', 'is-ok');
    if (service === 'KARGO' && weight < 10) {
        helper.classList.add('is-warning');
        helper.textContent = 'KARGO butuh minimal 10 kg. Tambah berat atau pilih REGULAR.';
    } else if (service === 'BEST') {
        helper.classList.add('is-ok');
        helper.textContent = 'BEST cocok untuk paket prioritas yang perlu lebih cepat.';
    } else if (service === 'KARGO') {
        helper.classList.add('is-ok');
        helper.textContent = 'KARGO aktif untuk paket berat mulai 10 kg.';
    } else {
        helper.classList.add('is-ok');
        helper.textContent = 'REGULAR cocok untuk kebanyakan rute.';
    }
}

function updateLiveSummary() {
    setSummary('summary-sender', document.getElementById('s_name').value);
    setSummary('summary-origin', getSelectedText('s_city_id').replace('Pilih Kota...', ''));
    setSummary('summary-receiver', document.querySelector('[name="receiver_name"]').value);
    setSummary('summary-destination', getSelectedText('r_city_id').replace('Pilih Kota...', ''));
    setSummary('summary-weight', `${document.getElementById('payload_weight').value || '-'} KG`);
    setSummary('summary-date', document.getElementById('pickup_date').value);
    setSummary('summary-service', document.getElementById('service_type').value);
    setSummary('summary-payment', document.getElementById('payment_method').value === 'cash_on_pickup' ? 'Cash Pickup' : 'Transfer');
    setSummary('summary-map', MAP_LOCKS.s && MAP_LOCKS.r ? 'Dua titik siap' : (MAP_LOCKS.s || MAP_LOCKS.r ? 'Satu titik siap' : 'Area siap'));
    syncFlowStepper();

    if (!document.getElementById('s_city_id').value || !document.getElementById('r_city_id').value) {
        updateNextAction('Lengkapi kota asal dan tujuan untuk menghitung ongkir.');
    } else if (document.getElementById('service_type').value === 'KARGO' && parseFloat(document.getElementById('payload_weight').value || 0) < 10) {
        updateNextAction('KARGO butuh minimal 10 kg sebelum request bisa dikirim.');
    } else if (document.getElementById('payment_method').value === 'transfer' && !document.getElementById('payment_proof').files.length) {
        updateNextAction('Upload bukti transfer setelah cek estimasi.');
    } else {
        updateNextAction('Siap submit. Ongkir final tetap diverifikasi server.');
    }
}

function getSelectedText(selectId) {
    const select = document.getElementById(selectId);
    if (!select) return '';

    const option = select.options[select.selectedIndex];
    return option ? option.textContent.trim() : '';
}

function geocodeQuery(query) {
    if (AREA_LOOKUP_CACHE.has(query)) {
        return Promise.resolve(AREA_LOOKUP_CACHE.get(query));
    }

    return fetch(`https://nominatim.openstreetmap.org/search?format=json&limit=1&countrycodes=id&q=${encodeURIComponent(query)}`)
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
        .catch(() => {
            // Silent fallback.
        });
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
        .catch(() => {
            // Silent fallback: map stays at previous known point.
        });
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
            .catch(() => {
                // Silent fallback.
            });
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

// Toggle logics for sender
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
syncPaymentMethodUI();
syncServiceHelper();
</script>
@endpush

@endsection
