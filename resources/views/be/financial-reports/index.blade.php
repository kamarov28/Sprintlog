@extends('be.layouts.main')

@section('header_title', 'FINANCIAL REPORTS')

@section('content')
    <x-be.panel>
        <form method="GET">
            <x-be.form-grid>
                @if(auth()->user()->role === 'admin')
                    <x-be.field label="HUB">
                        <select name="branch_id">
                            <option value="">Semua Hub</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ $branchId == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </x-be.field>
                @endif
                <x-be.field label="TANGGAL MULAI">
                    <input type="date" name="start_date" value="{{ $startDate }}">
                </x-be.field>
                <x-be.field label="TANGGAL AKHIR">
                    <input type="date" name="end_date" value="{{ $endDate }}">
                </x-be.field>
                <button type="submit" class="btn-neon">FILTER REPORT</button>
            </x-be.form-grid>
        </form>

        <x-be.actions>
            <a href="{{ route('be.financial-reports.pdf', request()->query()) }}" class="btn-neon" target="_blank" rel="noopener">EXPORT PDF</a>
        </x-be.actions>
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
        <h3 class="font-bank text-main mb-4">MONTHLY REVENUE GRAPH</h3>
        <canvas id="revenueChart" width="400" height="200"></canvas>
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

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('revenueChart').getContext('2d');
        const monthlyData = @json($monthlyData);
        const labels = monthlyData.map(item => `${item.year}-${item.month.toString().padStart(2, '0')}`);
        const data = monthlyData.map(item => item.revenue);

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Pendapatan Bulanan',
                    data: data,
                    borderColor: '#4a00e5',
                    backgroundColor: 'rgba(166, 216, 0, 0.12)',
                    tension: 0.25,
                    fill: true,
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
@endsection
