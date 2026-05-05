@extends('be.layouts.main')

@section('header_title', 'Daftar Hub')

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
                <div class="branch-summary__label">Jaringan Hub Nasional</div>
                <h3>{{ $totalBranches }} Hub</h3>
            </div>

            <x-be.stat label="Tampil" value="{{ $firstVisible }}-{{ $lastVisible }}" />
            <x-be.stat label="Cocok" :value="$currentTotal" />
            <x-be.stat label="Koordinat" value="{{ $coordinatePercent }}%" />
            <x-be.stat label="CREW" value="{{ $crewPercent }}%" />
        </x-be.panel>

        <x-be.panel>
            <form action="{{ route('be.branches.index') }}" method="GET" class="branch-filter">
                <div>
                    <label for="branch-search">Cari Hub</label>
                    <input id="branch-search" name="search" type="text" value="{{ $filters['search'] }}" placeholder="hub, provinsi, alamat, telepon...">
                </div>

                <div>
                    <label for="branch-coordinates">Koordinat</label>
                    <select id="branch-coordinates" name="coordinates">
                        <option value="" @selected($filters['coordinates'] === '')>Semua</option>
                        <option value="ready" @selected($filters['coordinates'] === 'ready')>Siap</option>
                        <option value="missing" @selected($filters['coordinates'] === 'missing')>Belum lengkap</option>
                    </select>
                </div>

                <x-be.button type="submit">Filter</x-be.button>
                <x-be.button :href="route('be.branches.index')" variant="neutral">Reset</x-be.button>
            </form>
        </x-be.panel>

        <x-be.panel>
            <div class="branch-toolbar mb-4">
                <div class="font-ui text-gray">Tampil {{ $firstVisible }}-{{ $lastVisible }} / {{ $currentTotal }}</div>
                <x-be.button :href="route('be.branches.create')">Tambah Hub</x-be.button>
            </div>

            <x-be.table min-width="860px">
                <table class="app-table branch-table">
                    <thead>
                        <tr>
                            <th>HUB</th>
                            <th>Lokasi</th>
                            <th>Kontak</th>
                            <th>Koordinat</th>
                            <th>CREW</th>
                            <th class="centered">Aksi</th>
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
                                            <div class="branch-subtext">{{ $branch->address ?: 'Alamat belum lengkap' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $branch->city ?: '-' }}</td>
                                <td>{{ $branch->phone ?: '-' }}</td>
                                <td>
                                    @if($branch->latitude && $branch->longitude)
                                        <x-be.badge variant="success">{{ number_format((float) $branch->latitude, 3) }}, {{ number_format((float) $branch->longitude, 3) }}</x-be.badge>
                                    @else
                                        <x-be.badge>Belum lengkap</x-be.badge>
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
                                    <x-be.button :href="route('be.branches.edit', $branch)" variant="neutral" size="sm">Edit</x-be.button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="table-empty">Tidak ada hub yang cocok dengan filter</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </x-be.table>

            <x-be.pagination :paginator="$branches" />
        </x-be.panel>
    </div>
@endsection
