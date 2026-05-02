@extends('be.layouts.main')

@section('header_title', 'PAYMENT PROOF')

@section('content')
    <div class="hud-panel" style="max-width: 760px; margin: 0 auto;">
        <h3 class="font-bank text-accent mb-4">BUKTI PEMBAYARAN</h3>

        <div class="form-cluster mb-4">
            <h4 class="font-bank text-main mb-2">PAYMENT INFORMATION</h4>
            <p class="font-ui text-main"><strong>ID Pembayaran:</strong> {{ $payment->id }}</p>
            <p class="font-ui text-main"><strong>Jumlah:</strong> Rp {{ number_format($payment->amount, 0, ',', '.') }}</p>
            <p class="font-ui text-main"><strong>Metode:</strong> {{ strtoupper($payment->payment_method) }}</p>
            <p class="font-ui text-main"><strong>Status:</strong> {{ strtoupper($payment->payment_status) }}</p>
            <p class="font-ui text-main"><strong>Tanggal:</strong> {{ $payment->payment_date ? $payment->payment_date->format('d/m/Y') : '-' }}</p>
        </div>

        @if($payment->bank_name)
            <div class="form-cluster form-cluster--accent mb-4">
                <h4 class="font-bank text-accent mb-2">TRANSFER INFORMATION</h4>
                <p class="font-ui text-main"><strong>Bank:</strong> {{ $payment->bank_name }}</p>
                <p class="font-ui text-main"><strong>Nomor Rekening:</strong> {{ $payment->account_number }}</p>
            </div>
        @endif

        <div class="form-cluster mb-4">
            <h4 class="font-bank text-main mb-2">SHIPMENT INFORMATION</h4>
            <p class="font-ui text-main"><strong>No. Resi:</strong> {{ $payment->shipment->tracking_number }}</p>
            <p class="font-ui text-main"><strong>Pengirim:</strong> {{ $payment->shipment->sender->name }}</p>
            <p class="font-ui text-main"><strong>Penerima:</strong> {{ $payment->shipment->receiver->name }}</p>
        </div>

        @if($payment->proof_file)
            <div class="form-cluster form-cluster--accent mb-4">
                <h4 class="font-bank text-accent mb-2">TRANSFER PROOF</h4>
                <img src="{{ asset('storage/' . $payment->proof_file) }}" alt="Bukti Transfer" style="max-width: 100%; border: 1px solid var(--color-panel-border);">
            </div>
        @endif

        <div class="font-ui text-gray" style="font-size: 0.78rem; text-align: center;">
            Dokumen ini dicetak pada {{ now()->format('d/m/Y H:i') }}
        </div>
    </div>
@endsection
