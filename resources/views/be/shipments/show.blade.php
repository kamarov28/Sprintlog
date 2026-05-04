@extends('be.layouts.main')

@section('header_title', 'SHIPMENT_DETAILS: ' . $shipment->tracking_number)

@section('content')

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 3rem;">
        
        <!-- Left: Static Details -->
        <div>
            <div class="hud-panel mb-4">
                <h3 class="font-bank text-primary mb-3" style="font-size: 1.2rem;">[ SENDER & RECEIVER ]</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <div class="font-ui text-gray" style="font-size: 0.75rem;">SENDER</div>
                        <div class="font-ui" style="font-size: 1rem; color: var(--color-text-main);">{{ $shipment->sender->name }}</div>
                        <div class="font-ui text-gray" style="font-size: 0.8rem;">{{ $shipment->sender->phone }}</div>
                    </div>
                    <div>
                        <div class="font-ui text-gray" style="font-size: 0.75rem;">RECEIVER</div>
                        <div class="font-ui" style="font-size: 1rem; color: var(--color-text-main);">{{ $shipment->receiver->name }}</div>
                        <div class="font-ui text-gray" style="font-size: 0.8rem;">{{ $shipment->receiver->phone }}</div>
                        @if($shipment->receiver->address)
                            <div class="font-ui" style="font-size: 0.8rem; color: var(--color-accent); margin-top: 4px;">{{ $shipment->receiver->address }}</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="hud-panel mb-4">
                <h3 class="font-bank text-accent mb-3" style="font-size: 1.2rem;">CARGO INFO</h3>
                <div style="margin-bottom: 1.5rem;">
                    <div class="font-ui text-gray" style="font-size: 0.75rem;">ITEMS</div>
                    @foreach($shipment->items as $item)
                        <div class="font-ui" style="font-size: 1rem; color: var(--color-text-main);">{{ $item->item_name }} ({{ $item->quantity }}x)</div>
                    @endforeach
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <div class="font-ui text-gray" style="font-size: 0.75rem;">WEIGHT</div>
                        <div class="font-ui" style="font-size: 1.2rem; color: var(--color-text-main);">{{ $shipment->total_weight }} KG</div>
                    </div>
                    <div>
                        <div class="font-ui text-gray" style="font-size: 0.75rem;">TOTAL SHIPMENT COST</div>
                        <div class="font-ui" style="font-size: 1.2rem; color: var(--color-accent); font-weight: bold;">Rp {{ number_format($shipment->total_price, 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>

            @if($shipment->payment)
            <div class="hud-panel mb-4">
                <div style="display: flex; justify-content: space-between; align-items: start;">
                    <h3 class="font-bank text-primary mb-3" style="font-size: 1.2rem;">PAYMENT DATA</h3>
                    @if($shipment->payment->payment_method === 'cash')
                        <a href="{{ route('be.shipments.receipt', $shipment) }}" target="_blank" class="btn-neon" style="font-size: 0.7rem; padding: 5px 15px;">PRINT RESI KASIR</a>
                    @endif
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div>
                        <div class="font-ui text-gray" style="font-size: 0.75rem;">METHOD</div>
                        <div class="font-ui" style="font-size: 1.2rem; color: var(--color-text-main);">{{ strtoupper($shipment->payment->payment_method) }}</div>
                    </div>
                    <div>
                        <div class="font-ui text-gray" style="font-size: 0.75rem;">STATUS</div>
                        <div class="font-ui flex items-center" style="font-size: 1.2rem; color: {{ $shipment->payment->payment_status == 'paid' ? 'var(--color-primary)' : 'var(--color-accent)' }}; font-weight: bold;">{{ strtoupper($shipment->payment->payment_status) }}</div>
                    </div>
                </div>
                @if($shipment->payment->payment_method === 'cash')
                <div style="border-top: 1px dashed var(--color-panel-border); padding-top: 1rem; display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <div class="font-ui text-gray" style="font-size: 0.75rem;">AMOUNT RECEIVED (TUNAI)</div>
                        <div class="font-ui" style="font-size: 1.2rem; color: var(--color-text-main);">Rp {{ number_format($shipment->payment->amount_received, 0, ',', '.') }}</div>
                    </div>
                    <div>
                        <div class="font-ui text-gray" style="font-size: 0.75rem;">CHANGE (KEMBALIAN)</div>
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
                <h3 class="font-bank text-main mb-3" style="font-size: 1.2rem;">ROUTE LEGS</h3>
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
                <div class="font-ui text-gray" style="font-size: 0.76rem;">NEXT_ACTION</div>
                <div class="font-bank text-main" style="font-size: 1.4rem; margin-top: 0.35rem;">{{ $nextAction['label'] }}</div>
                <div class="be-badge-row" style="margin-top: 0.85rem;">
                    <x-be.badge :variant="$nextAction['tone']">{{ strtoupper($shipment->status) }}</x-be.badge>
                    <x-be.badge :variant="$shipment->healthStatus() === 'exception' ? 'danger' : ($shipment->healthStatus() === 'on_time' ? 'success' : 'accent')">{{ $shipment->healthLabel() }}</x-be.badge>
                </div>
            </div>

            <x-be.route-card :estimate="$routeEstimate" />

            @if(in_array(auth()->user()->role, ['manager', 'cashier'], true))
                @php
                    $hubBranchId = auth()->user()->branch_id;
                    $departableLeg = $shipment->legs->first(fn ($leg) => (int) $leg->origin_branch_id === (int) $hubBranchId && $leg->status === 'pending');
                    $receivableLeg = $shipment->legs->first(fn ($leg) => (int) $leg->destination_branch_id === (int) $hubBranchId && in_array($leg->status, ['departed', 'pending'], true));
                    $canAssignDelivery = (int) $shipment->destination_branch_id === (int) $hubBranchId
                        && $shipment->status === 'arrived_at_branch'
                        && $shipment->legs->contains(fn ($leg) => (int) $leg->destination_branch_id === (int) $hubBranchId && $leg->status === 'arrived');
                @endphp

                <div class="hud-panel mb-4" style="border-color: var(--color-accent);">
                    <h3 class="font-bank text-accent mb-4">HUB SCAN</h3>
                    @if ($errors->any())
                        <div class="inline-alert">
                            @foreach ($errors->all() as $error)
                                <div class="font-ui text-gray" style="font-size: 0.85rem;">{{ $error }}</div>
                            @endforeach
                        </div>
                    @endif

                    <div style="display: grid; gap: 1rem;">
                        <form action="{{ route('be.shipments.hub-scan', $shipment) }}" method="POST">
                            @csrf
                            <input type="hidden" name="scan_type" value="receive">
                            <div class="font-ui text-gray" style="font-size: 0.76rem; margin-bottom: 0.45rem;">
                                RECEIVE: {{ $receivableLeg ? optional($receivableLeg->originBranch)->name.' -> '.optional($receivableLeg->destinationBranch)->name : 'NO INBOUND LEG READY' }}
                            </div>
                            <input type="text" name="note" placeholder="catatan inbound scan..." style="width: 100%; margin-bottom: 0.7rem;">
                            <x-be.button type="submit" variant="neutral" style="width: 100%;" :disabled="! $receivableLeg">RECEIVE HUB</x-be.button>
                        </form>

                        <form action="{{ route('be.shipments.hub-scan', $shipment) }}" method="POST">
                            @csrf
                            <input type="hidden" name="scan_type" value="depart">
                            <div class="font-ui text-gray" style="font-size: 0.76rem; margin-bottom: 0.45rem;">
                                DEPART: {{ $departableLeg ? optional($departableLeg->originBranch)->name.' -> '.optional($departableLeg->destinationBranch)->name : 'NO OUTBOUND LEG READY' }}
                            </div>
                            <input type="text" name="note" placeholder="catatan outbound scan..." style="width: 100%; margin-bottom: 0.7rem;">
                            <x-be.button type="submit" style="width: 100%;" :disabled="! $departableLeg">DEPART HUB</x-be.button>
                        </form>
                    </div>
                </div>

                @if(auth()->user()->role === 'manager')
                <div class="hud-panel mb-4" style="border-color: var(--color-primary);">
                    <h3 class="font-bank text-primary mb-4">DELIVERY ASSIGNMENT</h3>
                    <div class="font-ui text-gray" style="font-size: 0.78rem; margin-bottom: 0.8rem;">
                        CURRENT COURIER: {{ $shipment->courier ? $shipment->courier->name : 'UNASSIGNED' }}
                    </div>

                    <form action="{{ route('be.shipments.assign-delivery', $shipment) }}" method="POST">
                        @csrf
                        <select name="courier_id" required style="width: 100%; margin-bottom: 0.8rem;" {{ ! $canAssignDelivery ? 'disabled' : '' }}>
                            <option value="">SELECT DESTINATION COURIER...</option>
                            @foreach($deliveryCouriers as $courier)
                                <option value="{{ $courier->id }}" @selected((int) $shipment->courier_id === (int) $courier->id)>
                                    {{ $courier->name }} / {{ $courier->email }}
                                </option>
                            @endforeach
                        </select>
                        <x-be.button type="submit" style="width: 100%;" :disabled="! $canAssignDelivery">ASSIGN DELIVERY</x-be.button>
                    </form>

                    @unless($canAssignDelivery)
                        <div class="font-ui text-gray" style="font-size: 0.72rem; line-height: 1.55; margin-top: 0.8rem;">
                            Paket harus sudah RECEIVE HUB di hub tujuan sebelum kurir delivery bisa di-assign.
                        </div>
                    @endunless
                </div>
                @endif
            @endif

            @if(in_array(auth()->user()->role, ['manager', 'cashier', 'courier'], true))
                <div class="hud-panel mb-4" style="border-color: #ff4444;">
                    <h3 class="font-bank mb-4" style="color: #ff4444;">EXCEPTION LOG</h3>

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
                <h3 class="font-bank text-primary mb-4">UPDATE STATUS</h3>
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
                        <label class="font-ui text-gray" style="font-size: 0.8rem;">NEW STATUS</label><br>
                        <select name="status" required style="width: 100%; padding: 0.5rem; font-family: var(--font-ui);" {{ empty($nextShipmentStatuses) ? 'disabled' : '' }}>
                            @forelse($nextShipmentStatuses as $statusValue => $statusLabel)
                                <option value="{{ $statusValue }}">{{ $statusLabel }}</option>
                            @empty
                                <option value="">NO_NEXT_STATUS_AVAILABLE</option>
                            @endforelse
                        </select>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label class="font-ui text-gray" style="font-size: 0.8rem;">CURRENT LOCATION</label><br>
                        <input type="text" name="location" required placeholder="example: Jakarta Hub / Destination Hub" style="width: 100%; padding: 0.5rem; border: none; border-bottom: 2px solid var(--color-gray); background: transparent; font-family: var(--font-ui); color: var(--color-text-main); outline: none;">
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label class="font-ui text-gray" style="font-size: 0.8rem;">LOG DESCRIPTION</label><br>
                        <input type="text" name="description" required placeholder="example: Paket dalam perjalanan ke Medan" style="width: 100%; padding: 0.5rem; border: none; border-bottom: 2px solid var(--color-gray); background: transparent; font-family: var(--font-ui); color: var(--color-text-main); outline: none;">
                    </div>
                    <div style="margin-bottom: 2rem;">
                        <label class="font-ui text-gray" style="font-size: 0.8rem;">ATTACH PHOTO (FOR POD/PROOF)</label><br>
                        <input type="file" name="photo" style="color: var(--color-gray); font-family: var(--font-ui); font-size: 0.8rem;">
                    </div>
                    <button type="submit" class="btn-neon" style="width: 100%;" {{ empty($nextShipmentStatuses) ? 'disabled' : '' }}>BROADCAST UPDATE</button>
                </form>
            </div>
            @endif

            <div class="hud-panel">
                <h3 class="font-bank text-main mb-4">TIMELINE LOGS</h3>
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
