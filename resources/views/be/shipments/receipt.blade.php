<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Resi Kasir - {{ $shipment->tracking_number }}</title>
    <style>
        @page { margin: 0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: #fff3dc;
            color: #151515;
            font-family: DejaVu Sans, sans-serif;
            font-size: 8.5pt;
            line-height: 1.35;
            padding: 4mm;
            width: 72mm;
        }
        .receipt {
            background: #fffaf1;
            border: 2px solid #151515;
            border-right: 5px solid #151515;
            border-bottom: 5px solid #151515;
            padding: 4mm;
        }
        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: 900; }
        .brand {
            font-family: DejaVu Serif, serif;
            font-size: 18pt;
            font-weight: 900;
            letter-spacing: 3px;
            line-height: 1;
        }
        .brand span { color: #ff71ae; }
        .tag {
            background: #fff06d;
            border: 2px solid #151515;
            border-right: 4px solid #151515;
            border-bottom: 4px solid #151515;
            display: inline-block;
            font-size: 7pt;
            font-weight: 900;
            letter-spacing: 1px;
            margin-top: 2mm;
            padding: 1.5mm 2.5mm;
            text-transform: uppercase;
        }
        .tracking-box {
            background: #cdb8ff;
            border: 2px solid #151515;
            border-right: 5px solid #151515;
            border-bottom: 5px solid #151515;
            margin: 3mm 0;
            padding: 3mm;
            text-align: center;
        }
        .tracking-id {
            font-size: 12pt;
            font-weight: 900;
            letter-spacing: .5px;
            word-break: break-word;
        }
        .dash { border-top: 2px dashed #151515; margin: 3mm 0; }
        .solid { border-top: 2px solid #151515; margin: 3mm 0; }
        .label {
            color: #3f4652;
            font-size: 6.5pt;
            font-weight: 900;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .value { font-size: 8.8pt; font-weight: 900; }
        .muted { color: #3f4652; }
        table { border-collapse: collapse; width: 100%; }
        td { padding: 1.2mm 0; vertical-align: top; }
        .info td:first-child { width: 30%; }
        .section-title {
            background: #151515;
            color: #fffaf1;
            font-size: 7pt;
            font-weight: 900;
            letter-spacing: 1.5px;
            margin-bottom: 2mm;
            padding: 1.5mm 2mm;
            text-transform: uppercase;
        }
        .panel {
            background: #b8f4cf;
            border: 2px solid #151515;
            border-right: 4px solid #151515;
            border-bottom: 4px solid #151515;
            margin-bottom: 3mm;
            padding: 2.5mm;
        }
        .panel.pink { background: #ff7ab8; }
        .panel.lilac { background: #cdb8ff; }
        .total-box {
            background: #fff06d;
            border: 2px solid #151515;
            border-right: 5px solid #151515;
            border-bottom: 5px solid #151515;
            margin: 3mm 0;
            padding: 2.5mm;
        }
        .total-box .amount { font-size: 12pt; font-weight: 900; }
        .footer {
            color: #3f4652;
            font-size: 7pt;
            margin-top: 3mm;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="receipt">
        <div class="center">
            <div class="brand">SPRINT<span>LOG</span></div>
            <div class="tag">Resi Kasir Paket</div>
            <div class="muted" style="margin-top:2mm;">{{ $shipment->originBranch->name ?? 'HUB KAMI' }}</div>
            <div class="muted">{{ date('d M Y H:i', strtotime($shipment->created_at)) }}</div>
        </div>

        <div class="tracking-box">
            <div class="label">Tracking ID</div>
            <div class="tracking-id">{{ $shipment->tracking_number }}</div>
        </div>

        <div class="section-title">Rute</div>
        <div class="panel">
            <table class="info">
                <tr>
                    <td class="label">From</td>
                    <td class="value">{{ \Illuminate\Support\Str::limit($shipment->sender->name, 18) }}<br><span class="muted">{{ $shipment->sender->phone }}</span></td>
                </tr>
                <tr>
                    <td class="label">To</td>
                    <td class="value">{{ \Illuminate\Support\Str::limit($shipment->receiver->name, 18) }}<br><span class="muted">{{ $shipment->receiver->phone }}</span></td>
                </tr>
                <tr>
                    <td class="label">Dest</td>
                    <td class="value">{{ $shipment->destinationBranch->city ?? 'Unknown' }}</td>
                </tr>
            </table>
        </div>

        <div class="section-title">Items</div>
        <div class="panel pink">
            <table>
                @foreach($shipment->items as $item)
                    <tr>
                        <td>- {{ $item->item_name }}</td>
                        <td class="right bold" style="width: 24%;">{{ $item->quantity }}x</td>
                    </tr>
                @endforeach
            </table>
            <div class="dash"></div>
            <table>
                <tr>
                    <td class="label">Berat</td>
                    <td class="right value">{{ $shipment->total_weight }} KG</td>
                </tr>
            </table>
        </div>

        <div class="total-box">
            <table>
                <tr>
                    <td class="label">Total Tagihan</td>
                    <td class="right amount">Rp {{ number_format($shipment->total_price, 0, ',', '.') }}</td>
                </tr>
            </table>
        </div>

        @if($shipment->payment)
            <div class="section-title">Pembayaran</div>
            <div class="panel lilac">
                <table>
                    <tr>
                        <td class="label">Metode</td>
                        <td class="right value">{{ strtoupper($shipment->payment->payment_method) }}</td>
                    </tr>
                    @if($shipment->payment->payment_method === 'cash')
                        <tr>
                            <td class="label">Tunai</td>
                            <td class="right value">Rp {{ number_format($shipment->payment->amount_received, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td class="label">Kembali</td>
                            <td class="right value">Rp {{ number_format($shipment->payment->change_amount, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td class="label">Status</td>
                        <td class="right value">{{ strtoupper($shipment->payment->payment_status) }}</td>
                    </tr>
                </table>
            </div>
        @endif

        <div class="footer">
            Terima kasih telah menggunakan<br>layanan SprintLog Expedition.<br><br>
            _________________________<br>
            Ttd. Kasir / Petugas
        </div>
    </div>
</body>
</html>
