@extends('fe.layouts.main')

@push('head_assets')
    <style>
        .dashboard-shell {
            margin-top: 5rem;
        }

        .dashboard-empty-actions,
        .dashboard-notification-list {
            display: grid;
            gap: 0.85rem;
        }

        .dashboard-empty-actions {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
            align-items: stretch;
        }

        .dashboard-notification {
            border-color: var(--glass-control-border);
        }

        .dashboard-notification.is-unread {
            border-color: var(--color-primary);
        }

        .dashboard-notification__body {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 1rem;
            align-items: start;
        }

        .dashboard-notification__meta {
            display: flex;
            gap: 0.65rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .dashboard-notification__title {
            font-size: 1rem;
            font-weight: 900;
            margin-top: 0.45rem;
        }

        .dashboard-order-card {
            margin-bottom: 1.25rem;
        }

        .dashboard-lifecycle-body {
            display: grid;
            grid-template-columns: minmax(0, 1.3fr) minmax(220px, 0.7fr);
            gap: 1.5rem;
            margin-top: 1.1rem;
        }

        .dashboard-payment-stack {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-top: 0.45rem;
        }

        .dashboard-lifecycle-steps {
            display: grid;
            grid-template-columns: repeat(7, minmax(74px, 1fr));
            gap: 0.65rem;
        }

        .dashboard-lifecycle-step {
            border: 2px solid var(--color-panel-border);
            border-radius: 8px;
            background: var(--color-panel);
            padding: 0.55rem 0.45rem;
            text-align: center;
        }

        .dashboard-lifecycle-step.is-done {
            background: var(--color-primary);
        }

        .dashboard-lifecycle-step__mark {
            color: var(--color-gray);
            font-size: 0.9rem;
            font-weight: 800;
        }

        .dashboard-lifecycle-step.is-done .dashboard-lifecycle-step__mark {
            color: var(--color-text-main);
        }

        .dashboard-lifecycle-step__label {
            font-size: 0.62rem;
            margin-top: 0.3rem;
        }

        .dashboard-order-footer {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: center;
        }

        @media (max-width: 900px) {
            .dashboard-empty-actions,
            .dashboard-lifecycle-body {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@section('content')
<div class="dashboard-shell">
    <x-fe.page-header title="Dashboard" subtitle="Halo {{ $user->name }}, semua pickup dan pengiriman kamu ada di sini.">
        <x-fe.button href="{{ route('order.create') }}" variant="primary" style="font-size: 0.8rem;">Buat Order</x-fe.button>
        <x-fe.button href="{{ route('profile') }}" variant="secondary" style="font-size: 0.8rem;">Profil</x-fe.button>
    </x-fe.page-header>

    @if($errors->any())
        <x-fe.alert tone="danger" title="Ada yang perlu dicek">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </x-fe.alert>
    @endif

    @if($summary['total_orders'] === 0)
        <x-fe.panel title="Mulai Pengiriman Pertama" subtitle="Pilih jalur yang paling cepat buat kamu" variant="primary" class="section-animate" style="margin-bottom: 2rem;">
            <div class="dashboard-empty-actions">
                <div class="ops-card">
                    <span class="data-label">Langsung Order</span>
                    <p class="data-value" style="margin-top: 0.5rem;">Isi pickup, tujuan, berat, layanan, dan pembayaran.</p>
                    <div style="margin-top: 1rem;">
                        <x-fe.button href="{{ route('order.create') }}" variant="primary">Buat Order</x-fe.button>
                    </div>
                </div>
                <div class="ops-card">
                    <span class="data-label">Mulai Dari Estimasi</span>
                    <p class="data-value" style="margin-top: 0.5rem;">Cek ongkir dulu, lalu lanjut ke form order dengan data yang sama.</p>
                    <div style="margin-top: 1rem;">
                        <x-fe.button href="{{ route('home') }}#rates" variant="secondary">Cek Ongkir</x-fe.button>
                    </div>
                </div>
            </div>
        </x-fe.panel>
    @endif

    <div class="grid-4 section-animate" style="gap: 1rem; margin-bottom: 2rem;">
        <div class="metric-card">
            <div class="data-label">Total Order</div>
            <div class="metric-value">{{ $summary['total_orders'] }}</div>
        </div>
        <div class="metric-card metric-card--accent">
            <div class="data-label">Menunggu Bayar</div>
            <div class="metric-value">{{ $summary['waiting_payment'] }}</div>
        </div>
        <div class="metric-card">
            <div class="data-label">Paket Aktif</div>
            <div class="metric-value">{{ $summary['active_shipments'] }}</div>
        </div>
        <div class="metric-card metric-card--accent">
            <div class="data-label">Terkirim</div>
            <div class="metric-value">{{ $summary['delivered'] }}</div>
        </div>
    </div>

    @if($notifications->isNotEmpty())
        <x-fe.panel title="Update Paket" subtitle="{{ $summary['unread_notifications'] }} belum dibaca" variant="primary" class="section-animate" style="margin-bottom: 2rem;">
            <div class="dashboard-notification-list">
                @foreach($notifications as $notification)
                    <div class="ops-card dashboard-notification {{ $notification['is_unread'] ? 'is-unread' : '' }}">
                        <div class="dashboard-notification__body">
                            <div>
                                <div class="dashboard-notification__meta">
                                    <span class="data-label">{{ $notification['sent_at_label'] }}</span>
                                    @if($notification['is_unread'])
                                        <span class="status-chip status-chip--accent">BARU</span>
                                    @endif
                                </div>
                                <div class="text-main dashboard-notification__title">{{ $notification['title'] }}</div>
                                <p class="data-value" style="margin-top: 0.45rem;">{{ $notification['message'] }}</p>
                                @if($notification['tracking_url'])
                                    <a href="{{ $notification['tracking_url'] }}" class="text-accent" style="font-size: 0.78rem; font-weight: 900; text-decoration: none;">
                                        Lacak {{ $notification['tracking_number'] }}
                                    </a>
                                @endif
                            </div>
                            @if($notification['is_unread'])
                                <form action="{{ route('dashboard.notifications.read', $notification['id']) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn-neon" style="font-size: 0.68rem; padding: 0.55rem 0.75rem;">Dibaca</button>
                                </form>
                            @else
                                <span class="text-gray" style="font-size: 0.72rem;">Sudah dibaca</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </x-fe.panel>
    @endif

    <x-fe.panel title="Perjalanan Order" subtitle="Pickup, pembayaran, dan shipment dalam satu alur" variant="accent">
        @forelse($orderLifecycles as $order)
            <div class="ops-card dashboard-order-card">
                <div class="ops-card__topline">
                    <span class="ops-id {{ $order['has_shipment'] ? 'text-accent' : 'text-primary' }}">#{{ $order['reference'] }}</span>
                    <span class="status-chip {{ $order['status_class'] }}">{{ strtoupper((string) $order['main_status']) }}</span>
                </div>

                <div class="dashboard-lifecycle-body">
                    <div>
                        <span class="data-label">Tujuan</span>
                        <p class="data-value">
                            {{ $order['receiver_name'] ?: 'Penerima belum diisi' }} / {{ $order['destination'] ?: 'Tujuan belum diisi' }}
                        </p>
                        @if($order['pickup_address'])
                            <div class="ops-divider">
                                <span class="data-label">Alamat Pickup</span>
                                <p class="data-value">{{ $order['pickup_address'] }}</p>
                            </div>
                        @endif
                    </div>
                    <div>
                        <span class="data-label">Pembayaran</span>
                        <div class="dashboard-payment-stack">
                            <span class="status-chip {{ $order['payment_class'] }}">{{ strtoupper((string) $order['payment_status'] ?: 'UNSET') }}</span>
                            <span class="text-gray" style="font-size: 0.75rem;">{{ strtoupper((string) $order['payment_method'] ?: 'Belum dipilih') }}</span>
                            @if($order['total_price'])
                                <span class="money-line">Rp {{ number_format((float) $order['total_price'], 0, ',', '.') }}</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="ops-divider">
                    <div class="dashboard-lifecycle-steps">
                        @foreach($stepLabels as $key => $label)
                            <div class="dashboard-lifecycle-step {{ $order['steps'][$key] ? 'is-done' : '' }}">
                                <div class="dashboard-lifecycle-step__mark">{{ $order['steps'][$key] ? 'OK' : '-' }}</div>
                                <div class="text-gray dashboard-lifecycle-step__label">{{ $label }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="ops-divider dashboard-order-footer">
                    <div>
                        @if($order['latest_tracking'])
                            <span class="data-label">Tracking Terakhir</span>
                            <p class="data-value">{{ strtoupper($order['latest_tracking']->status) }} / {{ $order['latest_tracking']->location }} / {{ $order['latest_tracking']->tracked_at->format('d M H:i') }}</p>
                        @else
                            <span class="data-label">Update Terakhir</span>
                            <p class="data-value">{{ $order['has_shipment'] ? 'Shipment aktif, menunggu update tracking berikutnya.' : 'Hub akan mengaktifkan shipment setelah pickup dan payment diverifikasi.' }}</p>
                        @endif
                    </div>

                    @if($order['has_shipment'])
                        <a href="{{ route('track.show') }}?receipt={{ urlencode($order['reference']) }}" class="btn-neon" style="text-decoration: none; font-size: 0.72rem;">Lacak Paket</a>
                    @else
                        <span class="text-gray" style="font-size: 0.72rem;">Nomor resi belum aktif</span>
                    @endif
                </div>

                <div class="next-action-box">
                    <span class="data-label">Langkah Berikutnya</span>
                    <div class="text-main" style="font-size: 0.84rem;">{{ $order['next_action'] }}</div>
                </div>

                @if($order['can_reschedule'] || $order['can_cancel'] || $order['can_reupload_payment'])
                    <div class="customer-action-panel">
                        <span class="data-label">Aksi Tersedia</span>

                        @if($order['can_reupload_payment'])
                            <form action="{{ route('order.payment-proof', $order['id']) }}" method="POST" enctype="multipart/form-data" class="customer-action-form">
                                @csrf
                                <label class="text-gray" for="payment_proof_{{ $order['id'] }}">Upload ulang bukti transfer</label>
                                <input type="file" id="payment_proof_{{ $order['id'] }}" name="payment_proof" accept="image/*" required>
                                <button type="submit" class="btn-neon">Kirim Review</button>
                            </form>
                        @endif

                        @if($order['can_reschedule'])
                            <form action="{{ route('order.reschedule', $order['id']) }}" method="POST" class="customer-action-form customer-action-form--inline">
                                @csrf
                                <label class="text-gray" for="pickup_date_{{ $order['id'] }}">Jadwal ulang pickup</label>
                                <input type="date" id="pickup_date_{{ $order['id'] }}" name="pickup_date" value="{{ $order['pickup_date'] }}" min="{{ now()->format('Y-m-d') }}" required>
                                <button type="submit" class="btn-neon">Update Tanggal</button>
                            </form>
                        @endif

                        @if($order['can_cancel'])
                            <form action="{{ route('order.cancel', $order['id']) }}" method="POST" onsubmit="return confirm('Cancel this pickup request before courier assignment?');" class="customer-action-form customer-action-form--danger">
                                @csrf
                                <button type="submit" class="btn-secondary">Batalkan Request</button>
                                <span class="text-gray">Bisa dilakukan sebelum kurir ditugaskan.</span>
                            </form>
                        @endif
                    </div>
                @endif
            </div>
        @empty
            <div class="empty-state">
                <p class="text-gray" style="font-size: 0.95rem;">Belum ada pengiriman aktif.</p>
                <p class="text-gray" style="font-size: 0.82rem; margin-top: 0.75rem;">Buat pickup pertama, lalu progres pembayaran dan pengiriman akan tampil di sini.</p>
                <div style="margin-top: 1.5rem;">
                    <x-fe.button href="{{ route('order.create') }}" variant="primary">Buat Order Pertama</x-fe.button>
                </div>
            </div>
        @endforelse
    </x-fe.panel>
</div>
@endsection
