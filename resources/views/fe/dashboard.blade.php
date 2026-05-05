@extends('fe.layouts.main')

@section('content')
<div style="margin-top: 5rem;">
    <x-fe.page-header title="Dashboard" subtitle="Halo {{ $user->name }}, semua pickup dan pengiriman kamu ada di sini.">
        <x-fe.button href="{{ route('order.create') }}" variant="primary" style="font-size: 0.8rem;">Buat Order</x-fe.button>
        <x-fe.button href="{{ route('profile') }}" variant="secondary" style="font-size: 0.8rem;">Profil</x-fe.button>
    </x-fe.page-header>

    @if(session('success'))
        <x-fe.alert tone="success" title="Berhasil">
            <p>{{ session('success') }}</p>
            <p style="margin-top: 0.55rem;">Dashboard ini akan menampilkan update pickup, pembayaran, dan aktivasi shipment.</p>
        </x-fe.alert>
    @endif

    @if($errors->any())
        <x-fe.alert tone="danger" title="Ada yang perlu dicek">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </x-fe.alert>
    @endif

    <div class="grid-4 section-animate" style="gap: 1rem; margin-bottom: 2rem;">
        <div class="metric-card">
            <div class="data-label">Total Order</div>
            <div class="metric-value">{{ $summary['total_orders'] }}</div>
        </div>
        <div class="metric-card metric-card--accent">
            <div class="data-label">Menunggu Bayar</div>
            <div class="metric-value">{{ $summary['waiting_payment'] }}</div>
        </div>
        <div class="metric-card">
            <div class="data-label">Paket Aktif</div>
            <div class="metric-value">{{ $summary['active_shipments'] }}</div>
        </div>
        <div class="metric-card metric-card--accent">
            <div class="data-label">Terkirim</div>
            <div class="metric-value">{{ $summary['delivered'] }}</div>
        </div>
    </div>

    <x-fe.panel title="Perjalanan Order" subtitle="Pickup, pembayaran, dan shipment dalam satu alur" variant="accent">
        @forelse($orderLifecycles as $order)
            @php
                $mainStatus = $order['shipment_status'] ?: $order['pickup_status'];
                $statusClass = match ($mainStatus) {
                    'delivered', 'hub_received' => 'status-chip--success',
                    'cancelled', 'failed', 'transfer_rejected' => 'status-chip--danger',
                    'in_transit', 'arrived_at_branch', 'out_for_delivery' => 'status-chip--accent',
                    default => 'status-chip--waiting',
                };
                $paymentClass = match ($order['payment_status']) {
                    'paid' => 'status-chip--success',
                    'transfer_rejected', 'failed' => 'status-chip--danger',
                    'pending_transfer_verification', 'pending_verification', 'cash_collected_by_courier' => 'status-chip--accent',
                    default => 'status-chip--waiting',
                };
                $steps = [
                    'queued' => 'ORDER_QUEUED',
                    'assigned' => 'COURIER_ASSIGNED',
                    'picked_up' => 'PICKED_UP',
                    'hub_received' => 'AT_HUB',
                    'shipment_active' => 'SHIPMENT_ACTIVE',
                    'in_transit' => 'IN_TRANSIT',
                    'delivered' => 'DELIVERED',
                ];
                $nextAction = match (true) {
                    !$order['has_shipment'] && $order['payment_status'] === 'awaiting_pickup_cash' => 'Siapkan uang tunai untuk pickup. Kasir hub akan verifikasi setelah kurir menyetor cash.',
                    !$order['has_shipment'] && in_array($order['payment_status'], ['pending_transfer_verification', 'pending_verification'], true) => 'Bukti transfer sedang menunggu review kasir hub.',
                    !$order['has_shipment'] && $order['payment_status'] === 'cash_collected_by_courier' => 'Kurir sudah menerima cash. Menunggu kasir hub verifikasi setoran.',
                    !$order['has_shipment'] => 'Menunggu pickup kurir dan aktivasi shipment dari hub.',
                    $order['shipment_status'] === 'pending' => 'Shipment sudah aktif dan menunggu pergerakan pertama.',
                    in_array($order['shipment_status'], ['picked_up', 'in_transit'], true) => 'Paket sedang bergerak di jalur hub. Cek tracking untuk scan berikutnya.',
                    $order['shipment_status'] === 'arrived_at_branch' => 'Paket sudah tiba di hub tujuan dan menunggu delivery terakhir.',
                    $order['shipment_status'] === 'out_for_delivery' => 'Kurir sedang menuju penerima. Pastikan nomor penerima aktif.',
                    $order['shipment_status'] === 'delivered' => 'Pengiriman selesai. Timeline tersedia di halaman tracking.',
                    default => 'Hub sedang memproses langkah operasional berikutnya.',
                };
            @endphp

            <div class="ops-card" style="margin-bottom: 1.25rem;">
                <div class="ops-card__topline">
                    <span class="ops-id {{ $order['has_shipment'] ? 'text-accent' : 'text-primary' }}">#{{ $order['reference'] }}</span>
                    <span class="status-chip {{ $statusClass }}">{{ strtoupper((string) $mainStatus) }}</span>
                </div>

                <div class="lifecycle-body" style="display: grid; grid-template-columns: 1.3fr 0.7fr; gap: 1.5rem; margin-top: 1.1rem;">
                    <div>
                        <span class="data-label">Tujuan</span>
                        <p class="data-value">
                            {{ $order['receiver_name'] ?: 'Penerima belum diisi' }} / {{ $order['destination'] ?: 'Tujuan belum diisi' }}
                        </p>
                        @if($order['pickup_address'])
                            <div class="ops-divider">
                                <span class="data-label">Alamat Pickup</span>
                                <p class="data-value">{{ $order['pickup_address'] }}</p>
                            </div>
                        @endif
                    </div>
                    <div>
                        <span class="data-label">Pembayaran</span>
                        <div style="display: flex; flex-direction: column; gap: 0.5rem; margin-top: 0.45rem;">
                            <span class="status-chip {{ $paymentClass }}">{{ strtoupper((string) $order['payment_status'] ?: 'UNSET') }}</span>
                            <span class="text-gray" style="font-size: 0.75rem;">{{ strtoupper((string) $order['payment_method'] ?: 'Belum dipilih') }}</span>
                            @if($order['total_price'])
                                <span class="money-line">Rp {{ number_format((float) $order['total_price'], 0, ',', '.') }}</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="ops-divider">
                    <div class="lifecycle-steps" style="display: grid; grid-template-columns: repeat(7, minmax(74px, 1fr)); gap: 0.65rem;">
                        @foreach($steps as $key => $label)
                            <div style="border: 2px solid {{ $order['steps'][$key] ? 'var(--color-panel-border)' : 'var(--color-panel-border)' }}; border-radius: 8px; background: {{ $order['steps'][$key] ? 'var(--color-primary)' : 'var(--color-panel)' }}; padding: 0.55rem 0.45rem; text-align: center;">
                                <div style="color: {{ $order['steps'][$key] ? 'var(--color-text-main)' : 'var(--color-gray)' }}; font-size: 0.9rem; font-weight: 800;">{{ $order['steps'][$key] ? 'OK' : '-' }}</div>
                                <div class="text-gray" style="font-size: 0.62rem; margin-top: 0.3rem;">{{ str_replace('_', ' ', $label) }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="ops-divider" style="display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap; align-items: center;">
                    <div>
                        @if($order['latest_tracking'])
                            <span class="data-label">Tracking Terakhir</span>
                            <p class="data-value">{{ strtoupper($order['latest_tracking']->status) }} / {{ $order['latest_tracking']->location }} / {{ $order['latest_tracking']->tracked_at->format('d M H:i') }}</p>
                        @else
                            <span class="data-label">Update Terakhir</span>
                            <p class="data-value">{{ $order['has_shipment'] ? 'Shipment aktif, menunggu update tracking berikutnya.' : 'Hub akan mengaktifkan shipment setelah pickup dan payment diverifikasi.' }}</p>
                        @endif
                    </div>

                    @if($order['has_shipment'])
                        <a href="{{ route('track.show') }}?receipt={{ urlencode($order['reference']) }}" class="btn-neon" style="text-decoration: none; font-size: 0.72rem;">Lacak Paket</a>
                    @else
                        <span class="text-gray" style="font-size: 0.72rem;">Nomor resi belum aktif</span>
                    @endif
                </div>

                <div class="next-action-box">
                    <span class="data-label">Langkah Berikutnya</span>
                    <div class="text-main" style="font-size: 0.84rem;">{{ $nextAction }}</div>
                </div>

                @if($order['can_reschedule'] || $order['can_cancel'] || $order['can_reupload_payment'])
                    <div class="customer-action-panel">
                        <span class="data-label">Aksi Tersedia</span>

                        @if($order['can_reupload_payment'])
                            <form action="{{ route('order.payment-proof', $order['id']) }}" method="POST" enctype="multipart/form-data" class="customer-action-form">
                                @csrf
                                <label class="text-gray" for="payment_proof_{{ $order['id'] }}">Upload ulang bukti transfer</label>
                                <input type="file" id="payment_proof_{{ $order['id'] }}" name="payment_proof" accept="image/*" required>
                                <button type="submit" class="btn-neon">Kirim Review</button>
                            </form>
                        @endif

                        @if($order['can_reschedule'])
                            <form action="{{ route('order.reschedule', $order['id']) }}" method="POST" class="customer-action-form customer-action-form--inline">
                                @csrf
                                <label class="text-gray" for="pickup_date_{{ $order['id'] }}">Jadwal ulang pickup</label>
                                <input type="date" id="pickup_date_{{ $order['id'] }}" name="pickup_date" value="{{ $order['pickup_date'] }}" min="{{ now()->format('Y-m-d') }}" required>
                                <button type="submit" class="btn-neon">Update Tanggal</button>
                            </form>
                        @endif

                        @if($order['can_cancel'])
                            <form action="{{ route('order.cancel', $order['id']) }}" method="POST" onsubmit="return confirm('Cancel this pickup request before courier assignment?');" class="customer-action-form customer-action-form--danger">
                                @csrf
                                <button type="submit" class="btn-secondary">Batalkan Request</button>
                                <span class="text-gray">Bisa dilakukan sebelum kurir ditugaskan.</span>
                            </form>
                        @endif
                    </div>
                @endif
            </div>
        @empty
            <div class="empty-state">
                <p class="text-gray" style="font-size: 0.95rem;">Belum ada pengiriman aktif.</p>
                <p class="text-gray" style="font-size: 0.82rem; margin-top: 0.75rem;">Buat pickup pertama, lalu progres pembayaran dan pengiriman akan tampil di sini.</p>
                <div style="margin-top: 1.5rem;">
                    <x-fe.button href="{{ route('order.create') }}" variant="primary">Buat Order Pertama</x-fe.button>
                </div>
            </div>
        @endforelse
    </x-fe.panel>
</div>
@endsection
