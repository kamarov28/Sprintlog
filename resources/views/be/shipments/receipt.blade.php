<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Resi Kasir - {{ $shipment->tracking_number }}</title>
    <style>
        @page { margin: 10px; }
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 11px;
            color: #000;
            margin: 0;
            padding: 0;
            width: 100%;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .divider { border-bottom: 1px dashed #000; margin: 8px 0; }
        .logo { font-size: 18px; font-weight: bold; margin-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; }
        td { vertical-align: top; padding: 2px 0; }
        .total-box { border-top: 1px dashed #000; border-bottom: 1px dashed #000; margin-top: 5px; padding: 5px 0; }
    </style>
</head>
<body>

    <div class="text-center">
        <div class="logo">SPRINTLOG</div>
        <div>{{ $shipment->originBranch->name ?? 'HUB KAMI' }}</div>
        <div>{{ date('d M Y H:i', strtotime($shipment->created_at)) }}</div>
        <div style="margin-top: 5px; font-size: 14px;" class="font-bold">#{{ $shipment->tracking_number }}</div>
    </div>

    <div class="divider"></div>

    <!-- SENDER & RECEIVER -->
    <table>
        <tr>
            <td style="width: 30%"><strong>FROM</strong></td>
            <td>: {{ \Illuminate\Support\Str::limit($shipment->sender->name, 15) }}<br>&nbsp;&nbsp;{{ $shipment->sender->phone }}</td>
        </tr>
        <tr>
            <td><strong>TO</strong></td>
            <td>: {{ \Illuminate\Support\Str::limit($shipment->receiver->name, 15) }}<br>&nbsp;&nbsp;{{ $shipment->receiver->phone }}</td>
        </tr>
        <tr>
            <td><strong>DEST</strong></td>
            <td>: {{ $shipment->destinationBranch->city ?? 'Unknown' }}</td>
        </tr>
    </table>

    <div class="divider"></div>

    <!-- ITEMS -->
    <div style="margin-bottom: 5px;"><strong>ITEMS:</strong></div>
    <table>
        @foreach($shipment->items as $item)
        <tr>
            <td style="width: 70%">- {{ $item->item_name }}</td>
            <td class="text-right">{{ $item->quantity }}x</td>
        </tr>
        @endforeach
    </table>
    
    <div style="margin-top: 5px;">
        Weight: {{ $shipment->total_weight }} KG
    </div>

    <div class="total-box">
        <table>
            <tr>
                <td class="font-bold">TOTAL TAGIHAN</td>
                <td class="text-right font-bold">Rp {{ number_format($shipment->total_price, 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    @if($shipment->payment)
    <table>
        <tr>
            <td>METODE BAYAR</td>
            <td class="text-right">{{ strtoupper($shipment->payment->payment_method) }}</td>
        </tr>
        @if($shipment->payment->payment_method === 'cash')
        <tr>
            <td>TUNAI DITERIMA</td>
            <td class="text-right">Rp {{ number_format($shipment->payment->amount_received, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>KEMBALIAN</td>
            <td class="text-right">Rp {{ number_format($shipment->payment->change_amount, 0, ',', '.') }}</td>
        </tr>
        @endif
        <tr>
            <td>STATUS</td>
            <td class="text-right" style="font-weight:bold;">{{ strtoupper($shipment->payment->payment_status) }}</td>
        </tr>
    </table>
    @endif

    <div class="divider"></div>
    <div class="text-center" style="margin-top: 10px; font-size: 10px;">
        Terima kasih telah menggunakan<br>layanan SprintLog Expedition.
        <br><br><br>
        -------------------------<br>
        Ttd. Kasir / Petugas
    </div>

</body>
</html>
