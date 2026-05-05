@extends('be.layouts.main')

@section('header_title', $isAdmin ? 'Monitor Ekonomi' : 'Dashboard Operasi')

@section('content')

    <div class="hud-panel mb-4">
        <div class="role-brief">
            <div>
                <div class="font-ui text-gray" style="font-size: 0.78rem;">{{ str_replace('_', ' ', $roleProfile['code']) }} / {{ $roleProfile['scope'] }}</div>
                <h3 class="font-bank text-main" style="font-size: clamp(1.6rem, 3vw, 2.4rem); margin-top: 0.4rem;">{{ $roleProfile['label'] }}</h3>
                <p class="font-ui text-gray" style="font-size: 0.86rem; line-height: 1.7; margin: 0.8rem 0 0;">{{ $roleProfile['summary'] }}</p>
            </div>
            <div class="role-brief__actions">
                @foreach($roleProfile['quick_actions'] as $action)
                    <x-be.button :href="route($action['route'], $action['params'] ?? [])" :variant="$action['tone'] ?? 'neutral'" size="sm">
                        {{ $action['label'] }}
                    </x-be.button>
                @endforeach
            </div>
        </div>

        <div class="role-responsibility-grid">
            <x-be.stat label="Fokus Kerja" :value="$roleProfile['primary_job']" />
            <x-be.stat label="Area Kerja" :value="$roleProfile['hub']" />
            <div class="be-stat role-responsibility-list">
                <div class="be-stat__label">Tanggung Jawab</div>
                @foreach($roleProfile['responsibilities'] as $item)
                    <div class="font-ui text-main" style="font-size: 0.76rem; line-height: 1.55;">- {{ $item }}</div>
                @endforeach
            </div>
        </div>
    </div>

    @if($isAdmin)
        <div class="hud-panel mb-4">
            <div class="hud-header mb-4">
                <div>
                    <h3 class="font-bank text-main">Monitor Ekonomi Hub</h3>
                    <div class="font-ui text-gray" style="font-size: 0.78rem; margin-top: 0.35rem;">Performa bulanan hub, {{ $adminEconomy['month_label'] }}</div>
                </div>
                <a href="{{ route('be.financial-reports.index') }}" class="be-btn be-btn--primary be-btn--sm">Laporan Lengkap</a>
            </div>

            <div class="qol-stat-grid">
                <x-be.stat label="Revenue Bulan Ini" value="Rp {{ number_format($adminEconomy['total_revenue'], 0, ',', '.') }}" />
                <x-be.stat label="Cash Revenue" value="Rp {{ number_format($adminEconomy['cash_revenue'], 0, ',', '.') }}" />
                <x-be.stat label="Digital Revenue" value="Rp {{ number_format($adminEconomy['digital_revenue'], 0, ',', '.') }}" />
                <x-be.stat label="Paket Lunas" :value="$adminEconomy['paid_packages']" />
                <x-be.stat label="Rata-rata per Hub" value="Rp {{ number_format($adminEconomy['avg_revenue_per_hub'], 0, ',', '.') }}" />
            </div>
        </div>

        <div class="hud-panel">
            <h3 class="font-bank text-main mb-2">Ranking Performa Hub</h3>
            <div class="font-ui text-gray" style="font-size: 0.78rem; margin-top: 0.35rem;">Revenue hub tertinggi bulan ini. Admin memantau performa bisnis, bukan pergerakan paket harian.</div>

            <div class="table-responsive">
                <table style="margin-top: 1.5rem; min-width: 860px;" class="app-table">
                    <thead>
                        <tr>
                            <th>HUB</th>
                            <th>Revenue</th>
                            <th>Cash</th>
                            <th>Digital</th>
                            <th>Paket Lunas</th>
                            <th>Belum Lunas</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($hubEconomics as $row)
                            <tr>
                                <td>
                                    <div class="font-ui text-accent" style="font-size: 0.88rem;">{{ $row['branch']->name }}</div>
                                    <div class="font-ui text-gray" style="font-size: 0.72rem;">{{ $row['branch']->city }}</div>
                                </td>
                                <td class="font-bank text-main">Rp {{ number_format($row['revenue'], 0, ',', '.') }}</td>
                                <td>Rp {{ number_format($row['cash'], 0, ',', '.') }}</td>
                                <td>Rp {{ number_format($row['transfer'], 0, ',', '.') }}</td>
                                <td>{{ number_format($row['paid_packages']) }}</td>
                                <td>
                                    <x-be.badge :variant="$row['pending_payments'] > 0 ? 'danger' : 'success'">
                                        {{ $row['pending_payments'] }}
                                    </x-be.badge>
                                </td>
                                <td>
                                    <a href="{{ route('be.financial-reports.index', ['branch_id' => $row['branch']->id]) }}" class="be-btn be-btn--sm be-btn--neutral">Laporan</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="table-empty">Belum ada data ekonomi bulan ini</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @else
    <div class="grid-2" style="gap: 2rem;">

        <!-- Stat Box 1 -->
        <div class="hud-panel metric-card metric-card--accent">
            <div class="font-ui text-gray mb-2" style="font-size: 0.9rem;">{{ $stat1Label }}</div>
            <div class="metric-value text-accent">
                {{ number_format($stat1Value) }}</div>
            <div class="metric-rule text-accent"></div>
        </div>

        <!-- Stat Box 2 -->
        <div class="hud-panel metric-card">
            <div class="font-ui text-gray mb-2" style="font-size: 0.9rem;">{{ $stat2Label }}</div>
            <div class="metric-value text-primary">
                {{ number_format($stat2Value) }}</div>
            <div class="metric-rule text-primary"></div>
        </div>

    </div>

    <div class="hud-panel" style="margin-top: 2rem;">
        <div class="hud-header mb-4">
            <div>
                <h3 class="font-bank text-main">Operasi Hari Ini</h3>
                <div class="font-ui text-gray" style="font-size: 0.78rem; margin-top: 0.35rem;">Snapshot kerja harian hub.</div>
            </div>
            <a href="{{ route('be.shipments.index', ['preset' => 'exception_open']) }}" class="be-btn be-btn--danger be-btn--sm">Kendala</a>
        </div>

        <div class="qol-stat-grid">
            <x-be.stat label="Terdaftar" :value="$todayOps['registered_today']" />
            <x-be.stat label="Outbound" :value="$todayOps['outbound_today']" />
            <x-be.stat label="Menunggu Terima" :value="$todayOps['inbound_waiting']" />
            <x-be.stat label="Gagal Antar" :value="$todayOps['failed_delivery']" />
            <x-be.stat label="Issue Terbuka" :value="$todayOps['open_exceptions']" />
        </div>
    </div>

    <div class="hud-panel" style="margin-top: 2rem;">
        <h3 class="font-bank text-main mb-2">{{ $isCashier ? 'Antrean Pickup Terbaru' : 'Operasi Terbaru' }}</h3>

        <div class="table-responsive">
            <table style="margin-top: 1.5rem; min-width: 680px;" class="app-table">
                <thead>
                    <tr style="border-bottom: 2px solid var(--color-panel-border); font-size: 0.9rem;">
                        @if ($isCashier)
                            <th style="padding: 1rem 0.5rem; color: var(--color-gray);">Request ID</th>
                            <th style="padding: 1rem 0.5rem; color: var(--color-gray);">Customer</th>
                            <th style="padding: 1rem 0.5rem; color: var(--color-gray);">Jadwal</th>
                            <th style="padding: 1rem 0.5rem; color: var(--color-gray);">STATUS</th>
                        @else
                            <th style="padding: 1rem 0.5rem; color: var(--color-gray);">No Tracking</th>
                            <th style="padding: 1rem 0.5rem; color: var(--color-gray);">Asal</th>
                            <th style="padding: 1rem 0.5rem; color: var(--color-gray);">Tujuan</th>
                            <th style="padding: 1rem 0.5rem; color: var(--color-gray);">STATUS</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @if ($isCashier)
                        @forelse($recentPickups as $pickup)
                            <tr style="border-bottom: 1px solid var(--color-panel-border);">
                                <td style="padding: 1rem 0.5rem;">REQ_UNIT_{{ $pickup->id }}</td>
                                <td style="padding: 1rem 0.5rem;">{{ $pickup->customer_name }}</td>
                                <td style="padding: 1rem 0.5rem;">{{ $pickup->pickup_date }}</td>
                                <td style="padding: 1rem 0.5rem;"><span style="color: var(--color-primary); font-weight: bold;">{{ str_replace('_', ' ', $pickup->status) }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 2rem; color: var(--color-gray);">Belum ada data pickup terbaru</td>
                            </tr>
                        @endforelse
                    @else
                        @forelse($recentOperations as $op)
                            <tr style="border-bottom: 1px solid var(--color-panel-border);">
                                <td style="padding: 1rem 0.5rem;">
                                    @if (!empty($shipmentLinksEnabled))
                                        <a href="{{ route('be.shipments.show', $op) }}"
                                            class="text-accent">{{ $op->tracking_number }}</a>
                                    @else
                                        <span class="text-accent">{{ $op->tracking_number }}</span>
                                    @endif
                                </td>
                                <td style="padding: 1rem 0.5rem;">{{ $op->originBranch->city }}</td>
                                <td style="padding: 1rem 0.5rem;">{{ $op->destinationBranch->city }}</td>
                                <td style="padding: 1rem 0.5rem;">
                                    <div class="be-badge-row">
                                        <x-be.badge :variant="$op->healthStatus() === 'exception' ? 'danger' : ($op->healthStatus() === 'on_time' ? 'success' : 'accent')">
                                            {{ $op->healthLabel() }}
                                        </x-be.badge>
                                        <x-be.badge variant="neutral">{{ strtoupper($op->status) }}</x-be.badge>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 2rem; color: var(--color-gray);">Belum ada data terbaru</td>
                            </tr>
                        @endforelse
                    @endif
                </tbody>
            </table>
        </div>
    </div>
    @endif

@endsection
