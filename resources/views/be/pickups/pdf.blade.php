<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
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
            width: 30%;
            font-size: 7pt;
            text-transform: uppercase;
        }

        .value {
            font-weight: 700;
            word-break: break-word;
        }

        .amount {
            font-size: 10pt;
            font-weight: 700;
            white-space: nowrap;
        }

        .note {
            word-break: break-word;
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
<div class="receipt">
    <div class="center">
        <div class="brand">SPRINTLOG</div>
        <div class="subtitle">Tanda Terima Pickup</div>
        <div class="small">{{ $branch->name ?? 'SprintLog Hub' }}</div>
        <div class="small">{{ now()->format('d/m/Y H:i') }}</div>
    </div>

    <div class="line"></div>

    <div class="tracking">
        <span class="small">NO. RESI PICKUP</span>
        <span class="number">SPL-{{ str_pad($pickup->id, 6, '0', STR_PAD_LEFT) }}</span>
    </div>

    <div class="line"></div>

    <table>
        <tr>
            <td class="label">Tanggal</td>
            <td class="right">{{ now()->format('d/m/Y H:i') }}</td>
        </tr>
        @if($pickup->courier)
            <tr>
                <td class="label">Kurir</td>
                <td class="right">{{ $pickup->courier->name }}</td>
            </tr>
        @endif
        <tr>
            <td class="label">Status</td>
            <td class="right">{{ strtoupper((string) $pickup->status) }}</td>
        </tr>
    </table>

    <div class="section-title">Pengirim</div>
    <table>
        <tr>
            <td class="label">Nama</td>
            <td class="value">{{ $pickup->sender_name ?? $pickup->customer_name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Telp</td>
            <td>{{ $pickup->sender_phone ?? $pickup->customer_phone ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Alamat</td>
            <td>{{ $pickup->sender_address ?? $pickup->pickup_address ?? '-' }}</td>
        </tr>
    </table>

    <div class="section-title">Penerima</div>
    <table>
        <tr>
            <td class="label">Nama</td>
            <td class="value">{{ $pickup->receiver_name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Telp</td>
            <td>{{ $pickup->receiver_phone ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Alamat</td>
            <td>{{ $pickup->receiver_address ?? '-' }}</td>
        </tr>
    </table>

    <div class="line"></div>

    <table>
        <tr>
            <td class="label">Layanan</td>
            <td class="right value">{{ strtoupper((string) $pickup->service_type ?: '-') }}</td>
        </tr>
        <tr>
            <td class="label">Berat</td>
            <td class="right value">{{ number_format((float) $pickup->weight, 1, ',', '.') }} KG</td>
        </tr>
        <tr>
            <td class="label">Bayar</td>
            <td class="right">{{ strtoupper((string) $pickup->payment_method ?: '-') }}</td>
        </tr>
        <tr>
            <td class="label">Tagihan</td>
            <td class="right amount">Rp {{ number_format((float) $pickup->total_price, 0, ',', '.') }}</td>
        </tr>
    </table>

    @if($pickup->item_description ?? $pickup->notes ?? null)
        <div class="line"></div>
        <div class="section-title">Catatan</div>
        <div class="note">{{ $pickup->item_description ?? $pickup->notes }}</div>
    @endif

    <div class="line"></div>

    <div class="center small">
        Bukti resmi penerimaan pickup.<br>
        Nomor SPRINT aktif setelah paket dan payment diverifikasi hub.
    </div>

    <div class="signature">
        _______________________<br>
        Kurir / Petugas
    </div>

    <div class="cut">- - - - - - - - - - - - - - -</div>
</div>
</body>
</html>
