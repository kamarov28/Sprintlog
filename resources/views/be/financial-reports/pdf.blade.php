<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Keuangan SprintLog</title>
    <style>
        @page { margin: 22px 26px 30px; }
        * { box-sizing: border-box; }
        body {
            background: #fff3dc;
            color: #151515;
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            line-height: 1.45;
        }
        .sheet {
            background: #fffaf1;
            border: 2px solid #151515;
            border-right: 7px solid #151515;
            border-bottom: 7px solid #151515;
            padding: 22px;
        }
        .header {
            display: table;
            width: 100%;
            border-bottom: 3px solid #151515;
            padding-bottom: 14px;
        }
        .header-left,
        .header-right { display: table-cell; vertical-align: top; }
        .header-right { text-align: right; width: 34%; }
        .brand {
            font-family: DejaVu Serif, serif;
            font-size: 27px;
            font-weight: 900;
            letter-spacing: 5px;
            margin: 0;
            text-transform: uppercase;
        }
        .brand span { color: #ff71ae; }
        .subtitle {
            color: #3f4652;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 2px;
            margin-top: 2px;
            text-transform: uppercase;
        }
        .doc-type {
            background: #cdb8ff;
            border: 2px solid #151515;
            border-right: 5px solid #151515;
            border-bottom: 5px solid #151515;
            display: inline-block;
            font-size: 9px;
            font-weight: 900;
            letter-spacing: 2px;
            padding: 8px 13px;
            text-transform: uppercase;
        }
        .generated { color: #3f4652; margin-top: 8px; }
        .section { margin-top: 18px; }
        .hero {
            display: table;
            width: 100%;
        }
        .hero-main,
        .hero-side { display: table-cell; vertical-align: top; }
        .hero-main { width: 60%; padding-right: 18px; }
        .hero-side {
            background: #b8f4cf;
            border: 2px solid #151515;
            border-right: 6px solid #151515;
            border-bottom: 6px solid #151515;
            padding: 12px 14px;
            width: 40%;
        }
        .eyebrow {
            color: #3f4652;
            font-size: 8px;
            font-weight: 900;
            letter-spacing: 2px;
            text-transform: uppercase;
        }
        .title {
            font-family: DejaVu Serif, serif;
            font-size: 21px;
            font-weight: 900;
            letter-spacing: 1px;
            margin: 0 0 8px;
            text-transform: uppercase;
        }
        .copy { color: #3f4652; font-size: 11px; }
        .value { font-size: 14px; font-weight: 900; margin-top: 3px; }
        .spacer { height: 10px; }
        .summary { display: table; margin-top: 16px; width: 100%; border-spacing: 0; }
        .summary-row { display: table-row; }
        .card {
            background: #fffaf1;
            border: 2px solid #151515;
            border-right: 5px solid #151515;
            border-bottom: 5px solid #151515;
            display: table-cell;
            padding: 12px 13px;
            width: 25%;
        }
        .card + .card { border-left: 0; }
        .card-primary { background: #fff06d; }
        .card-mint { background: #b8f4cf; }
        .card-pink { background: #ff7ab8; }
        .card-lilac { background: #cdb8ff; }
        .metric {
            font-family: DejaVu Serif, serif;
            font-size: 19px;
            font-weight: 900;
            margin-top: 4px;
        }
        .breakdown { display: table; margin-top: 14px; width: 100%; border-spacing: 0; }
        .breakdown > div {
            background: #fffaf1;
            border: 2px solid #151515;
            border-right: 5px solid #151515;
            border-bottom: 5px solid #151515;
            display: table-cell;
            padding: 10px 12px;
            width: 33.33%;
        }
        .breakdown > div + div { border-left: 0; }
        .bar {
            background: #efe7d7;
            border: 1px solid #151515;
            height: 8px;
            margin-top: 8px;
            width: 100%;
        }
        .bar > span { background: #cdb8ff; display: block; height: 6px; }
        .bar.cash > span { background: #b8f4cf; }
        .bar.other > span { background: #ff7ab8; }
        table { border-collapse: collapse; margin-top: 10px; width: 100%; }
        th, td {
            border-bottom: 2px solid #151515;
            padding: 8px 7px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background: #151515;
            color: #fffaf1;
            font-size: 8px;
            font-weight: 900;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }
        tbody tr:nth-child(even) td { background: #fff2d6; }
        .amount { font-weight: 900; text-align: right; white-space: nowrap; }
        .center { text-align: center; }
        .muted { color: #555d6a; }
        .badge {
            background: #fff06d;
            border: 1.5px solid #151515;
            display: inline-block;
            font-size: 8px;
            font-weight: 900;
            letter-spacing: .8px;
            padding: 3px 7px;
            text-transform: uppercase;
        }
        .signatures { display: table; margin-top: 32px; width: 100%; }
        .signatures > div { display: table-cell; text-align: center; width: 50%; }
        .line {
            border-top: 2px solid #151515;
            margin: 42px auto 0;
            padding-top: 7px;
            width: 190px;
        }
        .footer {
            color: #3f4652;
            font-size: 8px;
            margin-top: 18px;
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

    <div class="sheet">
        <div class="header">
            <div class="header-left">
                <h1 class="brand">SPRINT<span>LOG</span></h1>
                <div class="subtitle">Hub financial performance report</div>
            </div>
            <div class="header-right">
                <div class="doc-type">Financial Report</div>
                <div class="generated">Generated {{ now()->format('d M Y H:i') }}</div>
            </div>
        </div>

        <div class="section hero">
            <div class="hero-main">
                <div class="eyebrow">Finance desk</div>
                <h2 class="title">Laporan Keuangan Hub</h2>
                <div class="copy">
                    Ringkasan pemasukan terverifikasi berdasarkan pembayaran berstatus paid.
                    Dipakai untuk monitoring kas hub, rekonsiliasi transaksi, dan review performa periode berjalan.
                </div>
            </div>
            <div class="hero-side">
                <div class="eyebrow">Hub</div>
                <div class="value">{{ $branchLabel }}</div>
                <div class="spacer"></div>
                <div class="eyebrow">Periode</div>
                <div class="value">{{ $periodLabel }}</div>
                <div class="spacer"></div>
                <div class="eyebrow">Disiapkan oleh</div>
                <div class="value">{{ auth()->user()->name }}</div>
            </div>
        </div>

        <div class="summary">
            <div class="summary-row">
                <div class="card card-primary">
                    <div class="eyebrow">Total Pendapatan</div>
                    <div class="metric">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</div>
                </div>
                <div class="card card-mint">
                    <div class="eyebrow">Jumlah Paket</div>
                    <div class="metric">{{ number_format($packageCount, 0, ',', '.') }}</div>
                </div>
                <div class="card card-lilac">
                    <div class="eyebrow">Rata-rata / Paket</div>
                    <div class="metric">Rp {{ number_format($averageRevenue, 0, ',', '.') }}</div>
                </div>
                <div class="card card-pink">
                    <div class="eyebrow">Transaksi Tunai</div>
                    <div class="metric">{{ $cashRatio }}%</div>
                </div>
            </div>
        </div>

        <div class="breakdown">
            <div>
                <div class="eyebrow">Tunai</div>
                <div class="value">Rp {{ number_format($cashPayments, 0, ',', '.') }}</div>
                <div class="bar cash"><span style="width: {{ $cashRatio }}%;"></span></div>
            </div>
            <div>
                <div class="eyebrow">Transfer</div>
                <div class="value">Rp {{ number_format($transferPayments, 0, ',', '.') }}</div>
                <div class="bar"><span style="width: {{ $transferRatio }}%;"></span></div>
            </div>
            <div>
                <div class="eyebrow">Metode Lain</div>
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
                                <span class="muted">{{ optional(optional($payment->shipment)->sender)->name ?? 'Pengirim' }} &gt; {{ optional(optional($payment->shipment)->receiver)->name ?? 'Penerima' }}</span>
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
            <div><div class="line">Manager Hub</div></div>
            <div><div class="line">Finance Reviewer</div></div>
        </div>

        <div class="footer">SprintLog Financial Report - generated from verified payment records</div>
    </div>
</body>
</html>
