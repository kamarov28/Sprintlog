@extends('be.layouts.main')

@section('header_title', 'Tambah Shipment')

@section('content')

    <div class="hud-panel" style="max-width: 800px; margin: 0 auto;">
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

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
                <!-- Sender Info -->
                <div class="form-cluster">
                    <div class="font-ui text-primary mb-3" style="font-size: 0.8rem;">Data Pengirim</div>
                    <div style="margin-bottom: 1rem;">
                        <label class="font-ui text-gray" style="font-size: 0.8rem;">Nama</label><br>
                        <input type="text" name="sender_name" required style="width: 100%; padding: 0.5rem; border: none; border-bottom: 2px solid var(--color-gray); background: transparent; font-family: var(--font-ui); color: var(--color-text-main); font-size: 1.1rem; outline: none;">
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label class="font-ui text-gray" style="font-size: 0.8rem;">Nomor Telepon</label><br>
                        <input type="text" name="sender_phone" required style="width: 100%; padding: 0.5rem; border: none; border-bottom: 2px solid var(--color-gray); background: transparent; font-family: var(--font-ui); color: var(--color-text-main); font-size: 1.1rem; outline: none;">
                    </div>
                </div>

                <!-- Receiver Info -->
                <div class="form-cluster form-cluster--accent">
                    <div class="font-ui text-accent mb-3" style="font-size: 0.8rem;">Data Penerima</div>
                    <div style="margin-bottom: 1rem;">
                        <label class="font-ui text-gray" style="font-size: 0.8rem;">Nama</label><br>
                        <input type="text" name="receiver_name" required style="width: 100%; padding: 0.5rem; border: none; border-bottom: 2px solid var(--color-gray); background: transparent; font-family: var(--font-ui); color: var(--color-text-main); font-size: 1.1rem; outline: none;">
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label class="font-ui text-gray" style="font-size: 0.8rem;">Nomor Telepon</label><br>
                        <input type="text" name="receiver_phone" required style="width: 100%; padding: 0.5rem; border: none; border-bottom: 2px solid var(--color-gray); background: transparent; font-family: var(--font-ui); color: var(--color-text-main); font-size: 1.1rem; outline: none;">
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label class="font-ui text-gray" style="font-size: 0.8rem;">Alamat Tujuan</label><br>
                        <textarea name="receiver_address" rows="2" placeholder="Alamat lengkap tujuan pengiriman" style="width: 100%; padding: 0.5rem; border: none; border-bottom: 2px solid var(--color-gray); background: transparent; font-family: var(--font-ui); color: var(--color-text-main); font-size: 0.95rem; outline: none; resize: none;"></textarea>
                    </div>
                </div>
            </div>

            <!-- Delivery & Package Config -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
                
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
                                <option value="{{ $courier->id }}">{{ $courier->name }}</option>
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
    const rateMatrix = @json($rates->mapWithKeys(fn ($rate) => [$rate->origin_zone . '-' . $rate->destination_zone => (float) $rate->price_per_kg]));
    const branchZones = @json($branchZones);
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
    
    let currentTotal = 0;

    function formatRupiah(num) {
        return 'Rp ' + num.toLocaleString('id-ID');
    }

    function calculate() {
        const weight = parseFloat(weightInput.value) || 0;
        const selectedOption = serviceTypeSelect.options[serviceTypeSelect.selectedIndex];
        const originBranchId = originBranchInput.value;
        const destinationBranchId = destinationBranchSelect.value;
        const originZone = branchZones[originBranchId] || '';
        const destinationZone = branchZones[destinationBranchId] || '';
        const basePriceRate = rateMatrix[originZone + '-' + destinationZone] || 0;

        // Validasi Kargo
        if (serviceTypeSelect.value === 'KARGO' && weight < 10) {
            alert('Layanan KARGO mewajibkan berat minimum 10 KG. Berat akan disesuaikan otomatis.');
            weightInput.value = 10;
            return calculate(); // Recalculate
        }

        const multiplier = parseFloat(selectedOption.getAttribute('data-multiplier')) || 1.0;
        
        if (!basePriceRate) {
            currentTotal = 0;
            displayTotal.innerText = 'Rate belum tersedia';
            btnSubmit.disabled = true;
            return;
        }

        const pricePerKg = basePriceRate * multiplier;
        currentTotal = weight * pricePerKg;
        
        displayTotal.innerText = formatRupiah(currentTotal);

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
                btnSubmit.disabled = false;
                displayChange.style.color = '#b0005a';
            }
        } else if (paymentMethod.value === 'transfer') {
            cashPanel.style.display = 'none';
            transferPanel.style.display = 'grid';
            btnSubmit.disabled = false;
            amountReceived.value = '';
        } else {
            cashPanel.style.display = 'none';
            transferPanel.style.display = 'none';
            btnSubmit.disabled = false;
            amountReceived.value = '';
        }
    }

    weightInput.addEventListener('input', calculate);
    serviceTypeSelect.addEventListener('change', calculate);
    destinationBranchSelect.addEventListener('change', calculate);
    paymentMethod.addEventListener('change', calculate);
    amountReceived.addEventListener('input', calculate);
    
    // Initial call
    calculate();
</script>
@endpush
