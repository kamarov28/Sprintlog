@extends('be.layouts.main')

@section('header_title', 'Detail Shipment')

@section('content')

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 3rem;">
        
        <!-- Left: Static Details -->
        <div>
            <div class="hud-panel mb-4">
                <h3 class="font-bank text-primary mb-3" style="font-size: 1.2rem;">Pengirim & Penerima</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <div class="font-ui text-gray" style="font-size: 0.75rem;">Pengirim</div>
                        <div class="font-ui" style="font-size: 1rem; color: var(--color-text-main);">{{ $shipment->sender->name }}</div>
                        <div class="font-ui text-gray" style="font-size: 0.8rem;">{{ $shipment->sender->phone }}</div>
                    </div>
                    <div>
                        <div class="font-ui text-gray" style="font-size: 0.75rem;">Penerima</div>
                        <div class="font-ui" style="font-size: 1rem; color: var(--color-text-main);">{{ $shipment->receiver->name }}</div>
                        <div class="font-ui text-gray" style="font-size: 0.8rem;">{{ $shipment->receiver->phone }}</div>
                        @if($shipment->receiver->address)
                            <div class="font-ui" style="font-size: 0.8rem; color: var(--color-accent); margin-top: 4px;">{{ $shipment->receiver->address }}</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="hud-panel mb-4">
                <h3 class="font-bank text-accent mb-3" style="font-size: 1.2rem;">Info Paket</h3>
                <div style="margin-bottom: 1.5rem;">
                    <div class="font-ui text-gray" style="font-size: 0.75rem;">No. Resi</div>
                    <div class="shipment-tracking-code">{{ $shipment->tracking_number }}</div>
                </div>
                <div style="margin-bottom: 1.5rem;">
                        <div class="font-ui text-gray" style="font-size: 0.75rem;">Item</div>
                    @foreach($shipment->items as $item)
                        <div class="font-ui" style="font-size: 1rem; color: var(--color-text-main);">{{ $item->item_name }} ({{ $item->quantity }}x)</div>
                    @endforeach
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <div class="font-ui text-gray" style="font-size: 0.75rem;">Berat</div>
                        <div class="font-ui" style="font-size: 1.2rem; color: var(--color-text-main);">{{ $shipment->total_weight }} KG</div>
                    </div>
                    <div>
                        <div class="font-ui text-gray" style="font-size: 0.75rem;">Total Ongkir</div>
                        <div class="font-ui" style="font-size: 1.2rem; color: var(--color-accent); font-weight: bold;">Rp {{ number_format($shipment->total_price, 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>

            @if($shipment->payment)
            <div class="hud-panel mb-4">
                <div style="display: flex; justify-content: space-between; align-items: start;">
                    <h3 class="font-bank text-primary mb-3" style="font-size: 1.2rem;">Data Pembayaran</h3>
                    @if($shipment->payment->payment_method === 'cash')
                        <a href="{{ route('be.shipments.receipt', $shipment) }}" target="_blank" class="btn-neon" style="font-size: 0.7rem; padding: 5px 15px;">PRINT RESI KASIR</a>
                    @endif
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div>
                        <div class="font-ui text-gray" style="font-size: 0.75rem;">Metode</div>
                        <div class="font-ui" style="font-size: 1.2rem; color: var(--color-text-main);">{{ strtoupper($shipment->payment->payment_method) }}</div>
                    </div>
                    <div>
                        <div class="font-ui text-gray" style="font-size: 0.75rem;">Status</div>
                        <div class="font-ui flex items-center" style="font-size: 1.2rem; color: {{ $shipment->payment->payment_status == 'paid' ? 'var(--color-primary)' : 'var(--color-accent)' }}; font-weight: bold;">{{ strtoupper($shipment->payment->payment_status) }}</div>
                    </div>
                </div>
                @if($shipment->payment->payment_method === 'cash')
                <div style="border-top: 1px dashed var(--color-panel-border); padding-top: 1rem; display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <div class="font-ui text-gray" style="font-size: 0.75rem;">Tunai Diterima</div>
                        <div class="font-ui" style="font-size: 1.2rem; color: var(--color-text-main);">Rp {{ number_format($shipment->payment->amount_received, 0, ',', '.') }}</div>
                    </div>
                    <div>
                        <div class="font-ui text-gray" style="font-size: 0.75rem;">Kembalian</div>
                        <div class="font-ui" style="font-size: 1.2rem; color: var(--color-accent);">Rp {{ number_format($shipment->payment->change_amount, 0, ',', '.') }}</div>
                    </div>
                </div>
                @endif
                @if($shipment->payment->payment_method === 'transfer')
                <div style="border-top: 1px dashed var(--color-panel-border); padding-top: 1rem;">
                    @if($shipment->payment->proof_file)
                        <a href="{{ route('be.payments.proof', $shipment->payment) }}" target="_blank" class="btn-neon" style="font-size: 0.7rem; padding: 5px 15px; display: inline-block; text-decoration: none; margin-bottom: 0.75rem;">LIHAT BUKTI TRANSFER</a>
                    @endif
                    @if(in_array(auth()->user()->role, ['manager', 'cashier']) && $shipment->payment->payment_status === 'pending_verification')
                        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                            <form action="{{ route('be.payments.verify', $shipment->payment) }}" method="POST">
                                @csrf
                                <input type="hidden" name="decision" value="approve">
                                <button type="submit" class="btn-neon" style="font-size: 0.7rem; padding: 5px 15px; border-color: var(--color-primary); color: var(--color-primary); background: transparent;">APPROVE TRANSFER</button>
                            </form>
                            <form action="{{ route('be.payments.verify', $shipment->payment) }}" method="POST">
                                @csrf
                                <input type="hidden" name="decision" value="reject">
                                <button type="submit" class="btn-neon" style="font-size: 0.7rem; padding: 5px 15px; border-color: red; color: red; background: transparent;">REJECT</button>
                            </form>
                        </div>
                    @endif
                </div>
                @endif
            </div>
            @endif

            @if($shipment->legs->isNotEmpty())
            <div class="hud-panel mb-4">
                <h3 class="font-bank text-main mb-3" style="font-size: 1.2rem;">Rute Antar Hub</h3>
                <div style="display: grid; gap: 0.85rem;">
                    @foreach($shipment->legs as $leg)
                        <div style="display: grid; grid-template-columns: 62px minmax(0, 1fr) auto; gap: 0.8rem; align-items: center; border-bottom: 1px solid var(--color-panel-border); padding-bottom: 0.85rem;">
                            <div class="font-ui text-gray" style="font-size: 0.76rem;">LEG {{ str_pad($leg->sequence, 2, '0', STR_PAD_LEFT) }}</div>
                            <div>
                                <div class="font-ui text-main" style="font-size: 0.86rem;">
                                    {{ optional($leg->originBranch)->name ?? '-' }} -> {{ optional($leg->destinationBranch)->name ?? '-' }}
                                </div>
                                <div class="font-ui text-gray" style="font-size: 0.72rem;">
                                    @if($leg->arrived_at)
                                        Arrived {{ $leg->arrived_at->format('d M Y H:i') }}
                                    @elseif($leg->departed_at)
                                        Departed {{ $leg->departed_at->format('d M Y H:i') }}
                                    @else
                                        Awaiting departure scan
                                    @endif
                                    @if($leg->planned_arrival_at)
                                        / ETA {{ $leg->planned_arrival_at->format('d M Y H:i') }}
                                    @endif
                                    @if($leg->distance_km)
                                        <br>{{ number_format((float) $leg->distance_km, 1, ',', '.') }} KM
                                        @if($leg->duration_minutes)
                                            / {{ $leg->duration_minutes }} menit
                                        @endif
                                        @if($leg->routing_provider)
                                            / {{ strtoupper($leg->routing_provider) }}
                                        @endif
                                    @endif
                                    @if($leg->delay_reason)
                                        <br>Delay: {{ $leg->delay_reason }}
                                    @endif
                                </div>
                            </div>
                            <x-be.badge :variant="$leg->status === 'arrived' ? 'success' : ($leg->status === 'departed' ? 'accent' : 'neutral')">
                                {{ strtoupper($leg->status) }}
                            </x-be.badge>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            @if($shipment->photo)
            <div class="hud-panel">
                <h3 class="font-bank text-main mb-3" style="font-size: 1.2rem;">PROOF OF DELIVERY (POD)</h3>
                <img src="{{ asset('storage/' . $shipment->photo) }}" alt="POD" style="width: 100%; border: 1px solid var(--color-panel-border);">
            </div>
            @endif
        </div>

        <!-- Right: Status Update & Timeline -->
        <div>
            @php
                $nextAction = $shipment->nextActionHint(auth()->user()->branch_id, auth()->user()->role);
            @endphp
            <div class="hud-panel mb-4" style="border-color: var(--color-primary);">
                <div class="font-ui text-gray" style="font-size: 0.76rem;">Langkah berikutnya</div>
                <div class="font-bank text-main" style="font-size: 1.4rem; margin-top: 0.35rem;">{{ $nextAction['label'] }}</div>
                <div class="font-ui text-gray" style="font-size: 0.78rem; line-height: 1.55; margin-top: 0.65rem;">
                    Ikuti panel aksi yang aktif di bawah. Tombol abu-abu berarti shipment belum berada di state/hub yang tepat.
                </div>
                <div class="be-badge-row" style="margin-top: 0.85rem;">
                    <x-be.badge :variant="$nextAction['tone']">{{ strtoupper($shipment->status) }}</x-be.badge>
                    <x-be.badge :variant="$shipment->healthStatus() === 'exception' ? 'danger' : ($shipment->healthStatus() === 'on_time' ? 'success' : 'accent')">{{ $shipment->healthLabel() }}</x-be.badge>
                </div>
            </div>

            <x-be.route-card :estimate="$routeEstimate" />

            @if(in_array(auth()->user()->role, ['manager', 'cashier'], true))
                @php
                    $hubBranchId = auth()->user()->branch_id;
                    $receivableLeg = $shipment->legs->first(fn ($leg) => (int) $leg->destination_branch_id === (int) $hubBranchId && $leg->status === 'departed');
                    $departableLeg = $shipment->legs
                        ->filter(fn ($leg) => (int) $leg->origin_branch_id === (int) $hubBranchId && $leg->status === 'pending')
                        ->sortBy('sequence')
                        ->first(function ($leg) use ($shipment) {
                            $previousLeg = $shipment->legs->firstWhere('sequence', $leg->sequence - 1);

                            return ! $previousLeg || $previousLeg->status === 'arrived';
                        });
                    $isDestinationHub = (int) $shipment->destination_branch_id === (int) $hubBranchId;
                    $canAssignOutbound = (int) $shipment->origin_branch_id === (int) $hubBranchId
                        && in_array($shipment->status, ['pending', 'in_transit'], true)
                        && $shipment->legs->contains(fn ($leg) => (int) $leg->origin_branch_id === (int) $hubBranchId && in_array($leg->status, ['pending', 'departed'], true));
                    $canAssignDelivery = (int) $shipment->destination_branch_id === (int) $hubBranchId
                        && $shipment->status === 'arrived_at_branch'
                        && $shipment->legs->contains(fn ($leg) => (int) $leg->destination_branch_id === (int) $hubBranchId && $leg->status === 'arrived');
                    $canAssignCourier = $canAssignOutbound || $canAssignDelivery;
                @endphp

                @if($receivableLeg || $departableLeg)
                <div class="hud-panel mb-4" style="border-color: var(--color-accent);">
                    <h3 class="font-bank text-accent mb-4">Scan Hub</h3>
                    @if ($errors->any())
                        <div class="inline-alert">
                            @foreach ($errors->all() as $error)
                                <div class="font-ui text-gray" style="font-size: 0.85rem;">{{ $error }}</div>
                            @endforeach
                        </div>
                    @endif

                    <div style="display: grid; gap: 1rem;">
                        @if($receivableLeg)
                        <form action="{{ route('be.shipments.hub-scan', $shipment) }}" method="POST">
                            @csrf
                            <input type="hidden" name="scan_type" value="receive">
                            <div class="font-ui text-gray" style="font-size: 0.76rem; margin-bottom: 0.45rem;">
                                Terima inbound: {{ optional($receivableLeg->originBranch)->name.' -> '.optional($receivableLeg->destinationBranch)->name }}
                            </div>
                            <input type="text" name="note" placeholder="catatan inbound scan..." style="width: 100%; margin-bottom: 0.7rem;">
                            <x-be.button type="submit" variant="neutral" style="width: 100%;">Terima di Hub</x-be.button>
                        </form>
                        @endif

                        @if($departableLeg)
                        <form action="{{ route('be.shipments.hub-scan', $shipment) }}" method="POST">
                            @csrf
                            <input type="hidden" name="scan_type" value="depart">
                            <div class="font-ui text-gray" style="font-size: 0.76rem; margin-bottom: 0.45rem;">
                                Berangkatkan outbound: {{ optional($departableLeg->originBranch)->name.' -> '.optional($departableLeg->destinationBranch)->name }}
                            </div>
                            <input type="text" name="note" placeholder="catatan outbound scan..." style="width: 100%; margin-bottom: 0.7rem;">
                            <x-be.button type="submit" style="width: 100%;">Berangkatkan dari Hub</x-be.button>
                        </form>
                        @endif
                    </div>
                </div>
                @endif

                @if(in_array(auth()->user()->role, ['manager', 'cashier'], true))
                <div class="hud-panel mb-4" style="border-color: var(--color-primary);">
                    <h3 class="font-bank text-primary mb-4">Assignment Kurir</h3>
                    <div class="font-ui text-gray" style="font-size: 0.78rem; margin-bottom: 0.8rem;">
                        Kurir saat ini: {{ $shipment->courier ? $shipment->courier->name : 'belum ditugaskan' }}
                        @if($shipment->courier?->vehicle)
                            / {{ $shipment->courier->vehicle->label() }}
                        @endif
                    </div>
                    @if($canAssignOutbound)
                        <div class="font-ui text-gray" style="font-size: 0.74rem; line-height: 1.5; margin-bottom: 0.8rem;">
                            Outbound antar hub hanya bisa di-assign ke kurir dengan kendaraan truck aktif dan kapasitas cukup.
                        </div>
                    @endif

                    <form action="{{ route('be.shipments.assign-delivery', $shipment) }}" method="POST">
                        @csrf
                        <select name="courier_id" required style="width: 100%; margin-bottom: 0.8rem;" {{ ! $canAssignCourier ? 'disabled' : '' }}>
                            <option value="">PILIH KURIR HUB...</option>
                            @foreach($deliveryCouriers as $courier)
                                @php
                                    $vehicle = $courier->vehicle;
                                    $vehicleBlocked = ! $vehicle
                                        || $vehicle->status !== 'active'
                                        || ($vehicle->branch_id && (int) $vehicle->branch_id !== (int) $courier->branch_id)
                                        || ($canAssignOutbound && $vehicle->type !== 'truck')
                                        || ((float) ($vehicle->capacity_kg ?? 0) < (float) $shipment->total_weight)
                                        || ((int) ($vehicle->capacity_packages ?? 0) < 1);
                                @endphp
                                <option value="{{ $courier->id }}" @selected((int) $shipment->courier_id === (int) $courier->id) @disabled($vehicleBlocked)>
                                    {{ $courier->name }} / {{ $vehicle?->label() ?? 'belum ada kendaraan' }}{{ $vehicleBlocked ? ' / tidak eligible' : '' }}
                                </option>
                            @endforeach
                        </select>
                        <x-be.button type="submit" style="width: 100%;" :disabled="! $canAssignCourier">
                            {{ $canAssignOutbound ? 'Assign Kurir Outbound' : 'Assign Delivery' }}
                        </x-be.button>
                    </form>

                    @unless($canAssignCourier)
                        <div class="font-ui text-gray" style="font-size: 0.72rem; line-height: 1.55; margin-top: 0.8rem;">
                            @if(! $isDestinationHub)
                                Shipment belum punya leg outbound yang siap di hub ini.
                            @else
                                Paket harus sudah RECEIVE HUB di hub tujuan sebelum kurir delivery bisa di-assign.
                            @endif
                        </div>
                    @endunless
                </div>
                @endif
            @endif

            @if(in_array(auth()->user()->role, ['manager', 'cashier', 'courier'], true))
                <div class="hud-panel mb-4" style="border-color: #ff4444;">
                    <h3 class="font-bank mb-4" style="color: #ff4444;">Catat Kendala</h3>

                    <form action="{{ route('be.shipments.exception', $shipment) }}" method="POST" style="display: grid; gap: 0.85rem;">
                        @csrf
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.85rem;">
                            <div>
                                <label class="font-ui text-gray" style="font-size: 0.78rem;">TYPE</label>
                                <select name="type" required style="width: 100%;">
                                    <option value="delay">DELAY</option>
                                    <option value="address_issue">ADDRESS ISSUE</option>
                                    <option value="hold">HOLD</option>
                                    <option value="damaged">DAMAGED</option>
                                    <option value="lost">LOST</option>
                                    <option value="other">OTHER</option>
                                </select>
                            </div>
                            <div>
                                <label class="font-ui text-gray" style="font-size: 0.78rem;">SEVERITY</label>
                                <select name="severity" required style="width: 100%;">
                                    <option value="medium">MEDIUM</option>
                                    <option value="low">LOW</option>
                                    <option value="high">HIGH</option>
                                    <option value="critical">CRITICAL</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="font-ui text-gray" style="font-size: 0.78rem;">AFFECTED LEG</label>
                            <select name="shipment_leg_id" style="width: 100%;">
                                <option value="">GENERAL SHIPMENT</option>
                                @foreach($shipment->legs as $leg)
                                    <option value="{{ $leg->id }}">
                                        LEG {{ str_pad($leg->sequence, 2, '0', STR_PAD_LEFT) }} / {{ optional($leg->originBranch)->name ?? '-' }} -> {{ optional($leg->destinationBranch)->name ?? '-' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <input type="text" name="location" placeholder="lokasi kejadian..." style="width: 100%;">
                        <textarea name="description" required rows="3" placeholder="jelaskan kendala operasional..." style="width: 100%;"></textarea>
                        <x-be.button type="submit" variant="danger" style="width: 100%;">RECORD EXCEPTION</x-be.button>
                    </form>

                    @if($shipment->exceptions->isNotEmpty())
                        <div style="border-top: 1px dashed var(--color-panel-border); margin-top: 1rem; padding-top: 1rem; display: grid; gap: 0.8rem;">
                            @foreach($shipment->exceptions->sortByDesc('created_at') as $exception)
                                <div>
                                    <div class="font-ui" style="font-size: 0.82rem; color: #ff4444;">
                                        {{ strtoupper($exception->type) }} / {{ strtoupper($exception->severity) }} / {{ strtoupper($exception->status) }}
                                    </div>
                                    <div class="font-ui text-gray" style="font-size: 0.74rem; line-height: 1.55;">
                                        {{ optional($exception->created_at)->format('d M Y H:i') }} @ {{ $exception->location ?: '-' }}
                                        @if($exception->leg)
                                            / LEG {{ str_pad($exception->leg->sequence, 2, '0', STR_PAD_LEFT) }}
                                        @endif
                                        / {{ optional($exception->reporter)->name ?? 'SYSTEM' }}
                                    </div>
                                    <div class="font-ui text-main" style="font-size: 0.8rem; line-height: 1.55; margin-top: 0.25rem;">
                                        {{ $exception->description }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

            @if(auth()->user()->role === 'courier')
            @php
                $nextShipmentStatuses = [
                    'pending' => ['picked_up' => 'PICKED_UP', 'in_transit' => 'IN_TRANSIT'],
                    'picked_up' => ['in_transit' => 'IN_TRANSIT'],
                    'in_transit' => ['arrived_at_branch' => 'ARRIVED_AT_BRANCH'],
                    'arrived_at_branch' => ['out_for_delivery' => 'OUT_FOR_DELIVERY'],
                    'out_for_delivery' => ['delivered' => 'DELIVERED', 'delivery_failed' => 'DELIVERY_FAILED', 'returned_to_hub' => 'RETURNED_TO_HUB'],
                    'delivery_failed' => ['rescheduled' => 'RESCHEDULED', 'out_for_delivery' => 'OUT_FOR_DELIVERY', 'returned_to_hub' => 'RETURNED_TO_HUB'],
                    'rescheduled' => ['out_for_delivery' => 'OUT_FOR_DELIVERY', 'returned_to_hub' => 'RETURNED_TO_HUB'],
                    'returned_to_hub' => ['out_for_delivery' => 'OUT_FOR_DELIVERY', 'cancelled' => 'CANCELLED'],
                    'held' => ['rescheduled' => 'RESCHEDULED', 'returned_to_hub' => 'RETURNED_TO_HUB', 'cancelled' => 'CANCELLED'],
                    'damaged' => ['returned_to_hub' => 'RETURNED_TO_HUB', 'cancelled' => 'CANCELLED'],
                    'lost' => ['cancelled' => 'CANCELLED'],
                    'exception' => ['rescheduled' => 'RESCHEDULED', 'returned_to_hub' => 'RETURNED_TO_HUB', 'cancelled' => 'CANCELLED'],
                    'delivered' => [],
                    'cancelled' => [],
                ][$shipment->status] ?? [];
            @endphp
            <div class="hud-panel mb-4" style="border-color: var(--color-primary);">
                <h3 class="font-bank text-primary mb-4">Update Status</h3>
                @if ($errors->any())
                    <div class="inline-alert">
                        @foreach ($errors->all() as $error)
                            <div class="font-ui text-gray" style="font-size: 0.85rem;">{{ $error }}</div>
                        @endforeach
                    </div>
                @endif
                <form action="{{ route('be.shipments.status', $shipment) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div style="margin-bottom: 1rem;">
                        <label class="font-ui text-gray" style="font-size: 0.8rem;">Status Baru</label><br>
                        <select name="status" required style="width: 100%; padding: 0.5rem; font-family: var(--font-ui);" {{ empty($nextShipmentStatuses) ? 'disabled' : '' }}>
                            @forelse($nextShipmentStatuses as $statusValue => $statusLabel)
                                <option value="{{ $statusValue }}">{{ $statusLabel }}</option>
                            @empty
                                <option value="">Tidak ada status lanjutan</option>
                            @endforelse
                        </select>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label class="font-ui text-gray" style="font-size: 0.8rem;">Lokasi Saat Ini</label><br>
                        <input type="text" name="location" required placeholder="example: Jakarta Hub / Destination Hub" style="width: 100%; padding: 0.5rem; border: none; border-bottom: 2px solid var(--color-gray); background: transparent; font-family: var(--font-ui); color: var(--color-text-main); outline: none;">
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label class="font-ui text-gray" style="font-size: 0.8rem;">Catatan Status</label><br>
                        <input type="text" name="description" required placeholder="example: Paket dalam perjalanan ke Medan" style="width: 100%; padding: 0.5rem; border: none; border-bottom: 2px solid var(--color-gray); background: transparent; font-family: var(--font-ui); color: var(--color-text-main); outline: none;">
                    </div>
                    <div style="margin-bottom: 2rem;">
                        <label class="font-ui text-gray" style="font-size: 0.8rem;">Foto Bukti</label><br>
                        <input type="file" name="photo" style="color: var(--color-gray); font-family: var(--font-ui); font-size: 0.8rem;">
                    </div>
                    <button type="submit" class="btn-neon" style="width: 100%;" {{ empty($nextShipmentStatuses) ? 'disabled' : '' }}>Simpan Update</button>
                </form>
            </div>
            @endif

            <div class="hud-panel">
                <h3 class="font-bank text-main mb-4">Timeline Status</h3>
                @php
                    $timelineGroups = $shipment->trackings->groupBy(function ($track) {
                        return match ($track->status) {
                            'pending', 'picked_up' => 'PAYMENT / INTAKE',
                            'in_transit', 'arrived_at_branch' => 'HUB SCAN',
                            'out_for_delivery', 'delivered', 'delivery_failed', 'rescheduled', 'returned_to_hub' => 'DELIVERY',
                            'held', 'damaged', 'lost', 'exception', 'cancelled' => 'EXCEPTION',
                            default => 'OTHER',
                        };
                    });
                @endphp
                <div style="display: grid; gap: 1.35rem;">
                    @foreach($timelineGroups as $group => $tracks)
                        <div>
                            <div class="font-bank text-accent" style="font-size: 0.84rem; margin-bottom: 0.8rem;">{{ $group }}</div>
                            <div style="border-left: 2px dashed var(--color-panel-border); padding-left: 1.5rem; position: relative;">
                                @foreach($tracks as $track)
                                    <div style="margin-bottom: 1.35rem; position: relative;">
                                        <div style="position: absolute; left: -1.9rem; top: 0; width: 10px; height: 10px; border-radius: 50%; background: var(--color-primary); border: 2px solid var(--color-bg);"></div>
                                        <div class="font-ui text-accent" style="font-size: 0.75rem;">{{ $track->tracked_at }}</div>
                                        <div class="font-ui text-main" style="font-size: 0.9rem; font-weight: bold;">{{ strtoupper($track->status) }} @ {{ $track->location }}</div>
                                        <div class="font-ui text-gray" style="font-size: 0.8rem; margin-top: 0.3rem;">{{ $track->description }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            @if($shipment->notifications->isNotEmpty())
                <div class="hud-panel mt-4">
                    <h3 class="font-bank text-accent mb-4">NOTIFICATION LOG</h3>
                    <div style="display: grid; gap: 1rem;">
                        @foreach($shipment->notifications->sortByDesc('created_at') as $notification)
                            <div style="border-bottom: 1px solid var(--color-panel-border); padding-bottom: 0.85rem;">
                                <div class="font-ui text-main" style="font-size: 0.84rem; font-weight: bold;">
                                    {{ strtoupper($notification->event) }} / {{ optional($notification->sent_at)->format('d M Y H:i') ?? '-' }}
                                </div>
                                <div class="font-ui text-accent" style="font-size: 0.78rem; margin-top: 0.25rem;">{{ $notification->title }}</div>
                                <div class="font-ui text-gray" style="font-size: 0.76rem; line-height: 1.55; margin-top: 0.25rem;">{{ $notification->message }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

    </div>

@endsection
