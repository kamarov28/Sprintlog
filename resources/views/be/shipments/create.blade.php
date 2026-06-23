@extends('be.layouts.main')

@section('header_title', 'Tambah Shipment')

@section('content')

    <div class="hud-panel pos-counter-shell">
        <h3 class="font-bank text-accent mb-4">Input Shipment Counter</h3>
        <p class="font-ui text-gray" style="font-size: 0.86rem; line-height: 1.65; margin: -0.5rem 0 1.5rem;">
            Dipakai kasir untuk paket walk-in. Untuk order customer dari website, gunakan flow pickup sampai shipment aktif.
        </p>

        @if ($errors->any())
        <div class="inline-alert">
            <div class="font-ui text-gray" style="font-size: 0.8rem; margin-bottom: 0.5rem;">Data belum lengkap:</div>
            <ul style="margin: 0; padding-left: 1.5rem;">
                @foreach ($errors->all() as $error)
                <li class="font-ui text-gray" style="font-size: 0.85rem;">{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form action="{{ route('be.shipments.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="pos-form-grid pos-contact-grid">
                <!-- Sender Info -->
                <div class="form-cluster">
                    <div class="font-ui text-primary mb-3" style="font-size: 0.8rem;">Data Pengirim</div>
                    <div class="pos-field">
                        <label class="font-ui text-gray" style="font-size: 0.8rem;">Nama</label><br>
                        <input type="text" name="sender_name" value="{{ old('sender_name') }}" required style="width: 100%; padding: 0.5rem; border: none; border-bottom: 2px solid var(--color-gray); background: transparent; font-family: var(--font-ui); color: var(--color-text-main); font-size: 1.1rem; outline: none;">
                    </div>
                    <div class="pos-field">
                        <label class="font-ui text-gray" style="font-size: 0.8rem;">Nomor Telepon</label><br>
                        <input type="text" name="sender_phone" value="{{ old('sender_phone') }}" required style="width: 100%; padding: 0.5rem; border: none; border-bottom: 2px solid var(--color-gray); background: transparent; font-family: var(--font-ui); color: var(--color-text-main); font-size: 1.1rem; outline: none;">
                    </div>
                </div>

                <!-- Receiver Info -->
                <div class="form-cluster form-cluster--accent">
                    <div class="font-ui text-accent mb-3" style="font-size: 0.8rem;">Data Penerima</div>
                    <div class="pos-field">
                        <label class="font-ui text-gray" style="font-size: 0.8rem;">Nama</label><br>
                        <input type="text" name="receiver_name" value="{{ old('receiver_name') }}" required style="width: 100%; padding: 0.5rem; border: none; border-bottom: 2px solid var(--color-gray); background: transparent; font-family: var(--font-ui); color: var(--color-text-main); font-size: 1.1rem; outline: none;">
                    </div>
                    <div class="pos-field">
                        <label class="font-ui text-gray" style="font-size: 0.8rem;">Nomor Telepon</label><br>
                        <input type="text" name="receiver_phone" value="{{ old('receiver_phone') }}" required style="width: 100%; padding: 0.5rem; border: none; border-bottom: 2px solid var(--color-gray); background: transparent; font-family: var(--font-ui); color: var(--color-text-main); font-size: 1.1rem; outline: none;">
                    </div>
                    <div class="pos-address-pair">
                        <div class="pos-field">
                            <label class="font-ui text-gray" style="font-size: 0.8rem;">Provinsi Tujuan</label><br>
                            <select name="receiver_province_id" id="receiver_province_id" required style="width: 100%; padding: 0.5rem; font-family: var(--font-ui);">
                                <option value="" disabled {{ old('receiver_province_id') ? '' : 'selected' }}>Pilih Provinsi...</option>
                                @foreach($provinces as $province)
                                    <option value="{{ $province->id }}" {{ old('receiver_province_id') == $province->id ? 'selected' : '' }}>{{ $province->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="pos-field">
                            <label class="font-ui text-gray" style="font-size: 0.8rem;">Kota/Kab. Tujuan</label><br>
                            <select name="receiver_city_id" id="receiver_city_id" required style="width: 100%; padding: 0.5rem; font-family: var(--font-ui);">
                                <option value="">Pilih Kota...</option>
                            </select>
                        </div>
                    </div>
                    <div class="pos-field">
                        <label class="font-ui text-gray" style="font-size: 0.8rem;">Alamat Lengkap Tujuan</label><br>
                        <textarea name="receiver_address" rows="2" required placeholder="Nama jalan, nomor rumah, RT/RW, kelurahan, kecamatan, patokan" style="width: 100%; padding: 0.5rem; border: none; border-bottom: 2px solid var(--color-gray); background: transparent; font-family: var(--font-ui); color: var(--color-text-main); font-size: 0.95rem; outline: none; resize: none;">{{ old('receiver_address') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Delivery & Package Config -->
            <div class="pos-form-grid">
                
                <!-- Routine & Destination -->
                <div class="form-cluster">
                    <div class="font-ui text-primary mb-3" style="font-size: 0.8rem;">Rute</div>
                    <div style="margin-bottom: 1rem;">
                        <label class="font-ui text-gray" style="font-size: 0.8rem;">Hub Asal</label><br>
                        @php $myBranch = $branches->firstWhere('id', auth()->user()->branch_id); @endphp
                        @if($myBranch)
                            <input type="hidden" name="origin_branch_id" value="{{ $myBranch->id }}">
                            <input type="text" disabled value="{{ $myBranch->name }} ({{ $myBranch->city }})" style="width: 100%; padding: 0.5rem; background: rgba(255,255,255,0.05); color: var(--color-gray); border: 1px solid var(--color-panel-border); font-family: var(--font-ui);">
                        @else
                            <select name="origin_branch_id" required style="width: 100%; padding: 0.5rem; font-family: var(--font-ui);">
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" data-zone="{{ $branchZones[$branch->id] ?? '' }}">{{ $branch->name }} ({{ $branch->city }})</option>
                                @endforeach
                            </select>
                        @endif
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label class="font-ui text-gray" style="font-size: 0.8rem;">Hub Tujuan</label><br>
                        <select name="destination_branch_id" required style="width: 100%; padding: 0.5rem; font-family: var(--font-ui);">
                            <option value="{{ auth()->user()->branch_id }}" data-zone="{{ $branchZones[auth()->user()->branch_id] ?? '' }}" style="color: var(--color-primary); font-weight: bold;">-- PENGIRIMAN LOKAL (DIRECT) --</option>
                            @foreach($branches as $branch)
                                @if($branch->id !== auth()->user()->branch_id)
                                    <option value="{{ $branch->id }}" data-zone="{{ $branchZones[$branch->id] ?? '' }}">TRANSIT KE: {{ $branch->name }} ({{ $branch->city }})</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Package & Handlers -->
                <div class="form-cluster form-cluster--accent">
                    <div class="font-ui text-accent mb-3" style="font-size: 0.8rem;">Paket</div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 1rem; margin-bottom: 1rem;">
                        <div>
                            <label class="font-ui text-gray" style="font-size: 0.8rem;">Berat (KG)</label><br>
                            <input type="number" name="weight" min="0.1" step="0.1" required style="width: 100%; padding: 0.5rem; border: 1px solid var(--color-panel-border); background: transparent; font-family: var(--font-ui); color: var(--color-text-main); outline: none;">
                        </div>
                        <div>
                            <label class="font-ui text-gray" style="font-size: 0.8rem;">Layanan</label><br>
                            <select name="service_type" id="service_type" required style="width: 100%; padding: 0.5rem; font-family: var(--font-ui);">
                                <option value="REGULAR" data-multiplier="1.0">REGULER (Standar)</option>
                                <option value="BEST" data-multiplier="1.3">BEST (Same Day)</option>
                                <option value="KARGO" data-multiplier="0.7">KARGO (Min. 10 KG)</option>
                            </select>
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 1rem;">
                        <label class="font-ui text-gray" style="font-size: 0.8rem;">Assign Kurir (opsional)</label><br>
                        <select name="courier_id" style="width: 100%; padding: 0.5rem; font-family: var(--font-ui);">
                            <option value="">-- BIARKAN PENDING (AUTO) --</option>
                            @foreach($couriers as $courier)
                                <option value="{{ $courier->id }}" @disabled(! $courier->vehicle || $courier->vehicle->status !== 'active')>
                                    {{ $courier->name }} / {{ $courier->vehicle?->label() ?? 'belum ada kendaraan aktif' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Item Description -->
            <div style="margin-bottom: 3rem;">
                <label class="font-ui text-gray" style="font-size: 0.8rem;">Deskripsi Item</label><br>
                <input type="text" name="item_name" required placeholder="contoh: elektronik / pakaian / makanan" style="width: 100%; padding: 0.5rem; border: none; border-bottom: 2px solid var(--color-primary); background: transparent; font-family: var(--font-ui); color: var(--color-text-main); font-size: 1.2rem; outline: none;">
            </div>

            <!-- Payment Section -->
            <div class="pos-payment-panel">
                <h4 class="font-bank text-primary mb-3">Pembayaran</h4>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; align-items: end;">
                    <div>
                        <label class="font-ui text-gray" style="font-size: 0.8rem;">Total Tagihan</label><br>
                        <div class="font-bank pos-payment-total" id="display-total-price">Rp 0</div>
                    </div>
                    <div>
                        <label class="font-ui text-gray" style="font-size: 0.8rem;">Metode Pembayaran</label><br>
                        <select name="payment_method" id="payment_method" required style="width: 100%; padding: 0.5rem; font-family: var(--font-ui); margin-bottom: 10px;">
                            <option value="cash">TUNAI (CASH)</option>
                            <option value="transfer">TRANSFER BANK</option>
                            <option value="e-wallet">QRIS / E-WALLET</option>
                        </select>
                    </div>
                </div>

                <div id="cash-panel" class="pos-payment-row">
                    <div>
                        <label class="font-ui text-gray" style="font-size: 0.8rem;">Nominal Diterima (Rp)</label><br>
                        <input type="number" name="amount_received" id="amount_received" min="0" step="1000" style="width: 100%; padding: 0.5rem; border: none; border-bottom: 2px solid var(--color-gray); background: transparent; font-family: var(--font-ui); color: var(--color-text-main); font-size: 1.5rem; outline: none;">
                    </div>
                    <div>
                        <label class="font-ui text-gray" style="font-size: 0.8rem;">Kembalian</label><br>
                        <div class="font-bank pos-payment-change" id="display-change">Rp 0</div>
                    </div>
                </div>

                <div id="transfer-panel" style="margin-top: 1.5rem; border-top: 1px dashed var(--color-accent); padding-top: 1.5rem; display: none; gap: 1.5rem;">
                    <div>
                        <label class="font-ui text-gray" style="font-size: 0.8rem;">Rekening Tujuan</label><br>
                        <select name="bank_id" id="bank_id" style="width: 100%; padding: 0.5rem; font-family: var(--font-ui);">
                            <option value="">-- Pilih Bank --</option>
                            @foreach($bankAccounts as $account)
                            <option value="{{ $account->id }}">{{ $account->bank_name }} - {{ $account->account_number }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="font-ui text-gray" style="font-size: 0.8rem;">Bukti Transfer</label><br>
                        <input type="file" name="proof_file" id="proof_file" accept="image/*" style="width: 100%; padding: 0.5rem; margin-top: 0.5rem;">
                    </div>
                </div>
            </div>

            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <a href="{{ route('be.shipments.index') }}" class="btn-neon" style="color: red; border-color: red;">Batal</a>
                <button type="submit" id="btn-submit" class="btn-neon" style="padding: 10px 40px;">Buat Shipment</button>
            </div>

        </form>
    </div>

@endsection

@push('scripts')
<script>
    const originBranchInput = document.querySelector('[name="origin_branch_id"]');
    const destinationBranchSelect = document.querySelector('[name="destination_branch_id"]');
    const weightInput = document.querySelector('input[name="weight"]');
    const serviceTypeSelect = document.getElementById('service_type');
    const displayTotal = document.getElementById('display-total-price');
    const paymentMethod = document.getElementById('payment_method');
    const cashPanel = document.getElementById('cash-panel');
    const transferPanel = document.getElementById('transfer-panel');
    const amountReceived = document.getElementById('amount_received');
    const displayChange = document.getElementById('display-change');
    const btnSubmit = document.getElementById('btn-submit');
    const receiverProvinceSelect = document.getElementById('receiver_province_id');
    const receiverCitySelect = document.getElementById('receiver_city_id');
    const selectedReceiverCityId = @json(old('receiver_city_id'));
    const originCityId = @json($originCityId);
    
    let currentTotal = 0;
    let priceRequestSerial = 0;

    function formatRupiah(num) {
        return 'Rp ' + num.toLocaleString('id-ID');
    }

    function updatePaymentPanel() {
        if (paymentMethod.value === 'cash') {
            cashPanel.style.display = 'grid';
            transferPanel.style.display = 'none';
            const received = parseFloat(amountReceived.value) || 0;
            const change = received - currentTotal;
            displayChange.innerText = formatRupiah(Math.max(0, change));

            if (received < currentTotal && currentTotal > 0) {
                btnSubmit.disabled = true;
                displayChange.style.color = '#b91c1c';
            } else {
                btnSubmit.disabled = currentTotal <= 0;
                displayChange.style.color = '#b0005a';
            }
        } else if (paymentMethod.value === 'transfer') {
            cashPanel.style.display = 'none';
            transferPanel.style.display = 'grid';
            btnSubmit.disabled = currentTotal <= 0;
            amountReceived.value = '';
        } else {
            cashPanel.style.display = 'none';
            transferPanel.style.display = 'none';
            btnSubmit.disabled = currentTotal <= 0;
            amountReceived.value = '';
        }
    }

    async function calculate() {
        const weight = parseFloat(weightInput.value) || 0;

        if (serviceTypeSelect.value === 'KARGO' && weight < 10) {
            alert('Layanan KARGO mewajibkan berat minimum 10 KG. Berat akan disesuaikan otomatis.');
            weightInput.value = 10;
            return calculate();
        }

        if (!originBranchInput.value || !originCityId || !receiverCitySelect.value || weight <= 0) {
            currentTotal = 0;
            displayTotal.innerText = 'Lengkapi tujuan & berat';
            btnSubmit.disabled = true;
            updatePaymentPanel();
            return;
        }

        const serial = ++priceRequestSerial;
        displayTotal.innerText = 'Menghitung ongkir...';
        btnSubmit.disabled = true;

            try {
            const params = new URLSearchParams({
                origin_kota_id: originCityId,
                destination_kota_id: receiverCitySelect.value,
                weight,
                service_type: serviceTypeSelect.value,
            });
            const response = await fetch(`/api/calculate-rate?${params.toString()}`);
            const data = await response.json();

            if (serial !== priceRequestSerial) {
                return;
            }

            if (!response.ok || !data.total_price) {
                currentTotal = 0;
                displayTotal.innerText = data.error || 'Ongkir belum tersedia';
                btnSubmit.disabled = true;
                updatePaymentPanel();
                return;
            }

            currentTotal = Number(data.total_price);
            displayTotal.innerText = formatRupiah(currentTotal);
            updatePaymentPanel();
        } catch (error) {
            if (serial !== priceRequestSerial) {
                return;
            }

            currentTotal = 0;
            displayTotal.innerText = 'Ongkir gagal dimuat';
            btnSubmit.disabled = true;
            updatePaymentPanel();
        }
    }

    function loadReceiverCities(selectedValue = null) {
        const provinceId = receiverProvinceSelect.value;

        if (!provinceId) {
            receiverCitySelect.innerHTML = '<option value="">Pilih Kota...</option>';
            calculate();
            return;
        }

        receiverCitySelect.innerHTML = '<option value="">Loading...</option>';

        fetch(`/api/locations/kota?provinsi_id=${provinceId}`)
            .then(response => response.json())
            .then(cities => {
                receiverCitySelect.innerHTML = '<option value="" disabled selected>Pilih Kota...</option>';
                cities.forEach(city => {
                    const option = document.createElement('option');
                    option.value = city.id;
                    option.textContent = city.name.toUpperCase();
                    if (selectedValue && String(city.id) === String(selectedValue)) {
                        option.selected = true;
                    }
                    receiverCitySelect.appendChild(option);
                });
                calculate();
            })
            .catch(() => {
                receiverCitySelect.innerHTML = '<option value="">Kota gagal dimuat</option>';
                calculate();
            });
    }

    weightInput.addEventListener('input', calculate);
    serviceTypeSelect.addEventListener('change', calculate);
    destinationBranchSelect.addEventListener('change', calculate);
    paymentMethod.addEventListener('change', calculate);
    amountReceived.addEventListener('input', calculate);
    receiverProvinceSelect.addEventListener('change', () => loadReceiverCities());
    receiverCitySelect.addEventListener('change', calculate);
    
    // Initial call
    if (receiverProvinceSelect.value) {
        loadReceiverCities(selectedReceiverCityId);
    }
    calculate();
</script>
@endpush
