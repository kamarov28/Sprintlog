<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: 'Courier New', Courier, monospace;
        font-size: 9pt;
        color: #000;
        width: 72mm;
        padding: 4mm;
    }
    .center { text-align: center; }
    .bold { font-weight: bold; }
    .divider { border-top: 1px dashed #000; margin: 3mm 0; }
    .divider-solid { border-top: 1px solid #000; margin: 3mm 0; }
    .brand { font-size: 14pt; font-weight: bold; letter-spacing: 2px; text-align: center; }
    .sub-brand { font-size: 7pt; text-align: center; letter-spacing: 1px; color: #333; }
    .label { font-size: 7pt; color: #555; text-transform: uppercase; }
    .value { font-size: 9pt; font-weight: bold; }
    .tracking-box {
        border: 2px solid #000;
        padding: 2mm 3mm;
        text-align: center;
        margin: 3mm 0;
    }
    .tracking-id { font-size: 13pt; font-weight: bold; letter-spacing: 1px; }
    .row { display: flex; justify-content: space-between; margin-bottom: 1mm; }
    .section-title { font-size: 7pt; font-weight: bold; text-transform: uppercase; background: #000; color: #fff; padding: 1mm 2mm; margin-bottom: 2mm; }
    .footer { font-size: 7pt; text-align: center; color: #555; margin-top: 3mm; }
</style>
</head>
<body>

    {{-- Header --}}
    <div class="brand">SPRINTLOG</div>
    <div class="sub-brand">SURAT TANDA TERIMA BARANG</div>
    <div class="divider-solid"></div>

    {{-- Tracking ID --}}
    <div class="tracking-box">
        <div class="label">NO. RESI</div>
        <div class="tracking-id">SPL-{{ str_pad($pickup->id, 6, '0', STR_PAD_LEFT) }}</div>
    </div>

    {{-- Meta --}}
    <div class="row">
        <span class="label">TGL TERIMA</span>
        <span class="value">{{ now()->format('d/m/Y H:i') }}</span>
    </div>
    <div class="row">
        <span class="label">HUB</span>
        <span class="value">{{ $branch->name ?? 'PUSAT' }}</span>
    </div>
    @if($pickup->courier)
    <div class="row">
        <span class="label">KURIR</span>
        <span class="value">{{ strtoupper($pickup->courier->name) }}</span>
    </div>
    @endif

    <div class="divider"></div>

    {{-- Pengirim --}}
    <div class="section-title">PENGIRIM</div>
    <div class="label">NAMA</div>
    <div class="value">{{ strtoupper($pickup->sender_name ?? $pickup->customer_name) }}</div>
    <div class="label" style="margin-top:1mm;">TELEPON</div>
    <div class="value">{{ $pickup->sender_phone ?? $pickup->customer_phone }}</div>
    <div class="label" style="margin-top:1mm;">ALAMAT</div>
    <div style="font-size:8pt;">{{ $pickup->sender_address ?? $pickup->pickup_address }}</div>

    <div class="divider"></div>

    {{-- Penerima --}}
    <div class="section-title">PENERIMA</div>
    <div class="label">NAMA</div>
    <div class="value">{{ strtoupper($pickup->receiver_name ?? '-') }}</div>
    <div class="label" style="margin-top:1mm;">TELEPON</div>
    <div class="value">{{ $pickup->receiver_phone ?? '-' }}</div>
    <div class="label" style="margin-top:1mm;">ALAMAT</div>
    <div style="font-size:8pt;">{{ $pickup->receiver_address ?? '-' }}</div>

    <div class="divider"></div>

    {{-- Keterangan --}}
    @if($pickup->item_description ?? $pickup->notes ?? null)
    <div class="label">KETERANGAN</div>
    <div style="font-size:8pt;">{{ $pickup->item_description ?? $pickup->notes }}</div>
    <div class="divider"></div>
    @endif

    {{-- Footer --}}
    <div class="footer">
        Dokumen ini adalah bukti resmi penerimaan barang.<br>
        Simpan resi ini untuk keperluan pelacakan.<br>
        <strong>sprintlog.id</strong>
    </div>

</body>
</html>
