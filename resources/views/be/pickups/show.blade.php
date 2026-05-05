@extends('be.layouts.main')

@section('header_title', 'Detail Pickup: SPL-' . str_pad($pickup->id, 6, '0', STR_PAD_LEFT))

@section('content')
    @php
        $statusColors = [
            'pending' => 'var(--color-gray)',
            'assigned' => 'var(--color-primary)',
            'picked_up' => '#00ff00',
            'hub_received' => '#00ccff',
            'cancelled' => 'red',
        ];
        $paymentColors = [
            'awaiting_pickup_cash' => '#f57f17',
            'cash_collected_by_courier' => '#ff9800',
            'pending_transfer_verification' => '#00ccff',
            'transfer_rejected' => 'red',
            'paid' => '#2e7d32',
            'pending' => 'var(--color-gray)',
        ];
        $canManage = in_array(auth()->user()->role, ['manager', 'cashier'], true);
        $canActivate = $canManage
            && $pickup->status === 'hub_received'
            && !$pickup->shipment
            && $pickup->weight
            && $pickup->service_type
            && $pickup->total_price
            && $pickup->sender_city_id
            && $pickup->receiver_city_id
            && $pickup->payment_status === 'paid';
        $nextAction = $pickup->nextActionHint(auth()->user()->role);
    @endphp

    <div class="hud-panel surface-toolbar mb-4">
        <div>
            <div class="font-ui text-gray" style="font-size: 0.78rem;">PICKUP REQUEST</div>
            <div class="font-bank text-primary" style="font-size: 1.35rem;">SPL-{{ str_pad($pickup->id, 6, '0', STR_PAD_LEFT) }}</div>
        </div>
        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
            <a href="{{ route('be.pickups.index') }}" class="btn-neon" style="text-decoration: none;">Kembali</a>
            <a href="{{ route('be.pickups.receipt', $pickup) }}" target="_blank" class="btn-neon" style="text-decoration: none; border-color: #00ff00; color: #00ff00;">Cetak Resi</a>
            @if($pickup->shipment)
                <a href="{{ route('be.shipments.show', $pickup->shipment) }}" class="btn-neon" style="text-decoration: none; border-color: var(--color-accent); color: var(--color-accent);">Buka Shipment</a>
            @endif
        </div>
    </div>

    <div class="hud-panel mb-4">
        <div class="role-brief">
            <div>
                <div class="font-ui text-gray" style="font-size: 0.78rem;">Langkah berikutnya</div>
                <h3 class="font-bank text-main" style="font-size: clamp(1.35rem, 2.5vw, 1.9rem); margin-top: 0.35rem;">{{ $nextAction['label'] }}</h3>
                <p class="font-ui text-gray" style="font-size: 0.86rem; line-height: 1.65; margin: 0.65rem 0 0;">{{ $nextAction['description'] }}</p>
            </div>
            <div class="role-brief__actions">
                <x-be.badge :variant="$nextAction['tone']">{{ strtoupper(str_replace('_', ' ', $pickup->status)) }}</x-be.badge>
                <x-be.badge :variant="$pickup->payment_status === 'paid' ? 'success' : ($pickup->payment_status === 'transfer_rejected' ? 'danger' : 'accent')">
                    {{ strtoupper(str_replace('_', ' ', (string) $pickup->payment_status)) }}
                </x-be.badge>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: minmax(0, 1.15fr) minmax(320px, 0.85fr); gap: 2rem; align-items: start;">
        <div>
            <div class="hud-panel mb-4">
                <div style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem;">
                    <div class="ops-card">
                        <div class="data-label">Status Pickup</div>
                        <div class="status-chip" style="color: {{ $statusColors[$pickup->status] ?? 'var(--color-text-main)' }};">{{ strtoupper($pickup->status) }}</div>
                    </div>
                    <div class="ops-card">
                        <div class="data-label">Status Payment</div>
                        <div class="status-chip" style="color: {{ $paymentColors[$pickup->payment_status] ?? 'var(--color-text-main)' }};">{{ strtoupper((string) $pickup->payment_status ?: 'UNSET') }}</div>
                    </div>
                    <div class="ops-card">
                        <div class="data-label">Total Ongkir</div>
                        <div class="font-bank text-accent" style="font-size: 1.2rem;">Rp {{ number_format((float) $pickup->total_price, 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>

            <div class="hud-panel mb-4">
                <h3 class="font-bank text-primary mb-3" style="font-size: 1.15rem;">Rute Pickup</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    <div class="form-cluster">
                        <div class="data-label">Pengirim</div>
                        <div class="font-ui text-main" style="font-size: 1.05rem;">{{ $pickup->sender_name ?: $pickup->customer_name }}</div>
                        <div class="font-ui text-gray">{{ $pickup->sender_phone ?: $pickup->customer_phone }}</div>
                        <div class="font-ui text-accent" style="font-size: 0.82rem; margin-top: 0.75rem;">{{ $pickup->senderCity->name ?? 'Kota belum lengkap' }}</div>
                        <div class="font-ui text-gray" style="font-size: 0.82rem; margin-top: 0.35rem;">{{ $pickup->sender_address ?: $pickup->pickup_address }}</div>
                    </div>
                    <div class="form-cluster form-cluster--accent">
                        <div class="data-label">Penerima</div>
                        <div class="font-ui text-main" style="font-size: 1.05rem;">{{ $pickup->receiver_name ?: '-' }}</div>
                        <div class="font-ui text-gray">{{ $pickup->receiver_phone ?: '-' }}</div>
                        <div class="font-ui text-accent" style="font-size: 0.82rem; margin-top: 0.75rem;">{{ $pickup->receiverCity->name ?? 'Kota belum lengkap' }}</div>
                        <div class="font-ui text-gray" style="font-size: 0.82rem; margin-top: 0.35rem;">{{ $pickup->receiver_address ?: '-' }}</div>
                    </div>
                </div>
            </div>

            <div class="hud-panel mb-4">
                <h3 class="font-bank text-accent mb-3" style="font-size: 1.15rem;">Paket dan Payment</h3>
                <div style="display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 1rem;">
                    <div>
                        <div class="data-label">Layanan</div>
                        <div class="font-ui text-main">{{ $pickup->service_type ?: '-' }}</div>
                    </div>
                    <div>
                        <div class="data-label">Berat</div>
                        <div class="font-ui text-main">{{ $pickup->weight ?: '0' }} KG</div>
                    </div>
                    <div>
                        <div class="data-label">Metode Payment</div>
                        <div class="font-ui text-main">{{ strtoupper((string) $pickup->payment_method ?: '-') }}</div>
                    </div>
                    <div>
                        <div class="data-label">HUB</div>
                        <div class="font-ui text-main">{{ $pickup->branch->name ?? 'Belum ditugaskan' }}</div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1.5rem;">
                    <div class="form-cluster">
                        <div class="data-label">Bukti Pickup</div>
                        @if($pickup->proof_of_pickup)
                            <a href="{{ Storage::url($pickup->proof_of_pickup) }}" target="_blank" class="btn-neon" style="display: inline-block; text-decoration: none; margin-top: 0.5rem;">Lihat Foto</a>
                        @else
                            <div class="font-ui text-gray">Belum ada bukti pickup</div>
                        @endif
                    </div>
                    <div class="form-cluster form-cluster--accent">
                        <div class="data-label">Bukti Payment / Tunai</div>
                        @if($pickup->payment_method === 'transfer' && $pickup->payment_proof)
                            <a href="{{ Storage::url($pickup->payment_proof) }}" target="_blank" class="btn-neon" style="display: inline-block; text-decoration: none; margin-top: 0.5rem; border-color: #00ccff; color: #00ccff;">Lihat Transfer</a>
                        @elseif($pickup->payment_method === 'cash_on_pickup')
                            <div class="font-bank text-accent" style="font-size: 1.2rem;">Rp {{ number_format((float) $pickup->cash_received_amount, 0, ',', '.') }}</div>
                            <div class="font-ui text-gray" style="font-size: 0.75rem;">Collector: {{ $pickup->cashCollector->name ?? '-' }}</div>
                            <div class="font-ui text-gray" style="font-size: 0.75rem;">Verifier: {{ $pickup->cashVerifier->name ?? '-' }}</div>
                        @else
                            <div class="font-ui text-gray">Belum ada bukti payment</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="hud-panel">
                <h3 class="font-bank text-main mb-4" style="font-size: 1.15rem;">Riwayat Pickup</h3>
                <div style="border-left: 2px dashed var(--color-panel-border); padding-left: 1.5rem;">
                    @forelse($pickup->statusAudits as $audit)
                        <div style="margin-bottom: 1.4rem; position: relative;">
                            <div style="position: absolute; left: -1.86rem; top: 0.15rem; width: 10px; height: 10px; border-radius: 50%; background: var(--color-primary); border: 2px solid var(--color-bg);"></div>
                            <div class="font-ui text-accent" style="font-size: 0.75rem;">{{ $audit->created_at->format('d/m/Y H:i') }} / {{ $audit->user->name ?? 'SYSTEM' }}</div>
                            <div class="font-ui text-main" style="font-weight: bold;">{{ strtoupper($audit->event) }}</div>
                            @if($audit->from_status || $audit->to_status)
                                <div class="font-ui text-gray" style="font-size: 0.78rem;">STATUS: {{ strtoupper($audit->from_status ?? '-') }} -> {{ strtoupper($audit->to_status ?? '-') }}</div>
                            @endif
                            @if($audit->from_payment_status || $audit->to_payment_status)
                                <div class="font-ui text-gray" style="font-size: 0.78rem;">PAYMENT: {{ strtoupper($audit->from_payment_status ?? '-') }} -> {{ strtoupper($audit->to_payment_status ?? '-') }}</div>
                            @endif
                            @if($audit->description)
                                <div class="font-ui text-gray" style="font-size: 0.8rem; margin-top: 0.25rem;">{{ $audit->description }}</div>
                            @endif
                        </div>
                    @empty
                        <div class="table-empty">Belum ada riwayat audit</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div>
            <div class="hud-panel mb-4">
                <h3 class="font-bank text-primary mb-4" style="font-size: 1.15rem;">AKSI PICKUP</h3>

                @if($canManage && !$pickup->courier)
                    <form action="{{ route('be.pickups.assign', $pickup) }}" method="POST" class="form-cluster mb-3">
                        @csrf
                        <div class="data-label">Assign Kurir Pickup</div>
                        <select name="courier_id" required style="width: 100%; padding: 0.55rem; font-family: var(--font-ui); margin: 0.5rem 0;">
                            <option value="" disabled selected>Pilih kurir...</option>
                            @foreach($couriers as $courier)
                                <option value="{{ $courier->id }}">{{ $courier->name }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn-neon" style="width: 100%; background: transparent;">Assign Kurir</button>
                    </form>
                @endif

                @if(auth()->user()->role === 'courier' && $pickup->status === 'assigned')
                    <form action="{{ route('be.pickups.status', $pickup) }}" method="POST" enctype="multipart/form-data" class="form-cluster mb-3">
                        @csrf
                        <input type="hidden" name="status" value="picked_up">
                        <div class="data-label">Konfirmasi Pickup</div>
                        <input type="file" name="proof_image" accept="image/*" capture="environment" required style="width: 100%; margin: 0.75rem 0;">
                        @if($pickup->payment_method === 'cash_on_pickup')
                            <input type="number" name="cash_received_amount" min="0" step="0.01" required placeholder="Nominal tunai diterima" style="width: 100%; padding: 0.55rem; margin-bottom: 0.75rem;">
                        @endif
                        <button type="submit" class="btn-neon" style="width: 100%; background: transparent; border-color: #ffaa00; color: #ffaa00;">Konfirmasi Diambil</button>
                    </form>
                @endif

                @if($canManage && $pickup->payment_method === 'cash_on_pickup' && $pickup->payment_status === 'cash_collected_by_courier')
                    <form action="{{ route('be.pickups.payment', $pickup) }}" method="POST" class="form-cluster mb-3">
                        @csrf
                        <div class="data-label">Verifikasi Setoran Kurir</div>
                        <input type="number" name="verified_cash_amount" min="0" step="0.01" required value="{{ $pickup->cash_received_amount }}" style="width: 100%; padding: 0.55rem; margin: 0.75rem 0;">
                        <button type="submit" class="btn-neon" style="width: 100%; background: transparent; border-color: #00ff00; color: #00ff00;">Verifikasi Tunai</button>
                    </form>
                @endif

                @if($canManage && $pickup->payment_method === 'transfer' && $pickup->payment_status === 'pending_transfer_verification' && $pickup->payment_proof)
                    <div class="form-cluster mb-3">
                        <div class="data-label">Verifikasi Transfer</div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-top: 0.75rem;">
                            <form action="{{ route('be.pickups.transfer', $pickup) }}" method="POST">
                                @csrf
                                <input type="hidden" name="decision" value="approve">
                                <button type="submit" class="btn-neon" style="width: 100%; background: transparent; border-color: #00ff00; color: #00ff00;">Approve</button>
                            </form>
                            <form action="{{ route('be.pickups.transfer', $pickup) }}" method="POST">
                                @csrf
                                <input type="hidden" name="decision" value="reject">
                                <button type="submit" class="btn-neon" style="width: 100%; background: transparent; border-color: red; color: red;">Reject</button>
                            </form>
                        </div>
                    </div>
                @endif

                @if($canManage && $pickup->status === 'picked_up')
                    <form action="{{ route('be.pickups.status', $pickup) }}" method="POST" class="form-cluster mb-3">
                        @csrf
                        <input type="hidden" name="status" value="hub_received">
                        <div class="data-label">Terima di Hub</div>
                        <button type="submit" class="btn-neon" style="width: 100%; background: transparent; margin-top: 0.75rem; border-color: #00ccff; color: #00ccff;">Tandai Diterima Hub</button>
                    </form>
                @endif

                @if($canActivate)
                    <form action="{{ route('be.pickups.activate-shipment', $pickup) }}" method="POST" class="form-cluster mb-3">
                        @csrf
                        <div class="data-label">Aktivasi Shipment</div>
                        <button type="submit" class="btn-neon" style="width: 100%; background: transparent; margin-top: 0.75rem; border-color: #00ff00; color: #00ff00;">Aktifkan Shipment</button>
                    </form>
                @elseif($canManage && $pickup->status === 'hub_received' && !$pickup->shipment)
                    <div class="inline-alert">
                        <div class="font-ui text-gray" style="font-size: 0.8rem;">Shipment belum bisa diaktifkan. Pastikan payment sudah `paid`, total fee, service, berat, sender city, dan receiver city lengkap.</div>
                    </div>
                @endif

                @if(!$canManage && !(auth()->user()->role === 'courier' && $pickup->status === 'assigned'))
                    <div class="table-empty">Belum ada aksi untuk status ini</div>
                @endif
            </div>

            <x-be.route-card :estimate="$routeEstimate" />

            <div class="hud-panel">
                <h3 class="font-bank text-accent mb-3" style="font-size: 1.15rem;">Link Lokasi</h3>
                <div style="display: grid; gap: 0.75rem;">
                    @php
                        $pickupMapDestination = ($pickup->latitude && $pickup->longitude)
                            ? $pickup->latitude . ',' . $pickup->longitude
                            : $pickup->pickup_address;
                        $hub = $pickup->branch ?: auth()->user()->branch;
                        $hubMapDestination = null;
                        if ($hub) {
                            $hubMapDestination = ($hub->latitude && $hub->longitude)
                                ? $hub->latitude . ',' . $hub->longitude
                                : trim($hub->address ?: ($hub->name . ' ' . $hub->city));
                        }
                    @endphp
                    <a href="https://www.google.com/maps/dir/?api=1&destination={{ urlencode($pickupMapDestination) }}&travelmode=driving" target="_blank" class="btn-neon" style="text-decoration: none; text-align: center; border-color: #4285F4; color: #4285F4;">Rute ke Pickup</a>
                    @if($hubMapDestination)
                        <a href="https://www.google.com/maps/dir/?api=1&destination={{ urlencode($hubMapDestination) }}&travelmode=driving" target="_blank" class="btn-neon" style="text-decoration: none; text-align: center; border-color: #4285F4; color: #4285F4;">Rute ke Hub</a>
                    @endif

                    @if($pickup->receiver_latitude && $pickup->receiver_longitude)
                        <a href="https://www.google.com/maps/search/?api=1&query={{ $pickup->receiver_latitude }},{{ $pickup->receiver_longitude }}" target="_blank" class="btn-neon" style="text-decoration: none; text-align: center; border-color: var(--color-accent); color: var(--color-accent);">Buka Map Penerima</a>
                    @else
                        <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($pickup->receiver_address) }}" target="_blank" class="btn-neon" style="text-decoration: none; text-align: center; border-color: var(--color-accent); color: var(--color-accent);">Cari Map Penerima</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
