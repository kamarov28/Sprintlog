@extends('fe.layouts.main')

@section('title', 'SprintLog | Public Tracking')

@push('head_assets')
    <style>
        .tracking-shell {
            margin-top: 4rem;
        }

        .tracking-summary {
            display: grid;
            grid-template-columns: minmax(0, 1.2fr) minmax(260px, 0.8fr);
            gap: clamp(1.25rem, 3vw, 2rem);
            align-items: stretch;
        }

        .tracking-route {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            flex-wrap: wrap;
            margin-top: 1rem;
        }

        .tracking-route__node {
            border: 1px solid var(--glass-control-border);
            border-radius: var(--radius-md);
            padding: 0.85rem 1rem;
            min-width: min(100%, 190px);
        }

        .tracking-route__arrow {
            color: var(--color-primary);
            font-family: 'BankGothic', sans-serif;
            letter-spacing: 0;
        }

        .tracking-progress {
            margin-top: 1.5rem;
        }

        .tracking-progress__bar {
            position: relative;
            height: 8px;
            border-radius: 999px;
            background: rgba(10, 10, 10, 0.08);
            overflow: hidden;
        }

        .tracking-progress__fill {
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, var(--color-primary), var(--color-accent));
        }

        .tracking-steps {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 0.75rem;
            margin-top: 1rem;
        }

        .tracking-step {
            min-width: 0;
        }

        .tracking-step__dot {
            width: 14px;
            height: 14px;
            border-radius: 50%;
            border: 1px solid rgba(10, 10, 10, 0.22);
            background: rgba(255, 255, 255, 0.72);
            margin-bottom: 0.5rem;
        }

        .tracking-step.is-done .tracking-step__dot,
        .tracking-step.is-current .tracking-step__dot {
            border-color: var(--color-primary);
            background: var(--color-primary);
            box-shadow: 0 0 0 5px rgba(166, 216, 0, 0.12);
        }

        .tracking-step.is-current .tracking-step__dot {
            background: var(--color-accent);
            border-color: var(--color-accent);
        }

        .tracking-timeline {
            display: grid;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .tracking-event {
            display: grid;
            grid-template-columns: 150px minmax(0, 1fr);
            gap: 1rem;
            padding: 1rem 0;
            border-top: 1px solid rgba(10, 10, 10, 0.08);
        }

        .tracking-event:first-child {
            border-top: 0;
            padding-top: 0;
        }

        .tracking-event__status {
            color: var(--color-text-main);
            font-size: 0.82rem;
            line-height: 1.45;
        }

        .tracking-event__desc {
            margin-top: 0.35rem;
            color: var(--color-gray);
            font-size: 0.8rem;
            line-height: 1.65;
        }

        .tracking-current {
            border-left: 3px solid var(--color-primary);
            padding-left: 1rem;
        }

        @media (max-width: 900px) {
            .tracking-summary,
            .tracking-event {
                grid-template-columns: 1fr;
            }

            .tracking-steps {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 560px) {
            .tracking-steps {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
    </style>
@endpush

@section('content')
    <div class="tracking-shell">
        <x-fe.page-header title="Tracking Paket" subtitle="Pantau perjalanan paket dari hub asal sampai alamat penerima." style="margin-bottom: 2rem;">
            <x-fe.button href="{{ route('home') }}" variant="secondary" style="font-size: 0.8rem;">Beranda</x-fe.button>
        </x-fe.page-header>

        <x-fe.panel title="Cek Resi" subtitle="{{ $shipment ? 'Data paket ditemukan' : 'Masukkan nomor resi' }}" variant="primary">
            <form action="{{ route('track.show') }}" method="GET" style="margin-top: 1.5rem;">
                <x-fe.input
                    label="Nomor Resi"
                    name="receipt"
                    placeholder="SPRINT-YYYYMMDD-XXXX"
                    value="{{ request('receipt') }}"
                    required
                />

                <x-fe.button type="submit" variant="primary" style="width: 100%; margin-bottom: 1.5rem;">Cek Resi</x-fe.button>
            </form>

            @if($shipment && $trackingFlow)
                <div class="tracking-summary">
                    <div>
                        <div class="data-label">Nomor Resi</div>
                        <div class="text-accent" style="font-size: clamp(1.15rem, 2.4vw, 1.8rem); margin-top: 0.35rem; font-weight: 900;">
                            {{ $shipment->tracking_number }}
                        </div>

                        <div class="tracking-route">
                            <div class="tracking-route__node">
                                <span class="data-label">Asal</span>
                                <div class="text-main" style="font-size: 0.88rem;">{{ optional($shipment->originBranch)->name ?? '-' }}</div>
                            </div>
                            <span class="tracking-route__arrow">-&gt;</span>
                            <div class="tracking-route__node">
                                <span class="data-label">Tujuan</span>
                                <div class="text-main" style="font-size: 0.88rem;">{{ optional($shipment->destinationBranch)->name ?? '-' }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="tracking-current">
                        <div class="data-label">Status Sekarang</div>
                        <div class="text-main" style="font-size: 1.35rem; margin-top: 0.4rem; font-weight: 900;">
                            {{ strtoupper($trackingFlow['status_label']) }}
                        </div>
                        <p class="text-gray" style="font-size: 0.82rem; line-height: 1.65; margin: 0.75rem 0 0;">
                            {{ $trackingFlow['status_message'] }}
                        </p>
                        @if($trackingFlow['latest_tracking'])
                            <p class="text-accent" style="font-size: 0.78rem; line-height: 1.6; margin: 0.75rem 0 0;">
                                Last scan: {{ $trackingFlow['latest_tracking']->location }} / {{ optional($trackingFlow['latest_tracking']->tracked_at)->format('d M Y H:i') }}
                            </p>
                        @endif
                    </div>
                </div>

                <div class="tracking-progress">
                    <div class="tracking-progress__bar" aria-hidden="true">
                        <div class="tracking-progress__fill" style="width: {{ $trackingFlow['progress'] }}%;"></div>
                    </div>

                    <div class="tracking-steps">
                        @foreach($trackingFlow['steps'] as $step)
                            <div class="tracking-step {{ $step['is_done'] ? 'is-done' : '' }} {{ $step['is_current'] ? 'is-current' : '' }}">
                                <div class="tracking-step__dot"></div>
                                <div class="text-main" style="font-size: 0.72rem; line-height: 1.35;">{{ $step['label'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>

                @if($shipment->legs->isNotEmpty())
                    <div class="tracking-timeline" style="margin-top: 1rem;">
                        <div class="data-label">Rute Rencana</div>

                        @foreach($shipment->legs as $leg)
                            <div class="tracking-event">
                                <div class="text-gray" style="font-size: 0.76rem;">
                                    LEG {{ str_pad($leg->sequence, 2, '0', STR_PAD_LEFT) }}<br>
                                    {{ strtoupper($leg->status) }}
                                </div>
                                <div>
                                    <div class="tracking-event__status">
                                        {{ strtoupper(optional($leg->originBranch)->name ?? '-') }} -> {{ strtoupper(optional($leg->destinationBranch)->name ?? '-') }}
                                    </div>
                                    <div class="tracking-event__desc">
                                        @if($leg->arrived_at)
                                            Arrived {{ $leg->arrived_at->format('d M Y H:i') }}.
                                        @elseif($leg->departed_at)
                                            Departed {{ $leg->departed_at->format('d M Y H:i') }}.
                                        @else
                                            Menunggu scan keberangkatan dari hub terkait.
                                        @endif
                                        @if($leg->planned_arrival_at)
                                            ETA {{ $leg->planned_arrival_at->format('d M Y H:i') }}.
                                        @endif
                                        @if($leg->delay_reason)
                                            Kendala: {{ $leg->delay_reason }}
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="tracking-timeline">
                    <div class="data-label">Timeline Scan</div>

                    @forelse($shipment->trackings->sortByDesc('tracked_at') as $log)
                        @php
                            $meta = [
                                'pending' => 'Paket terdaftar',
                                'picked_up' => 'Diproses hub asal',
                                'in_transit' => 'Dalam perjalanan',
                                'arrived_at_branch' => 'Tiba di hub',
                                'out_for_delivery' => 'Diantar kurir',
                                'delivered' => 'Terkirim',
                                'delivery_failed' => 'Pengantaran tertunda',
                                'rescheduled' => 'Dijadwalkan ulang',
                                'returned_to_hub' => 'Kembali ke hub',
                                'held' => 'Ditahan sementara',
                                'damaged' => 'Perlu pemeriksaan',
                                'lost' => 'Dalam investigasi',
                                'exception' => 'Ada kendala rute',
                                'cancelled' => 'Dibatalkan',
                            ][$log->status] ?? strtoupper($log->status);
                        @endphp
                        <div class="tracking-event">
                            <div class="text-gray" style="font-size: 0.76rem;">
                                {{ optional($log->tracked_at)->format('d M Y') ?? '-' }}<br>
                                {{ optional($log->tracked_at)->format('H:i') ?? '' }}
                            </div>
                            <div>
                                <div class="tracking-event__status">
                                    {{ strtoupper($meta) }} / {{ strtoupper($log->location) }}
                                </div>
                                <div class="tracking-event__desc">
                                    {{ $log->description ?: 'Scan tercatat oleh hub.' }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-gray" style="font-size: 0.85rem; margin: 0;">Belum ada event tracking.</p>
                    @endforelse
                </div>
            @elseif(request('receipt'))
                <x-fe.alert tone="danger" title="Resi belum ditemukan">
                    Nomor resi belum aktif atau belum terdaftar.
                </x-fe.alert>
                <div class="next-action-box" style="border-color: #ff4444; background: rgba(255, 68, 68, 0.06); margin-top: 1rem;">
                    <span class="data-label">Artinya</span>
                    <div class="text-gray" style="font-size: 0.82rem; line-height: 1.6;">
                        Nomor SPRINT baru bisa dilacak setelah hub menerima paket, memverifikasi pembayaran, dan mengaktifkan shipment. Kalau baru request pickup, cek dashboard dulu untuk status request.
                    </div>
                    @auth
                        <div style="margin-top: 1rem;">
                            <x-fe.button href="{{ route('dashboard') }}" variant="secondary" style="font-size: 0.75rem;">Cek Dashboard</x-fe.button>
                        </div>
                    @endauth
                </div>
            @else
                <div class="text-gray" style="font-size: 0.8rem;">Masukkan nomor resi untuk melihat status paket.</div>
                <div class="next-action-box" style="margin-top: 1rem;">
                    <span class="data-label">Tips Tracking</span>
                    <div class="text-gray" style="font-size: 0.82rem; line-height: 1.6;">
                        Gunakan nomor resi yang diawali SPRINT. Untuk pengiriman pickup, nomor ini muncul setelah paket dan pembayaran diverifikasi hub.
                    </div>
                </div>
            @endif
        </x-fe.panel>
    </div>
@endsection
