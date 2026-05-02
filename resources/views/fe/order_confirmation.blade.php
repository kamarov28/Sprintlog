@extends('fe.layouts.main')

@section('content')
<div style="margin-top: 5rem;">
    <x-fe.page-header title="Request Diterima" subtitle="Pickup sudah masuk antrean hub.">
        <x-fe.button href="{{ route('dashboard') }}" variant="primary" style="font-size: 0.8rem;">Dashboard</x-fe.button>
        <x-fe.button href="{{ route('order.create') }}" variant="secondary" style="font-size: 0.8rem;">Buat Lagi</x-fe.button>
    </x-fe.page-header>

    @if(session('success'))
        <x-fe.alert tone="success" title="Request diterima">
            <p>{{ session('success') }}</p>
        </x-fe.alert>
    @endif

    <div class="grid-2 section-animate" style="gap: 2rem; align-items: flex-start;">
        <x-fe.panel title="Ringkasan Request" subtitle="Simpan kode ini sampai shipment aktif" variant="primary">
            <div class="confirmation-code">
                REQ_UNIT_{{ str_pad($pickup->id, 5, '0', STR_PAD_LEFT) }}
            </div>

            <div class="form-cluster">
                <div class="summary-row"><span>Tanggal Pickup</span><strong>{{ $pickup->pickup_date }}</strong></div>
                <div class="summary-row"><span>Pengirim</span><strong>{{ $pickup->sender_name ?: $pickup->customer_name }}</strong></div>
                <div class="summary-row"><span>Kota Asal</span><strong>{{ $pickup->senderCity?->name ?: '-' }}</strong></div>
                <div class="summary-row"><span>Penerima</span><strong>{{ $pickup->receiver_name ?: '-' }}</strong></div>
                <div class="summary-row"><span>Kota Tujuan</span><strong>{{ $pickup->receiverCity?->name ?: '-' }}</strong></div>
                <div class="summary-row"><span>Layanan</span><strong>{{ strtoupper((string) $pickup->service_type) }}</strong></div>
                <div class="summary-row"><span>Pembayaran</span><strong>{{ strtoupper((string) $pickup->payment_method) }}</strong></div>
                <div class="summary-total">
                    <span class="data-label">Estimasi Total</span>
                    <div class="text-primary" style="font-size: 2rem; font-weight: 900;">Rp {{ number_format((float) $pickup->total_price, 0, ',', '.') }}</div>
                </div>
            </div>
        </x-fe.panel>

        <x-fe.panel title="Langkah Berikutnya" subtitle="Alur setelah request dibuat" variant="accent">
            <div class="confirmation-timeline">
                <div class="confirmation-step is-active">
                    <strong>01 / Request Masuk</strong>
                    <span>Hub menerima request dan akan assign courier.</span>
                </div>
                <div class="confirmation-step">
                    <strong>02 / Pickup Kurir</strong>
                    <span>Kurir mengambil paket sesuai jadwal. Untuk cash, bayar ke kurir saat pickup.</span>
                </div>
                <div class="confirmation-step">
                    <strong>03 / Pembayaran Beres</strong>
                    <span>Kasir hub verifikasi transfer atau setoran tunai kurir.</span>
                </div>
                <div class="confirmation-step">
                    <strong>04 / Shipment Aktif</strong>
                    <span>Tracking number SPRINT akan muncul di dashboard setelah hub mengaktifkan shipment.</span>
                </div>
            </div>

            <div class="next-action-box">
                <span class="data-label">Yang Perlu Kamu Lakukan</span>
                <div class="text-main" style="font-size: 0.86rem;">
                    @if($pickup->payment_method === 'cash_on_pickup')
                        Siapkan uang tunai sejumlah estimasi di atas. Kurir akan melaporkan uang diterima, lalu kasir hub memverifikasi setoran.
                    @else
                        Bukti transfer sudah masuk antrean review kasir hub. Jika ditolak, dashboard akan membuka tombol upload ulang.
                    @endif
                </div>
            </div>
        </x-fe.panel>
    </div>
</div>
@endsection
