<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Keuangan SprintLog</title>
    <style>
        @page {
            margin: 24px 28px 30px;
        }

        body {
            color: #141414;
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            line-height: 1.45;
        }

        .header {
            border-bottom: 3px solid #111;
            padding-bottom: 14px;
            position: relative;
        }

        .brand {
            font-size: 25px;
            font-weight: 800;
            letter-spacing: 5px;
            margin: 0;
            text-transform: uppercase;
        }

        .subtitle {
            color: #555;
            font-size: 9px;
            letter-spacing: 1.8px;
            margin-top: 3px;
            text-transform: uppercase;
        }

        .doc-meta {
            position: absolute;
            right: 0;
            top: 2px;
            text-align: right;
        }

        .doc-type {
            border: 1px solid #111;
            display: inline-block;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 1.5px;
            padding: 6px 10px;
            text-transform: uppercase;
        }

        .muted {
            color: #666;
        }

        .section {
            margin-top: 18px;
        }

        .intro {
            display: table;
            width: 100%;
        }

        .intro > div {
            display: table-cell;
            vertical-align: top;
        }

        .intro-main {
            width: 62%;
        }

        .intro-side {
            border-left: 3px solid #a6d800;
            padding-left: 14px;
            width: 38%;
        }

        .label {
            color: #555;
            font-size: 8px;
            font-weight: 700;
            letter-spacing: 1.2px;
            text-transform: uppercase;
        }

        .value {
            font-size: 13px;
            font-weight: 700;
            margin-top: 2px;
        }

        .title {
            font-size: 18px;
            font-weight: 800;
            letter-spacing: 1px;
            margin: 0 0 8px;
            text-transform: uppercase;
        }

        .summary {
            display: table;
            margin-top: 16px;
            width: 100%;
        }

        .summary-row {
            display: table-row;
        }

        .card {
            border: 1px solid #ddd;
            display: table-cell;
            padding: 12px 13px;
            width: 25%;
        }

        .card + .card {
            border-left: 0;
        }

        .card-primary {
            background: #f7fbea;
            border-color: #b5dd22;
        }

        .metric {
            font-size: 18px;
            font-weight: 800;
            margin-top: 4px;
        }

        .breakdown {
            display: table;
            margin-top: 14px;
            width: 100%;
        }

        .breakdown > div {
            border: 1px solid #e0e0e0;
            display: table-cell;
            padding: 9px 11px;
            width: 33.33%;
        }

        .breakdown > div + div {
            border-left: 0;
        }

        .bar {
            background: #eee;
            height: 6px;
            margin-top: 8px;
            width: 100%;
        }

        .bar > span {
            background: #4a00e5;
            display: block;
            height: 6px;
        }

        .bar.cash > span {
            background: #2f8f3a;
        }

        .bar.other > span {
            background: #777;
        }

        table {
            border-collapse: collapse;
            margin-top: 10px;
            width: 100%;
        }

        th,
        td {
            border-bottom: 1px solid #ddd;
            padding: 8px 6px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #111;
            color: #fff;
            font-size: 8px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        tbody tr:nth-child(even) td {
            background: #fafafa;
        }

        .amount {
            font-weight: 800;
            text-align: right;
            white-space: nowrap;
        }

        .center {
            text-align: center;
        }

        .badge {
            border: 1px solid #999;
            display: inline-block;
            font-size: 8px;
            font-weight: 700;
            letter-spacing: .8px;
            padding: 3px 7px;
            text-transform: uppercase;
        }

        .signatures {
            display: table;
            margin-top: 36px;
            width: 100%;
        }

        .signatures > div {
            display: table-cell;
            text-align: center;
            width: 50%;
        }

        .line {
            border-top: 1px solid #111;
            margin: 48px auto 0;
            padding-top: 7px;
            width: 190px;
        }

        .footer {
            bottom: -14px;
            color: #777;
            font-size: 8px;
            left: 0;
            position: fixed;
            right: 0;
            text-align: center;
        }
    </style>
</head>
<body>
    @php
        $branchLabel = $selectedBranch?->name ?? 'Semua Hub';
        $periodLabel = \Illuminate\Support\Carbon::parse($startDate)->format('d M Y') . ' - ' . \Illuminate\Support\Carbon::parse($endDate)->format('d M Y');
        $cashRatio = $totalRevenue > 0 ? round(($cashPayments / $totalRevenue) * 100) : 0;
        $transferRatio = $totalRevenue > 0 ? round(($transferPayments / $totalRevenue) * 100) : 0;
        $otherRatio = max(0, 100 - $cashRatio - $transferRatio);
    @endphp

    <div class="header">
        <h1 class="brand">SprintLog</h1>
        <div class="subtitle">Hub financial performance report</div>
        <div class="doc-meta">
            <div class="doc-type">Financial Report</div>
            <div class="muted" style="margin-top: 7px;">Generated {{ now()->format('d M Y H:i') }}</div>
        </div>
    </div>

    <div class="section intro">
        <div class="intro-main">
            <h2 class="title">Laporan Keuangan Hub</h2>
            <div class="muted">
                Ringkasan pemasukan terverifikasi berdasarkan pembayaran berstatus paid.
                Laporan ini dapat dipakai untuk monitoring kas hub, rekonsiliasi transaksi, dan review performa periode berjalan.
            </div>
        </div>
        <div class="intro-side">
            <div class="label">Hub</div>
            <div class="value">{{ $branchLabel }}</div>
            <div style="height: 10px;"></div>
            <div class="label">Periode</div>
            <div class="value">{{ $periodLabel }}</div>
            <div style="height: 10px;"></div>
            <div class="label">Disiapkan oleh</div>
            <div class="value">{{ auth()->user()->name }}</div>
        </div>
    </div>

    <div class="summary">
        <div class="summary-row">
            <div class="card card-primary">
                <div class="label">Total Pendapatan</div>
                <div class="metric">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</div>
            </div>
            <div class="card">
                <div class="label">Jumlah Paket</div>
                <div class="metric">{{ number_format($packageCount, 0, ',', '.') }}</div>
            </div>
            <div class="card">
                <div class="label">Rata-rata / Paket</div>
                <div class="metric">Rp {{ number_format($averageRevenue, 0, ',', '.') }}</div>
            </div>
            <div class="card">
                <div class="label">Transaksi Tunai</div>
                <div class="metric">{{ $cashRatio }}%</div>
            </div>
        </div>
    </div>

    <div class="breakdown">
        <div>
            <div class="label">Tunai</div>
            <div class="value">Rp {{ number_format($cashPayments, 0, ',', '.') }}</div>
            <div class="bar cash"><span style="width: {{ $cashRatio }}%;"></span></div>
        </div>
        <div>
            <div class="label">Transfer</div>
            <div class="value">Rp {{ number_format($transferPayments, 0, ',', '.') }}</div>
            <div class="bar"><span style="width: {{ $transferRatio }}%;"></span></div>
        </div>
        <div>
            <div class="label">Metode Lain</div>
            <div class="value">Rp {{ number_format($otherPayments, 0, ',', '.') }}</div>
            <div class="bar other"><span style="width: {{ $otherRatio }}%;"></span></div>
        </div>
    </div>

    <div class="section">
        <h2 class="title" style="font-size: 14px;">Detail Transaksi</h2>
        <table>
            <thead>
                <tr>
                    <th style="width: 7%;">No</th>
                    <th style="width: 13%;">Tanggal</th>
                    <th style="width: 22%;">Resi</th>
                    <th style="width: 18%;">Rute</th>
                    <th style="width: 14%;">Metode</th>
                    <th style="width: 26%; text-align: right;">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                    <tr>
                        <td class="center">{{ $loop->iteration }}</td>
                        <td>{{ optional($payment->payment_date)->format('d/m/Y') ?? '-' }}</td>
                        <td>
                            <strong>{{ optional($payment->shipment)->tracking_number ?? '-' }}</strong><br>
                            <span class="muted">{{ optional(optional($payment->shipment)->sender)->name ?? 'Pengirim' }} -> {{ optional(optional($payment->shipment)->receiver)->name ?? 'Penerima' }}</span>
                        </td>
                        <td>
                            {{ optional(optional($payment->shipment)->originBranch)->city ?? '-' }}<br>
                            <span class="muted">ke {{ optional(optional($payment->shipment)->destinationBranch)->city ?? '-' }}</span>
                        </td>
                        <td><span class="badge">{{ str_replace('_', ' ', $payment->payment_method ?? '-') }}</span></td>
                        <td class="amount">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="center muted">Tidak ada transaksi pada periode ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="signatures">
        <div>
            <div class="line">Manager Hub</div>
        </div>
        <div>
            <div class="line">Finance Reviewer</div>
        </div>
    </div>

    <div class="footer">
        SprintLog Financial Report - generated from verified payment records
    </div>
</body>
</html>
