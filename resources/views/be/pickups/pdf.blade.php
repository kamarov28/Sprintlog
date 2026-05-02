<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
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
    .dash { border-top: 2px dashed #151515; margin: 3mm 0; }
    .solid { border-top: 2px solid #151515; margin: 3mm 0; }
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
        font-size: 14pt;
        font-weight: 900;
        letter-spacing: 1px;
        word-break: break-word;
    }
    .label {
        color: #3f4652;
        font-size: 6.5pt;
        font-weight: 900;
        letter-spacing: 1px;
        text-transform: uppercase;
    }
    .value { font-size: 8.8pt; font-weight: 900; }
    .muted { color: #3f4652; }
    .row { display: table; margin-bottom: 1.5mm; width: 100%; }
    .row > span { display: table-cell; vertical-align: top; }
    .row > span:last-child { text-align: right; }
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
    .address { font-size: 8pt; margin-top: .5mm; }
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
            <div class="tag">Surat Tanda Terima Barang</div>
        </div>
        <div class="solid"></div>

        <div class="tracking-box">
            <div class="label">No. Resi Pickup</div>
            <div class="tracking-id">SPL-{{ str_pad($pickup->id, 6, '0', STR_PAD_LEFT) }}</div>
        </div>

        <div class="row">
            <span class="label">Tgl Terima</span>
            <span class="value">{{ now()->format('d/m/Y H:i') }}</span>
        </div>
        <div class="row">
            <span class="label">Hub</span>
            <span class="value">{{ $branch->name ?? 'PUSAT' }}</span>
        </div>
        @if($pickup->courier)
            <div class="row">
                <span class="label">Kurir</span>
                <span class="value">{{ strtoupper($pickup->courier->name) }}</span>
            </div>
        @endif

        <div class="dash"></div>

        <div class="section-title">Pengirim</div>
        <div class="panel">
            <div class="label">Nama</div>
            <div class="value">{{ strtoupper($pickup->sender_name ?? $pickup->customer_name) }}</div>
            <div class="label" style="margin-top:1.5mm;">Telepon</div>
            <div class="value">{{ $pickup->sender_phone ?? $pickup->customer_phone }}</div>
            <div class="label" style="margin-top:1.5mm;">Alamat</div>
            <div class="address">{{ $pickup->sender_address ?? $pickup->pickup_address }}</div>
        </div>

        <div class="section-title">Penerima</div>
        <div class="panel pink">
            <div class="label">Nama</div>
            <div class="value">{{ strtoupper($pickup->receiver_name ?? '-') }}</div>
            <div class="label" style="margin-top:1.5mm;">Telepon</div>
            <div class="value">{{ $pickup->receiver_phone ?? '-' }}</div>
            <div class="label" style="margin-top:1.5mm;">Alamat</div>
            <div class="address">{{ $pickup->receiver_address ?? '-' }}</div>
        </div>

        @if($pickup->item_description ?? $pickup->notes ?? null)
            <div class="section-title">Keterangan</div>
            <div class="address">{{ $pickup->item_description ?? $pickup->notes }}</div>
            <div class="dash"></div>
        @endif

        <div class="footer">
            Dokumen ini adalah bukti resmi penerimaan barang.<br>
            Simpan resi ini untuk keperluan pelacakan.<br>
            <strong>sprintlog.id</strong>
        </div>
    </div>
</body>
</html>
