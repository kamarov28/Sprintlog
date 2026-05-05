<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Bukti Pembayaran #{{ $payment->id }}</title>
    <style>
        @page { margin: 28px; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            color: #1d1d1f;
            background: #fff7e8;
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 12px;
            line-height: 1.45;
        }
        .document {
            border: 3px solid #1d1d1f;
            background: #fffdf7;
            padding: 24px;
        }
        .header {
            border-bottom: 3px solid #1d1d1f;
            padding-bottom: 16px;
            margin-bottom: 18px;
        }
        .brand {
            font-size: 30px;
            font-weight: 800;
            letter-spacing: 3px;
        }
        .brand span { color: #ff78b6; }
        .title {
            margin-top: 8px;
            font-size: 16px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .meta {
            margin-top: 6px;
            color: #606066;
            font-size: 11px;
        }
        .grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        .grid th {
            width: 34%;
            padding: 9px 10px;
            border-bottom: 1px dashed #c9c4b8;
            color: #606066;
            font-weight: 700;
            text-align: left;
        }
        .grid td {
            padding: 9px 10px;
            border-bottom: 1px dashed #c9c4b8;
            font-weight: 800;
            text-align: right;
        }
        .section-title {
            margin: 18px 0 8px;
            padding: 8px 10px;
            border: 2px solid #1d1d1f;
            background: #c8ffd7;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .proof-box {
            margin-top: 10px;
            padding: 12px;
            border: 2px solid #1d1d1f;
            background: #fff;
            text-align: center;
        }
        .proof-box img {
            max-width: 100%;
            max-height: 520px;
            object-fit: contain;
        }
        .empty-proof {
            padding: 42px 12px;
            color: #606066;
            font-weight: 700;
        }
        .footer {
            margin-top: 18px;
            color: #606066;
            font-size: 10px;
            text-align: center;
        }
    </style>
</head>
<body>
    @php
        $shipment = $payment->shipment;
        $proofPath = $payment->proof_file ? public_path('storage/' . $payment->proof_file) : null;
    @endphp

    <div class="document">
        <div class="header">
            <div class="brand">SPRINT<span>LOG</span></div>
            <div class="title">Bukti Pembayaran</div>
            <div class="meta">Dicetak pada {{ now()->format('d/m/Y H:i') }}</div>
        </div>

        <div class="section-title">Informasi Pembayaran</div>
        <table class="grid">
            <tr><th>ID Pembayaran</th><td>#{{ $payment->id }}</td></tr>
            <tr><th>Jumlah</th><td>Rp {{ number_format($payment->amount, 0, ',', '.') }}</td></tr>
            <tr><th>Metode</th><td>{{ strtoupper(str_replace('_', ' ', $payment->payment_method)) }}</td></tr>
            <tr><th>Status</th><td>{{ strtoupper(str_replace('_', ' ', $payment->payment_status)) }}</td></tr>
            <tr><th>Tanggal Bayar</th><td>{{ $payment->payment_date ? $payment->payment_date->format('d/m/Y') : '-' }}</td></tr>
        </table>

        @if($payment->bank_name)
            <div class="section-title">Informasi Transfer</div>
            <table class="grid">
                <tr><th>Bank</th><td>{{ $payment->bank_name }}</td></tr>
                <tr><th>Nomor Rekening</th><td>{{ $payment->account_number }}</td></tr>
            </table>
        @endif

        <div class="section-title">Informasi Pengiriman</div>
        <table class="grid">
            <tr><th>No. Resi</th><td>{{ $shipment?->tracking_number ?? '-' }}</td></tr>
            <tr><th>Pengirim</th><td>{{ $shipment?->sender?->name ?? '-' }}</td></tr>
            <tr><th>Penerima</th><td>{{ $shipment?->receiver?->name ?? '-' }}</td></tr>
        </table>

        <div class="section-title">Bukti Transfer</div>
        <div class="proof-box">
            @if($proofPath && file_exists($proofPath))
                <img src="{{ $proofPath }}" alt="Bukti Transfer">
            @else
                <div class="empty-proof">Bukti transfer belum tersedia.</div>
            @endif
        </div>

        <div class="footer">
            Dokumen ini dibuat khusus untuk cetak PDF dan tidak menyertakan sidebar atau navigasi backend.
        </div>
    </div>
</body>
</html>
