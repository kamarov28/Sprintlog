<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Resi Kasir - {{ $shipment->tracking_number }}</title>
    <style>
        @page { margin: 0; }
        * { box-sizing: border-box; }

        body {
            margin: 0;
            width: 72mm;
            padding: 3mm;
            background: #ffffff;
            color: #000000;
            font-family: "DejaVu Sans Mono", "Courier New", monospace;
            font-size: 8pt;
            line-height: 1.32;
        }

        .receipt {
            width: 100%;
        }

        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: 700; }
        .small { font-size: 7pt; }
        .muted { color: #000000; }

        .brand {
            font-size: 14pt;
            font-weight: 700;
            letter-spacing: 1.5px;
            line-height: 1;
        }

        .subtitle {
            margin-top: 1mm;
            font-size: 7pt;
            text-transform: uppercase;
        }

        .line {
            border-top: 1px dashed #000000;
            margin: 2.5mm 0;
        }

        .solid {
            border-top: 1px solid #000000;
            margin: 2.5mm 0;
        }

        .tracking {
            margin: 2mm 0;
            text-align: center;
            word-break: break-word;
        }

        .tracking .number {
            display: block;
            margin-top: 1mm;
            font-size: 10pt;
            font-weight: 700;
            letter-spacing: .4px;
        }

        .section-title {
            margin: 2mm 0 1mm;
            font-weight: 700;
            text-transform: uppercase;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td {
            padding: .7mm 0;
            vertical-align: top;
        }

        .label {
            width: 28%;
            font-size: 7pt;
            text-transform: uppercase;
        }

        .value {
            font-weight: 700;
            word-break: break-word;
        }

        .items td:last-child {
            text-align: right;
            white-space: nowrap;
        }

        .total td {
            padding-top: 1mm;
            font-size: 9pt;
            font-weight: 700;
        }

        .amount {
            font-size: 10pt;
            white-space: nowrap;
        }

        .signature {
            margin-top: 5mm;
            text-align: center;
        }

        .cut {
            margin-top: 3mm;
            text-align: center;
            font-size: 7pt;
            letter-spacing: .7px;
        }
    </style>
</head>
<body>
@php
    $payment = $shipment->payment;
    $sender = $shipment->sender;
    $receiver = $shipment->receiver;
    $originBranch = $shipment->originBranch;
    $destinationBranch = $shipment->destinationBranch;
@endphp

<div class="receipt">
    <div class="center">
        <div class="brand">SPRINTLOG</div>
        <div class="subtitle">Resi Pengiriman</div>
        <div class="small">{{ $originBranch->name ?? 'SprintLog Hub' }}</div>
        <div class="small">{{ optional($shipment->created_at)->format('d/m/Y H:i') }}</div>
    </div>

    <div class="line"></div>

    <div class="tracking">
        <span class="small">NO. RESI</span>
        <span class="number">{{ $shipment->tracking_number }}</span>
    </div>

    <div class="line"></div>

    <div class="section-title">Pengirim</div>
    <table>
        <tr>
            <td class="label">Nama</td>
            <td class="value">{{ $sender->name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Telp</td>
            <td>{{ $sender->phone ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Asal</td>
            <td>{{ $originBranch->city ?? $originBranch->name ?? '-' }}</td>
        </tr>
    </table>

    <div class="section-title">Penerima</div>
    <table>
        <tr>
            <td class="label">Nama</td>
            <td class="value">{{ $receiver->name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Telp</td>
            <td>{{ $receiver->phone ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Tujuan</td>
            <td>{{ $destinationBranch->city ?? $destinationBranch->name ?? '-' }}</td>
        </tr>
        @if($receiver?->address)
            <tr>
                <td class="label">Alamat</td>
                <td>{{ $receiver->address }}</td>
            </tr>
        @endif
    </table>

    <div class="line"></div>

    <div class="section-title">Detail Paket</div>
    <table class="items">
        @forelse($shipment->items as $item)
            <tr>
                <td>{{ $item->item_name }}</td>
                <td>{{ $item->quantity }}x</td>
            </tr>
        @empty
            <tr>
                <td>Paket</td>
                <td>1x</td>
            </tr>
        @endforelse
    </table>
    <table>
        <tr>
            <td class="label">Berat</td>
            <td class="right value">{{ number_format((float) $shipment->total_weight, 1, ',', '.') }} KG</td>
        </tr>
        @if($shipment->shipping_courier_service)
            <tr>
                <td class="label">Layanan</td>
                <td class="right value">{{ strtoupper($shipment->shipping_courier_service) }}</td>
            </tr>
        @endif
        @if($shipment->shipping_estimated_days)
            <tr>
                <td class="label">Estimasi</td>
                <td class="right">{{ $shipment->shipping_estimated_days }} hari</td>
            </tr>
        @endif
    </table>

    <div class="solid"></div>

    <table class="total">
        <tr>
            <td>Total</td>
            <td class="right amount">Rp {{ number_format((float) $shipment->total_price, 0, ',', '.') }}</td>
        </tr>
    </table>

    @if($payment)
        <table>
            <tr>
                <td class="label">Bayar</td>
                <td class="right">{{ strtoupper((string) $payment->payment_method) }}</td>
            </tr>
            @if($payment->payment_method === 'cash')
                <tr>
                    <td class="label">Tunai</td>
                    <td class="right">Rp {{ number_format((float) $payment->amount_received, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td class="label">Kembali</td>
                    <td class="right">Rp {{ number_format((float) $payment->change_amount, 0, ',', '.') }}</td>
                </tr>
            @endif
            <tr>
                <td class="label">Status</td>
                <td class="right">{{ strtoupper((string) $payment->payment_status) }}</td>
            </tr>
        </table>
    @endif

    <div class="line"></div>

    <div class="center small">
        Simpan resi ini untuk pelacakan.<br>
        Tracking: sprintlog.id/track
    </div>

    <div class="signature">
        _______________________<br>
        Kasir / Petugas
    </div>

    <div class="cut">- - - - - - - - - - - - - - -</div>
</div>
</body>
</html>
