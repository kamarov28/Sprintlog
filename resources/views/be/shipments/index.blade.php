@extends('be.layouts.main')

@section('header_title', 'SHIPMENT MANIFEST')

@section('content')

    @php
        $canBatchDispatch = auth()->user()->role === 'manager';
        $hubBranchId = auth()->user()->branch_id;
    @endphp

    <div class="hud-panel surface-toolbar mb-4">
        <div class="font-ui text-gray">TOTAL SHIPMENTS: {{ $shipments->total() }}</div>
        <div style="display: flex; gap: 0.8rem; flex-wrap: wrap;">
            @if (session('manifest_print_url'))
                <a href="{{ session('manifest_print_url') }}" target="_blank" class="be-btn be-btn--neutral">PRINT MANIFEST</a>
            @endif
            @if (in_array(auth()->user()->role, ['manager', 'cashier']))
                <a href="{{ route('be.shipments.create') }}" class="btn-neon" style="padding: 5px 20px;">ADD NEW SHIPMENT</a>
            @endif
        </div>
    </div>

    <div class="hud-panel">
        <form action="{{ route('be.shipments.index') }}" method="GET" class="shipment-filter mb-4">
            <div>
                <label class="font-ui text-gray" style="font-size: 0.78rem;">SEARCH_SHIPMENT</label>
                <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="resi, sender, receiver, hub...">
            </div>
            <div>
                <label class="font-ui text-gray" style="font-size: 0.78rem;">PRESET</label>
                <select name="preset">
                    <option value="">ALL SHIPMENTS</option>
                    <option value="need_payment" @selected(($filters['preset'] ?? '') === 'need_payment')>NEED PAYMENT</option>
                    <option value="ready_dispatch" @selected(($filters['preset'] ?? '') === 'ready_dispatch')>READY DISPATCH</option>
                    <option value="inbound_today" @selected(($filters['preset'] ?? '') === 'inbound_today')>INBOUND TODAY</option>
                    <option value="failed_delivery" @selected(($filters['preset'] ?? '') === 'failed_delivery')>FAILED DELIVERY</option>
                    <option value="exception_open" @selected(($filters['preset'] ?? '') === 'exception_open')>EXCEPTION OPEN</option>
                </select>
            </div>
            <div style="display: flex; gap: 0.65rem; align-items: end;">
                <x-be.button type="submit">FILTER</x-be.button>
                <x-be.button href="{{ route('be.shipments.index') }}" variant="neutral">RESET</x-be.button>
            </div>
        </form>

        @if($canBatchDispatch)
            <form action="{{ route('be.shipments.manifest-dispatch') }}" method="POST" id="manifest-dispatch-form">
                @csrf
                <div class="surface-toolbar mb-3" style="align-items: end;">
                    <div style="min-width: min(100%, 360px);">
                        <div class="font-ui text-gray" style="font-size: 0.76rem; margin-bottom: 0.35rem;">MANIFEST_NOTE</div>
                        <input type="text" name="notes" placeholder="catatan batch departure..." style="width: 100%;">
                    </div>
                    <x-be.button type="submit" style="padding: 5px 20px;">DISPATCH SELECTED</x-be.button>
                </div>
        @endif

        <div class="table-responsive">
            <table style="min-width: 800px;" class="app-table">
            <thead>
                <tr style="border-bottom: 2px solid var(--color-panel-border); font-size: 0.85rem; color: var(--color-gray);">
                    @if($canBatchDispatch)
                        <th style="padding: 1rem 0.5rem;">LOAD</th>
                    @endif
                    <th style="padding: 1rem 0.5rem;">TRACKING ID</th>
                    <th style="padding: 1rem 0.5rem;">SENDER</th>
                    <th style="padding: 1rem 0.5rem;">ROUTE</th>
                    <th style="padding: 1rem 0.5rem;">STATUS</th>
                    <th style="padding: 1rem 0.5rem;">DATE</th>
                    <th style="padding: 1rem 0.5rem; text-align: center;">ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                @forelse($shipments as $shipment)
                @php
                    $departableLeg = $shipment->legs
                        ->where('origin_branch_id', $hubBranchId)
                        ->where('status', 'pending')
                        ->sortBy('sequence')
                        ->first();
                @endphp
                <tr style="border-bottom: 1px solid var(--color-panel-border); font-size: 0.95rem;">
                    @if($canBatchDispatch)
                        @php
                            $lockReason = $shipment->dispatchLockReason($hubBranchId);
                        @endphp
                        <td style="padding: 1.2rem 0.5rem;">
                            @if(! $lockReason)
                                <input type="checkbox" name="shipment_ids[]" value="{{ $shipment->id }}" title="Load to manifest">
                            @else
                                <span class="font-ui text-gray" style="font-size: 0.72rem;" title="{{ $lockReason }}">LOCKED</span>
                                <div class="font-ui text-gray" style="font-size: 0.65rem; margin-top: 0.25rem;">{{ $lockReason }}</div>
                            @endif
                        </td>
                    @endif
                    <td style="padding: 1.2rem 0.5rem;">
                        <span class="text-accent" style="font-weight: bold;">{{ $shipment->tracking_number }}</span>
                        @if($departableLeg)
                            <div class="font-ui text-gray" style="font-size: 0.72rem; margin-top: 0.35rem;">
                                NEXT: {{ optional($departableLeg->destinationBranch)->name ?? '-' }}
                            </div>
                        @endif
                    </td>
                    <td style="padding: 1.2rem 0.5rem;">
                        <div>{{ $shipment->sender->name }}</div>
                        <div style="font-size: 0.75rem; color: var(--color-gray);">{{ $shipment->sender->phone }}</div>
                    </td>
                    <td style="padding: 1.2rem 0.5rem;">
                        <div style="font-size: 0.85rem;">
                            {{ $shipment->originBranch->city }} <span class="text-primary">&rarr;</span> {{ $shipment->destinationBranch->city }}
                        </div>
                    </td>
                    <td style="padding: 1.2rem 0.5rem;">
                        @php
                            $statusColors = [
                                'pending' => 'var(--color-gray)',
                                'picked_up' => 'var(--color-primary)',
                                'in_transit' => 'var(--color-accent)',
                                'arrived_at_branch' => 'var(--color-primary)',
                                'out_for_delivery' => 'var(--color-accent)',
                                'delivery_failed' => '#ff4444',
                                'rescheduled' => 'var(--color-accent)',
                                'returned_to_hub' => '#ff4444',
                                'held' => '#ff4444',
                                'damaged' => '#ff4444',
                                'lost' => '#ff4444',
                                'exception' => '#ff4444',
                                'delivered' => '#00ff00',
                                'cancelled' => 'red'
                            ];
                            $color = $statusColors[$shipment->status] ?? 'var(--color-text-main)';
                        @endphp
                        <span class="status-chip" style="color: {{ $color }};">{{ strtoupper($shipment->status) }}</span>
                        <div class="be-badge-row" style="margin-top: 0.4rem;">
                            <x-be.badge :variant="$shipment->healthStatus() === 'exception' ? 'danger' : ($shipment->healthStatus() === 'on_time' ? 'success' : 'accent')">
                                {{ $shipment->healthLabel() }}
                            </x-be.badge>
                        </div>
                    </td>
                    <td style="padding: 1.2rem 0.5rem; font-size: 0.85rem; color: var(--color-gray);">
                        {{ $shipment->shipment_date->format('d/m/Y H:i') }}
                    </td>
                    <td style="padding: 1.2rem 0.5rem; text-align: center;">
                        <a href="{{ route('be.shipments.show', $shipment) }}" class="btn-neon" style="font-size: 0.75rem; padding: 2px 10px;">VIEW / UPDATE</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="{{ $canBatchDispatch ? 7 : 6 }}" class="table-empty">NO SHIPMENTS FOUND</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        </div>

        @if($canBatchDispatch)
            </form>
        @endif

        <x-be.pagination :paginator="$shipments" />
    </div>

@endsection
