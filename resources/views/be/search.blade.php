@extends('be.layouts.main')

@section('header_title', 'GLOBAL SEARCH')

@section('content')
    <div class="hud-panel mb-4">
        <div class="font-ui text-gray" style="font-size: 0.82rem;">QUERY</div>
        <h3 class="font-bank text-main" style="font-size: 1.7rem; margin-top: 0.4rem;">{{ $term }}</h3>
    </div>

    <div class="qol-search-grid">
        <div class="hud-panel">
            <div class="hud-header mb-4">
                <h3 class="font-bank text-main">SHIPMENTS</h3>
                <x-be.badge variant="accent">{{ $shipments->count() }}</x-be.badge>
            </div>
            <div class="qol-result-list">
                @forelse($shipments as $shipment)
                    <a href="{{ route('be.shipments.show', $shipment) }}" class="qol-result">
                        <div>
                            <div class="font-ui text-accent" style="font-size: 0.85rem;">{{ $shipment->tracking_number }}</div>
                            <div class="font-ui text-main" style="font-size: 0.78rem; margin-top: 0.25rem;">
                                {{ optional($shipment->sender)->name ?? '-' }} -> {{ optional($shipment->receiver)->name ?? '-' }}
                            </div>
                            <div class="font-ui text-gray" style="font-size: 0.72rem; margin-top: 0.25rem;">
                                {{ optional($shipment->originBranch)->city ?? '-' }} / {{ optional($shipment->destinationBranch)->city ?? '-' }}
                            </div>
                        </div>
                        <x-be.badge :variant="$shipment->healthStatus() === 'exception' ? 'danger' : ($shipment->healthStatus() === 'on_time' ? 'success' : 'accent')">
                            {{ $shipment->healthLabel() }}
                        </x-be.badge>
                    </a>
                @empty
                    <x-be.empty title="NO SHIPMENT MATCH" body="Coba nomor resi lengkap, nama customer, atau nomor HP." />
                @endforelse
            </div>
        </div>

        <div class="hud-panel">
            <div class="hud-header mb-4">
                <h3 class="font-bank text-main">PICKUPS</h3>
                <x-be.badge variant="accent">{{ $pickups->count() }}</x-be.badge>
            </div>
            <div class="qol-result-list">
                @forelse($pickups as $pickup)
                    <a href="{{ route('be.pickups.show', $pickup) }}" class="qol-result">
                        <div>
                            <div class="font-ui text-accent" style="font-size: 0.85rem;">REQ_UNIT_{{ $pickup->id }}</div>
                            <div class="font-ui text-main" style="font-size: 0.78rem; margin-top: 0.25rem;">
                                {{ $pickup->sender_name ?: $pickup->customer_name }} / {{ $pickup->sender_phone ?: $pickup->customer_phone }}
                            </div>
                            <div class="font-ui text-gray" style="font-size: 0.72rem; margin-top: 0.25rem;">
                                {{ optional($pickup->branch)->name ?? 'UNASSIGNED HUB' }}
                            </div>
                        </div>
                        <x-be.badge variant="neutral">{{ strtoupper($pickup->status) }}</x-be.badge>
                    </a>
                @empty
                    <x-be.empty title="NO PICKUP MATCH" body="Pickup bisa dicari dari nama pengirim, penerima, alamat, atau nomor HP." />
                @endforelse
            </div>
        </div>

        <div class="hud-panel">
            <div class="hud-header mb-4">
                <h3 class="font-bank text-main">HUBS</h3>
                <x-be.badge variant="accent">{{ $branches->count() }}</x-be.badge>
            </div>
            <div class="qol-result-list">
                @forelse($branches as $branch)
                    @if(auth()->user()->role === 'admin')
                        <a href="{{ route('be.branches.index', ['search' => $branch->name]) }}" class="qol-result">
                    @else
                        <div class="qol-result">
                    @endif
                        <div>
                            <div class="font-ui text-accent" style="font-size: 0.85rem;">{{ $branch->name }}</div>
                            <div class="font-ui text-main" style="font-size: 0.78rem; margin-top: 0.25rem;">{{ $branch->city }}</div>
                            <div class="font-ui text-gray" style="font-size: 0.72rem; margin-top: 0.25rem;">{{ $branch->phone }}</div>
                        </div>
                    @if(auth()->user()->role === 'admin')
                        </a>
                    @else
                        </div>
                    @endif
                @empty
                    <x-be.empty title="NO HUB MATCH" body="Coba nama provinsi, kota, alamat, atau nomor telepon hub." />
                @endforelse
            </div>
        </div>

        <div class="hud-panel">
            <div class="hud-header mb-4">
                <h3 class="font-bank text-main">PERSONNEL</h3>
                <x-be.badge variant="accent">{{ $users->count() }}</x-be.badge>
            </div>
            <div class="qol-result-list">
                @forelse($users as $person)
                    <a href="{{ route('be.users.index', ['search' => $person->name]) }}" class="qol-result">
                        <div>
                            <div class="font-ui text-accent" style="font-size: 0.85rem;">{{ $person->name }}</div>
                            <div class="font-ui text-main" style="font-size: 0.78rem; margin-top: 0.25rem;">{{ strtoupper($person->role) }} / {{ $person->email }}</div>
                            <div class="font-ui text-gray" style="font-size: 0.72rem; margin-top: 0.25rem;">{{ optional($person->branch)->name ?? 'NO HUB' }}</div>
                        </div>
                    </a>
                @empty
                    <x-be.empty title="NO PERSONNEL MATCH" body="Coba nama, email, telepon, atau nama hub." />
                @endforelse
            </div>
        </div>
    </div>
@endsection
