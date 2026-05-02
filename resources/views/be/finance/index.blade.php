@extends('be.layouts.main')

@section('header_title', 'FINANCE REPORT: ' . auth()->user()->branch->name)

@section('content')
    <x-be.grid columns="3">
        <div class="hud-panel metric-card" style="border-color: var(--color-primary);">
            <div class="font-ui text-gray" style="font-size: 0.8rem;">Pendapatan Tunai Harian</div>
            <div class="font-bank text-primary" style="font-size: 2.5rem; margin-top: 10px;">Rp {{ number_format($dailyCash, 0, ',', '.') }}</div>
            <div class="font-ui text-main" style="font-size: 0.7rem; margin-top: 10px;">Uang tunai yang diterima hari ini.</div>
        </div>
        <div class="hud-panel metric-card metric-card--accent" style="border-color: var(--color-accent);">
            <div class="font-ui text-gray" style="font-size: 0.8rem;">Pendapatan Digital Harian</div>
            <div class="font-bank text-accent" style="font-size: 2.5rem; margin-top: 10px;">Rp {{ number_format($dailyDigital, 0, ',', '.') }}</div>
            <div class="font-ui text-main" style="font-size: 0.7rem; margin-top: 10px;">Pembayaran QRIS dan transfer hari ini.</div>
        </div>
        <div class="hud-panel metric-card" style="border-color: var(--color-text-main);">
            <div class="font-ui text-gray" style="font-size: 0.8rem;">Total Omset Harian</div>
            <div class="font-bank text-main" style="font-size: 2.5rem; margin-top: 10px;">Rp {{ number_format($dailyOmset, 0, ',', '.') }}</div>
            <div class="font-ui text-main" style="font-size: 0.7rem; margin-top: 10px;">Total semua pembayaran hari ini.</div>
        </div>
    </x-be.grid>

    <x-be.panel>
        <h3 class="font-bank text-main mb-4">Riwayat Pembayaran</h3>
        
        <x-be.table min-width="860px">
            <table class="app-table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>No. Resi</th>
                        <th>Penerima</th>
                        <th>Tujuan</th>
                        <th>Metode</th>
                        <th>Jumlah</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                    <tr>
                        <td>{{ $payment->payment_date ? $payment->payment_date->format('d M y') : '-' }}</td>
                        <td><a href="{{ route('be.shipments.show', $payment->shipment_id) }}" class="text-primary hover-glow">#{{ $payment->shipment->tracking_number ?? 'UNKNOWN' }}</a></td>
                        <td>{{ $payment->shipment->receiver->name ?? 'Unknown' }}</td>
                        <td>{{ $payment->shipment->destinationBranch->name ?? 'Unknown' }}</td>
                        <td style="color: {{ $payment->payment_method == 'cash' ? 'var(--color-primary)' : 'var(--color-accent)' }}">{{ strtoupper($payment->payment_method) }}</td>
                        <td class="font-bank text-main" style="font-size: 1.1rem;">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                        <td style="color: {{ $payment->payment_status == 'paid' ? 'var(--color-primary)' : 'var(--color-danger, red)' }}">{{ strtoupper($payment->payment_status) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="table-empty">Tidak ada data</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </x-be.table>
        
        <x-be.pagination :paginator="$payments" />
    </x-be.panel>
@endsection
