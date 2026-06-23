@extends('be.layouts.main')

@section('header_title', 'FINANCIAL REPORTS')

@section('content')
    <x-be.panel class="financial-filter-panel">
        <div class="financial-filter-header">
            <div>
                <h3 class="font-bank text-main">EXPORT PDF</h3>
                <div class="financial-filter-meta">{{ $periodOptions[$period] ?? 'Custom' }} / {{ $hubLabel }}</div>
            </div>
        </div>

        <form method="GET" class="financial-filter-form" id="financialReportForm">
            <div class="financial-filter-grid">
                <x-be.field label="PERIODE">
                    <select name="period" id="financialPeriod">
                        @foreach($periodOptions as $value => $label)
                            <option value="{{ $value }}" {{ $period === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </x-be.field>

                <x-be.field label="TANGGAL MULAI">
                    <input type="date" name="start_date" id="financialStartDate" value="{{ $startDate }}">
                </x-be.field>

                <x-be.field label="TANGGAL AKHIR">
                    <input type="date" name="end_date" id="financialEndDate" value="{{ $endDate }}">
                </x-be.field>

                @if(auth()->user()->role === 'admin')
                    <x-be.field label="CAKUPAN HUB">
                        <select name="hub_scope" id="financialHubScope">
                            <option value="all" {{ $hubScope === 'all' ? 'selected' : '' }}>Semua Hub</option>
                            <option value="single" {{ $hubScope === 'single' ? 'selected' : '' }}>Satu Hub</option>
                            <option value="selected" {{ $hubScope === 'selected' ? 'selected' : '' }}>Beberapa Hub</option>
                        </select>
                    </x-be.field>

                    <x-be.field label="HUB" class="financial-single-hub">
                        <select name="branch_id" id="financialSingleHub">
                            <option value="">Pilih Hub</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ (int) $branchId === (int) $branch->id ? 'selected' : '' }}>
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                    </x-be.field>

                    <x-be.field label="HUB DIPILIH" class="financial-hub-multi">
                        <select name="branch_ids[]" id="financialSelectedHubs" multiple size="4">
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ in_array((int) $branch->id, $branchIds, true) ? 'selected' : '' }}>
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                    </x-be.field>
                @else
                    <input type="hidden" name="hub_scope" value="single">
                @endif
            </div>

            <div class="financial-filter-actions">
                <button type="submit" class="btn-neon">FILTER REPORT</button>
                <button
                    type="submit"
                    class="btn-neon financial-export-button"
                    formaction="{{ route('be.financial-reports.pdf') }}"
                    formtarget="_blank"
                    rel="noopener"
                >
                    EXPORT PDF
                </button>
            </div>
        </form>
    </x-be.panel>

    <x-be.grid columns="3">
        <div class="hud-panel metric-card">
            <div class="font-ui text-gray" style="font-size: 0.8rem;">TOTAL PENDAPATAN</div>
            <div class="font-bank text-primary" style="font-size: 2.35rem; margin-top: 10px;">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</div>
        </div>
        <div class="hud-panel metric-card metric-card--accent">
            <div class="font-ui text-gray" style="font-size: 0.8rem;">PEMBAYARAN TUNAI</div>
            <div class="font-bank text-accent" style="font-size: 2.35rem; margin-top: 10px;">Rp {{ number_format($cashPayments, 0, ',', '.') }}</div>
        </div>
        <div class="hud-panel metric-card">
            <div class="font-ui text-gray" style="font-size: 0.8rem;">JUMLAH PAKET</div>
            <div class="font-bank text-main" style="font-size: 2.35rem; margin-top: 10px;">{{ $packageCount }}</div>
        </div>
    </x-be.grid>

    <x-be.panel>
        <div class="financial-chart-header">
            <h3 class="font-bank text-main mb-4">REVENUE GRAPH</h3>
            <span>{{ $chartLabel }}</span>
        </div>
        <div class="financial-chart-wrap">
            <canvas id="revenueChart" width="900" height="320" aria-label="{{ $chartLabel }}"></canvas>
        </div>
    </x-be.panel>

    <x-be.panel>
        <h3 class="font-bank text-main mb-4">TRANSACTION DETAIL</h3>
        <x-be.table>
            <table class="app-table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>No. Resi</th>
                        <th>Metode</th>
                        <th>Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                        <tr>
                            <td>{{ $payment->payment_date ? $payment->payment_date->format('d/m/Y') : '-' }}</td>
                            <td>{{ $payment->shipment->tracking_number }}</td>
                            <td>{{ strtoupper($payment->payment_method) }}</td>
                            <td class="font-bank">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="table-empty">Tidak ada data</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </x-be.table>
        @if(method_exists($payments, 'links'))
            <div style="margin-top: 1.25rem;">
                {{ $payments->links() }}
            </div>
        @endif
    </x-be.panel>

    <script>
        (() => {
            const presetDates = @json($presetDates);
            const periodSelect = document.getElementById('financialPeriod');
            const startInput = document.getElementById('financialStartDate');
            const endInput = document.getElementById('financialEndDate');
            const hubScope = document.getElementById('financialHubScope');
            const singleHubField = document.querySelector('.financial-single-hub');
            const multiHubField = document.querySelector('.financial-hub-multi');
            const singleHub = document.getElementById('financialSingleHub');
            const selectedHubs = document.getElementById('financialSelectedHubs');

            // Store original values to detect manual changes
            const originalStartDate = startInput?.value;
            const originalEndDate = endInput?.value;

            periodSelect?.addEventListener('change', (e) => {
                const dates = presetDates[e.target.value];

                if (!dates) {
                    return;
                }

                startInput.value = dates.start;
                endInput.value = dates.end;
            });

            // When user manually changes dates, set period to 'custom'
            const handleDateChange = () => {
                if (periodSelect.value !== 'custom') {
                    periodSelect.value = 'custom';
                }
            };

            startInput?.addEventListener('change', handleDateChange);
            endInput?.addEventListener('change', handleDateChange);

            const syncHubFields = () => {
                if (!hubScope) {
                    return;
                }

                const scope = hubScope.value;
                singleHubField?.classList.toggle('is-hidden', scope !== 'single');
                multiHubField?.classList.toggle('is-hidden', scope !== 'selected');

                if (singleHub) {
                    singleHub.disabled = scope !== 'single';
                }

                if (selectedHubs) {
                    selectedHubs.disabled = scope !== 'selected';
                }
            };

            hubScope?.addEventListener('change', syncHubFields);
            syncHubFields();
        })();

        (() => {
            const canvas = document.getElementById('revenueChart');
            const chartData = @json($chartData);
            const chartLabel = @json($chartLabel);
            const labels = chartData.map(item => item.label);
            const values = chartData.map(item => Number(item.revenue || 0));
            const formatCurrency = value => `Rp ${new Intl.NumberFormat('id-ID').format(Math.round(value))}`;

            window.sprintlogRevenueChart = {
                label: chartLabel,
                labels,
                data: values,
                total: values.reduce((sum, value) => sum + value, 0),
            };

            if (!canvas) {
                return;
            }

            const ctx = canvas.getContext('2d');

            const drawChart = () => {
                const parentWidth = Math.max(canvas.parentElement?.clientWidth || 900, 320);
                const ratio = window.devicePixelRatio || 1;
                const cssWidth = parentWidth;
                const cssHeight = 320;

                canvas.style.width = `${cssWidth}px`;
                canvas.style.height = `${cssHeight}px`;
                canvas.width = Math.floor(cssWidth * ratio);
                canvas.height = Math.floor(cssHeight * ratio);
                ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
                ctx.clearRect(0, 0, cssWidth, cssHeight);

                const padding = { top: 24, right: 28, bottom: 48, left: 92 };
                const width = cssWidth - padding.left - padding.right;
                const height = cssHeight - padding.top - padding.bottom;
                const maxValue = Math.max(...values, 1);
                const yMax = Math.ceil(maxValue / 10000) * 10000 || 10000;
                const xStep = values.length > 1 ? width / (values.length - 1) : width;

                ctx.lineWidth = 1;
                ctx.strokeStyle = '#ded3c0';
                ctx.fillStyle = '#25252b';
                ctx.font = '700 12px sans-serif';
                ctx.textAlign = 'right';
                ctx.textBaseline = 'middle';

                for (let i = 0; i <= 4; i += 1) {
                    const value = (yMax / 4) * i;
                    const y = padding.top + height - (height * i / 4);
                    ctx.beginPath();
                    ctx.moveTo(padding.left, y);
                    ctx.lineTo(cssWidth - padding.right, y);
                    ctx.stroke();
                    ctx.fillText(formatCurrency(value), padding.left - 12, y);
                }

                ctx.strokeStyle = '#151515';
                ctx.lineWidth = 2;
                ctx.beginPath();
                ctx.moveTo(padding.left, padding.top);
                ctx.lineTo(padding.left, padding.top + height);
                ctx.lineTo(cssWidth - padding.right, padding.top + height);
                ctx.stroke();

                const points = values.map((value, index) => ({
                    x: values.length > 1 ? padding.left + (xStep * index) : padding.left + width / 2,
                    y: padding.top + height - ((value / yMax) * height),
                    value,
                }));

                ctx.beginPath();
                points.forEach((point, index) => {
                    index === 0 ? ctx.moveTo(point.x, point.y) : ctx.lineTo(point.x, point.y);
                });
                ctx.lineTo(points.at(-1)?.x ?? padding.left, padding.top + height);
                ctx.lineTo(points[0]?.x ?? padding.left, padding.top + height);
                ctx.closePath();
                ctx.fillStyle = 'rgba(184, 244, 207, 0.58)';
                ctx.fill();

                ctx.beginPath();
                points.forEach((point, index) => {
                    index === 0 ? ctx.moveTo(point.x, point.y) : ctx.lineTo(point.x, point.y);
                });
                ctx.strokeStyle = '#176b3b';
                ctx.lineWidth = 4;
                ctx.stroke();

                points.forEach(point => {
                    ctx.beginPath();
                    ctx.arc(point.x, point.y, 5, 0, Math.PI * 2);
                    ctx.fillStyle = '#fff06d';
                    ctx.fill();
                    ctx.strokeStyle = '#151515';
                    ctx.lineWidth = 2;
                    ctx.stroke();
                });

                ctx.fillStyle = '#25252b';
                ctx.font = '800 12px sans-serif';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'top';

                const maxVisibleLabels = Math.max(2, Math.floor(width / 92));
                const labelGap = Math.max(1, Math.ceil(labels.length / maxVisibleLabels));
                labels.forEach((label, index) => {
                    if (index % labelGap !== 0 && index !== labels.length - 1) {
                        return;
                    }

                    const x = values.length > 1 ? padding.left + (xStep * index) : padding.left + width / 2;
                    ctx.fillText(label, x, padding.top + height + 16);
                });

                if (values.every(value => value === 0)) {
                    ctx.fillStyle = '#25252b';
                    ctx.font = '900 16px sans-serif';
                    ctx.fillText('Tidak ada pendapatan pada filter ini', padding.left + width / 2, padding.top + height / 2);
                }
            };

            drawChart();

            if ('ResizeObserver' in window) {
                new ResizeObserver(drawChart).observe(canvas.parentElement);
            } else {
                window.addEventListener('resize', drawChart);
            }
        })();
    </script>
@endsection
