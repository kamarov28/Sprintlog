@extends('be.layouts.main')

@section('header_title', 'REGIONAL HUBS')

@section('content')
    @php
        $firstVisible = $branches->firstItem() ?? 0;
        $lastVisible = $branches->lastItem() ?? 0;
        $currentTotal = $branches->total();
        $coordinatePercent = $totalBranches > 0 ? round(($coordinateReady / $totalBranches) * 100) : 0;
        $crewPercent = $totalBranches > 0 ? round(($crewReady / $totalBranches) * 100) : 0;
    @endphp

    <div class="branch-workspace">
        <x-be.panel class="branch-summary">
            <div class="branch-summary__title">
                <div class="branch-summary__label">NATIONAL_HUB_NETWORK</div>
                <h3>{{ $totalBranches }} HUBS</h3>
            </div>

            <x-be.stat label="VISIBLE" value="{{ $firstVisible }}-{{ $lastVisible }}" />
            <x-be.stat label="MATCHED" :value="$currentTotal" />
            <x-be.stat label="COORDS" value="{{ $coordinatePercent }}%" />
            <x-be.stat label="CREW" value="{{ $crewPercent }}%" />
        </x-be.panel>

        <x-be.panel>
            <form action="{{ route('be.branches.index') }}" method="GET" class="branch-filter">
                <div>
                    <label for="branch-search">SEARCH_HUB</label>
                    <input id="branch-search" name="search" type="text" value="{{ $filters['search'] }}" placeholder="hub, provinsi, alamat, telepon...">
                </div>

                <div>
                    <label for="branch-coordinates">COORDS</label>
                    <select id="branch-coordinates" name="coordinates">
                        <option value="" @selected($filters['coordinates'] === '')>ALL</option>
                        <option value="ready" @selected($filters['coordinates'] === 'ready')>READY</option>
                        <option value="missing" @selected($filters['coordinates'] === 'missing')>MISSING</option>
                    </select>
                </div>

                <x-be.button type="submit">FILTER</x-be.button>
                <x-be.button :href="route('be.branches.index')" variant="neutral">RESET</x-be.button>
            </form>
        </x-be.panel>

        <x-be.panel>
            <div class="branch-toolbar mb-4">
                <div class="font-ui text-gray">SHOWING {{ $firstVisible }}-{{ $lastVisible }} / {{ $currentTotal }}</div>
                <x-be.button :href="route('be.branches.create')">DEPLOY</x-be.button>
            </div>

            <x-be.table min-width="860px">
                <table class="app-table branch-table">
                    <thead>
                        <tr>
                            <th>HUB</th>
                            <th>LOCATION</th>
                            <th>CONTACT</th>
                            <th>COORDS</th>
                            <th>CREW</th>
                            <th class="centered">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($branches as $branch)
                            <tr>
                                <td>
                                    <div class="branch-name">
                                        <span class="branch-index">{{ $firstVisible + $loop->index }}</span>
                                        <div>
                                            <strong>{{ $branch->name }}</strong>
                                            <div class="branch-subtext">{{ $branch->address ?: 'NO ADDRESS DATA' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $branch->city ?: '-' }}</td>
                                <td>{{ $branch->phone ?: '-' }}</td>
                                <td>
                                    @if($branch->latitude && $branch->longitude)
                                        <x-be.badge variant="success">{{ number_format((float) $branch->latitude, 3) }}, {{ number_format((float) $branch->longitude, 3) }}</x-be.badge>
                                    @else
                                        <x-be.badge>MISSING</x-be.badge>
                                    @endif
                                </td>
                                <td>
                                    <div class="be-badge-row">
                                        <x-be.badge :variant="$branch->manager_count ? 'success' : 'neutral'">MGR {{ $branch->manager_count }}</x-be.badge>
                                        <x-be.badge :variant="$branch->cashier_count ? 'success' : 'neutral'">CSH {{ $branch->cashier_count }}</x-be.badge>
                                        <x-be.badge :variant="$branch->courier_count ? 'success' : 'neutral'">CRR {{ $branch->courier_count }}</x-be.badge>
                                    </div>
                                </td>
                                <td class="centered">
                                    <x-be.button :href="route('be.branches.edit', $branch)" variant="neutral" size="sm">MODIFY</x-be.button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="table-empty">NO HUBS MATCH CURRENT FILTER</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </x-be.table>

            <x-be.pagination :paginator="$branches" />
        </x-be.panel>
    </div>
@endsection
