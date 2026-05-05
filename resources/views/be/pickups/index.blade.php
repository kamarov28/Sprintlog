@extends('be.layouts.main')

@section('header_title', 'Antrean Pickup')

@section('content')

    <div class="hud-panel mb-4">
        <div class="role-brief">
            <div>
                <div class="font-ui text-gray" style="font-size: 0.78rem;">{{ $pickups->total() }} request pickup</div>
                <h3 class="font-bank text-main" style="font-size: clamp(1.45rem, 2.8vw, 2.1rem); margin-top: 0.35rem;">Kerjakan dari aksi berikutnya</h3>
                <p class="font-ui text-gray" style="font-size: 0.86rem; line-height: 1.65; margin: 0.7rem 0 0;">
                    Urutan normal: assign courier, pickup lapangan, terima di hub, verifikasi payment, lalu aktifkan shipment.
                </p>
            </div>
        </div>
    </div>

    <div class="hud-panel">
        <div class="table-responsive">
            <table style="min-width: 1220px;" class="app-table pickup-queue-table">
            <thead>
                <tr style="border-bottom: 2px solid var(--color-panel-border); font-size: 0.85rem; color: var(--color-gray);">
                    <th style="padding: 1rem 0.5rem;">CUSTOMER</th>
                    <th style="padding: 1rem 0.5rem;">ADDRESS</th>
                    <th style="padding: 1rem 0.5rem;">SCHEDULE</th>
                    <th style="padding: 1rem 0.5rem;">STATUS</th>
                    <th style="padding: 1rem 0.5rem;">PAYMENT</th>
                    <th style="padding: 1rem 0.5rem;">ASSIGNED UNIT</th>
                    <th style="padding: 1rem 0.5rem;">NEXT</th>
                    <th style="padding: 1rem 0.5rem; text-align: center;">ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pickups as $pickup)
                @php
                    $nextAction = $pickup->nextActionHint(auth()->user()->role);
                @endphp
                <tr style="border-bottom: 1px solid var(--color-panel-border); font-size: 0.9rem;">
                    <td style="padding: 1.2rem 0.5rem;">
                        <div style="font-weight: bold;">{{ $pickup->customer_name }}</div>
                        <div style="font-size: 0.75rem; color: var(--color-gray);">{{ $pickup->customer_phone }}</div>
                    </td>
                    <td style="padding: 1.2rem 0.5rem;">
                        <div style="font-size: 0.75rem; color: var(--color-primary); margin-bottom: 3px;">LOKASI JEMPUT</div>
                        <div style="font-size: 0.8rem; max-width: 250px; margin-bottom: 0.8rem;">{{ $pickup->pickup_address }}</div>
                        
                        @if($pickup->receiver_address)
                        <div style="font-size: 0.75rem; color: var(--color-accent); margin-bottom: 3px;">LOKASI ANTAR</div>
                        <div style="font-size: 0.8rem; max-width: 250px;">{{ $pickup->receiver_address }}</div>
                        @endif
                    </td>
                    <td style="padding: 1.2rem 0.5rem;">
                        <span class="text-accent">{{ $pickup->pickup_date }}</span>
                    </td>
                    <td style="padding: 1.2rem 0.5rem;">
                        @php
                            $pColors = ['pending' => 'var(--color-gray)', 'assigned' => 'var(--color-primary)', 'picked_up' => '#00ff00', 'hub_received' => '#00ccff', 'cancelled' => 'red'];
                            $pColor = $pColors[$pickup->status] ?? 'var(--color-text-main)';
                        @endphp
                        <span class="status-chip" style="color: {{ $pColor }};">{{ strtoupper($pickup->status) }}</span>
                    </td>
                    <td style="padding: 1.2rem 0.5rem;">
                        @php
                            $paymentColors = [
                                'awaiting_pickup_cash' => '#f57f17',
                                'cash_collected_by_courier' => '#ff9800',
                                'pending_transfer_verification' => '#00ccff',
                                'transfer_rejected' => 'red',
                                'paid' => '#2e7d32',
                                'pending' => 'var(--color-gray)',
                            ];
                            $paymentColor = $paymentColors[$pickup->payment_status] ?? 'var(--color-text-main)';
                        @endphp
                        <div style="font-size: 0.75rem; color: var(--color-gray); margin-bottom: 0.35rem;">{{ strtoupper((string) $pickup->payment_method) }}</div>
                        <div style="font-weight: bold; color: {{ $paymentColor }}; font-size: 0.75rem;">{{ strtoupper((string) $pickup->payment_status) }}</div>
                        @if($pickup->cash_received_amount)
                            <div style="font-size: 0.72rem; color: var(--color-text-main); margin-top: 0.35rem;">
                                Rp {{ number_format((float) $pickup->cash_received_amount, 0, ',', '.') }}
                            </div>
                        @endif
                        @if($pickup->cashCollector)
                            <div style="font-size: 0.68rem; color: var(--color-gray); margin-top: 0.2rem;">
                                Kolektor: {{ $pickup->cashCollector->name }}
                            </div>
                        @endif
                        @if($pickup->latestStatusAudit)
                            <div style="font-size: 0.65rem; color: var(--color-gray); margin-top: 0.4rem;">
                                Audit: {{ strtoupper($pickup->latestStatusAudit->event) }}
                            </div>
                        @endif
                    </td>
                    <td style="padding: 1.2rem 0.5rem;">
                        @if(auth()->user()->role === 'courier')
                            <span class="font-ui text-primary">TUGAS ANDA</span>
                        @elseif($pickup->shipment)
                            <div class="font-ui text-gray" style="font-size: 0.68rem; line-height: 1.35;">Pickup: {{ $pickup->courier?->name ?? '-' }}</div>
                            <div class="font-ui {{ $pickup->shipment->courier ? 'text-primary' : 'text-gray' }}" style="font-size: 0.78rem; line-height: 1.35; margin-top: 0.25rem;">
                                Shipment: {{ $pickup->shipment->courier?->name ?? 'belum ditugaskan' }}
                            </div>
                        @elseif($pickup->courier)
                            <span class="font-ui text-primary">{{ $pickup->courier->name }}</span>
                        @elseif(auth()->user()->role === 'manager')
                            <form action="{{ route('be.pickups.assign', $pickup) }}" method="POST" class="pickup-assign-form">
                                @csrf
                                <select name="courier_id" required class="pickup-assign-select">
                                    <option value="" disabled selected>Select Courier...</option>
                                    @foreach($couriers as $courier)
                                        <option value="{{ $courier->id }}">{{ $courier->name }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn-neon pickup-assign-button">ASSIGN</button>
                            </form>
                        @else
                            <span class="font-ui text-gray" style="font-size: 0.72rem;">Tunggu manager assign</span>
                        @endif
                    </td>
                    <td style="padding: 1.2rem 0.5rem;">
                        <x-be.badge :variant="$nextAction['tone']">{{ $nextAction['label'] }}</x-be.badge>
                        <div class="font-ui text-gray" style="font-size: 0.72rem; line-height: 1.45; margin-top: 0.45rem; max-width: 220px;">
                            {{ $nextAction['description'] }}
                        </div>
                    </td>
                    <td style="padding: 1.2rem 0.5rem; text-align: center;">
                        @if(auth()->user()->role === 'courier')
                            @if($pickup->status === 'assigned')
                                <div style="display: flex; flex-direction: column; gap: 8px; align-items: center;">
                                    @php
                                        $waNumber = preg_replace('/[^0-9]/', '', $pickup->customer_phone);
                                        // if missing leading country code, prepend it
                                        if (str_starts_with($waNumber, '0')) {
                                            $waNumber = '62' . substr($waNumber, 1);
                                        }
                                        $pickupMapDestination = ($pickup->latitude && $pickup->longitude)
                                            ? $pickup->latitude . ',' . $pickup->longitude
                                            : $pickup->pickup_address;
                                    @endphp
                                    <a href="https://wa.me/{{ $waNumber }}" target="_blank" class="btn-neon" style="font-size: 0.65rem; padding: 6px 12px; border-color: #25D366; color: #25D366; text-decoration: none; width: 100%; text-align: center; letter-spacing: 0.04em;">CHAT WA PELANGGAN</a>
                                    <a href="https://www.google.com/maps/dir/?api=1&destination={{ urlencode($pickupMapDestination) }}&travelmode=driving" target="_blank" class="btn-neon" style="font-size: 0.65rem; padding: 6px 12px; border-color: #4285F4; color: #4285F4; text-decoration: none; width: 100%; text-align: center; letter-spacing: 0.04em;">RUTE KE PICKUP</a>
                                    {{-- Proof of pickup upload form --}}
                                    <form action="{{ route('be.pickups.status', $pickup) }}" method="POST" enctype="multipart/form-data" style="width: 100%; margin-top: 4px;">
                                        @csrf
                                        <input type="hidden" name="status" value="picked_up">
                                        <label style="display: block; font-size: 0.65rem; color: var(--color-gray); margin-bottom: 4px; text-align: left;">FOTO BUKTI AMBIL *</label>
                                        <input type="file" name="proof_image" accept="image/*" capture="environment" required
                                            style="font-size: 0.65rem; color: var(--color-text-main); background: transparent; border: 1px solid var(--color-panel-border); padding: 4px; width: 100%; box-sizing: border-box; margin-bottom: 6px;">
                                        @if($pickup->payment_method === 'cash_on_pickup')
                                            <label style="display: block; font-size: 0.65rem; color: var(--color-gray); margin-bottom: 4px; text-align: left;">UANG DITERIMA DARI PENGIRIM *</label>
                                            <input type="number" name="cash_received_amount" min="0" step="0.01" required
                                                style="font-size: 0.7rem; color: var(--color-text-main); background: transparent; border: 1px solid var(--color-panel-border); padding: 6px; width: 100%; box-sizing: border-box; margin-bottom: 6px;"
                                                placeholder="Masukkan nominal tunai">
                                        @endif
                                        <button type="submit" class="btn-neon" style="font-size: 0.65rem; padding: 6px 12px; border-color: #ffaa00; color: #ffaa00; width: 100%; text-align: center; letter-spacing: 0.04em; background: transparent; cursor: pointer;">KONFIRMASI DIAMBIL</button>
                                    </form>
                                </div>
                            @elseif($pickup->status === 'picked_up')
                                @php
                                    $hub = $pickup->branch ?: auth()->user()->branch;
                                    $hubMapDestination = null;
                                    if ($hub) {
                                        $hubMapDestination = ($hub->latitude && $hub->longitude)
                                            ? $hub->latitude . ',' . $hub->longitude
                                            : trim($hub->address ?: ($hub->name . ' ' . $hub->city));
                                    }
                                @endphp
                                <div style="display: flex; flex-direction: column; gap: 8px; align-items: center;">
                                    @if($hubMapDestination)
                                        <a href="https://www.google.com/maps/dir/?api=1&destination={{ urlencode($hubMapDestination) }}&travelmode=driving" target="_blank" class="btn-neon" style="font-size: 0.65rem; padding: 6px 12px; border-color: #4285F4; color: #4285F4; text-decoration: none; width: 100%; text-align: center; letter-spacing: 0.04em;">RUTE KE HUB</a>
                                    @endif
                                    <span class="text-accent" style="font-size: 0.75rem; text-align: center; display: block;">DIBAWA KE HUB</span>
                                </div>
                            @elseif($pickup->status === 'hub_received')
                                <span style="font-size: 0.75rem; color: #00ccff; display: block;">SUDAH DI HUB</span>
                            @endif
                        @else
                            {{-- Manager / Cashier actions --}}
                            <div style="display: flex; flex-direction: column; gap: 6px; align-items: center;">
                                <a href="{{ route('be.pickups.show', $pickup) }}" class="btn-neon" style="font-size: 0.65rem; padding: 4px 10px; border-color: var(--color-primary); color: var(--color-primary); text-decoration: none; width: 100%; text-align: center;">DETAIL</a>

                                {{-- Proof thumbnail --}}
                                @if($pickup->proof_of_pickup)
                                    <a href="{{ Storage::url($pickup->proof_of_pickup) }}" target="_blank" class="btn-neon" style="font-size: 0.65rem; padding: 4px 10px; border-color: #aaa; color: #aaa; text-decoration: none; width: 100%; text-align: center;">LIHAT BUKTI FOTO</a>
                                @endif

                                @if($pickup->payment_method === 'cash_on_pickup' && $pickup->payment_status === 'cash_collected_by_courier')
                                    <form action="{{ route('be.pickups.payment', $pickup) }}" method="POST" style="width: 100%;">
                                        @csrf
                                        <label style="display: block; font-size: 0.65rem; color: var(--color-gray); margin-bottom: 4px; text-align: left;">VERIFIKASI SETOR KAS *</label>
                                        <input type="number" name="verified_cash_amount" min="0" step="0.01" required
                                            style="font-size: 0.7rem; color: var(--color-text-main); background: transparent; border: 1px solid var(--color-panel-border); padding: 6px; width: 100%; box-sizing: border-box; margin-bottom: 6px;"
                                            value="{{ $pickup->cash_received_amount }}">
                                        <button type="submit" class="btn-neon" style="font-size: 0.65rem; padding: 6px 12px; border-color: #00ff00; color: #00ff00; width: 100%; background: transparent; cursor: pointer; letter-spacing: 0.04em;">VERIFIKASI SETOR</button>
                                    </form>
                                @endif

                                @if($pickup->payment_method === 'transfer' && $pickup->payment_proof)
                                    <a href="{{ Storage::url($pickup->payment_proof) }}" target="_blank" class="btn-neon" style="font-size: 0.65rem; padding: 4px 10px; border-color: #00ccff; color: #00ccff; text-decoration: none; width: 100%; text-align: center;">LIHAT BUKTI TRANSFER</a>
                                    @if($pickup->payment_status === 'pending_transfer_verification')
                                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 6px; width: 100%;">
                                            <form action="{{ route('be.pickups.transfer', $pickup) }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="decision" value="approve">
                                                <button type="submit" class="btn-neon" style="font-size: 0.62rem; padding: 6px 8px; border-color: #00ff00; color: #00ff00; width: 100%; background: transparent; cursor: pointer;">OK</button>
                                            </form>
                                            <form action="{{ route('be.pickups.transfer', $pickup) }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="decision" value="reject">
                                                <button type="submit" class="btn-neon" style="font-size: 0.62rem; padding: 6px 8px; border-color: red; color: red; width: 100%; background: transparent; cursor: pointer;">REJECT</button>
                                            </form>
                                        </div>
                                    @endif
                                @endif

                                @if($pickup->status === 'picked_up')
                                    <form action="{{ route('be.pickups.status', $pickup) }}" method="POST" style="width: 100%;">
                                        @csrf
                                        <input type="hidden" name="status" value="hub_received">
                                        <button type="submit" class="btn-neon" style="font-size: 0.65rem; padding: 6px 12px; border-color: #00ccff; color: #00ccff; width: 100%; background: transparent; cursor: pointer; letter-spacing: 0.04em;">TERIMA DI HUB</button>
                                    </form>
                                @elseif($pickup->status === 'hub_received')
                                    @if($pickup->shipment)
                                        <a href="{{ route('be.shipments.show', $pickup->shipment) }}" class="btn-neon" style="font-size: 0.65rem; padding: 6px 12px; border-color: #00ff00; color: #00ff00; text-decoration: none; width: 100%; text-align: center; letter-spacing: 0.04em;">LIHAT SHIPMENT</a>
                                    @elseif($pickup->weight && $pickup->service_type && $pickup->total_price && $pickup->sender_city_id && $pickup->receiver_city_id && in_array($pickup->payment_status, ['paid']))
                                        <form action="{{ route('be.pickups.activate-shipment', $pickup) }}" method="POST" style="width: 100%;">
                                            @csrf
                                            <button type="submit" class="btn-neon" style="font-size: 0.65rem; padding: 6px 12px; border-color: #00ff00; color: #00ff00; width: 100%; background: transparent; cursor: pointer; letter-spacing: 0.04em;">AKTIFKAN SHIPMENT</button>
                                        </form>
                                    @else
                                        <span style="font-size: 0.68rem; color: var(--color-gray); display: block;">MENUNGGU DATA / PAYMENT CLEAR</span>
                                    @endif
                                    <a href="{{ route('be.pickups.receipt', $pickup) }}" target="_blank" class="btn-neon" style="font-size: 0.65rem; padding: 6px 12px; border-color: #00ff00; color: #00ff00; text-decoration: none; width: 100%; text-align: center; letter-spacing: 0.04em;">CETAK RESI</a>
                                @elseif($pickup->status === 'pending')
                                    <span class="text-gray" style="font-size: 0.7rem;">Belum ada aksi lanjutan</span>
                                @else
                                    <span class="text-accent" style="font-size: 0.7rem;">Sedang diproses</span>
                                @endif
                            </div>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="table-empty">Belum ada request pickup</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        </div>

        <x-be.pagination :paginator="$pickups" />
    </div>

@endsection
