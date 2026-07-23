@extends('be.layouts.main')

@section('header_title', 'Antrean Pickup')

@section('content')

    {{-- ═══ Filter Bar ══════════════════════════════════════════════════════ --}}
    <div class="hud-panel mb-4">
        <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; flex-wrap: wrap; margin-bottom: 1rem;">
            <div>
                <div class="font-ui text-gray" style="font-size: 0.78rem;">
                    {{ $pickups->total() }} request pickup
                    @if(count(array_filter($filters ?? [])))
                        &nbsp;<span style="color: var(--color-accent); font-size: 0.72rem;">— filter aktif</span>
                    @endif
                </div>
                <h3 class="font-bank text-main" style="font-size: clamp(1.2rem, 2.4vw, 1.7rem); margin-top: 0.3rem;">Antrean Pickup</h3>
            </div>
            @if(count(array_filter($filters ?? [])))
                <a href="{{ route('be.pickups.index') }}" class="be-btn be-btn--xs" style="background: rgba(252,165,165,0.08); border-color: rgba(252,165,165,0.25); color: #fca5a5; font-size: 0.72rem; min-height: 30px !important; border-radius: 8px !important; text-decoration: none; align-self: flex-start; margin-top: 0.6rem;">
                    ✕ Reset Filter
                </a>
            @endif
        </div>

        <form method="GET" action="{{ route('be.pickups.index') }}" id="filter-form">
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr 1fr; gap: 0.65rem; align-items: end;">

                {{-- Search --}}
                <div style="grid-column: span 2;">
                    <label style="display: block; font-size: 0.65rem; color: var(--color-gray); margin-bottom: 4px; letter-spacing: 0.06em;">CARI</label>
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                        placeholder="Nama, no. telp, atau alamat..."
                        style="width: 100%; box-sizing: border-box; height: 34px; font-size: 0.78rem; padding: 4px 10px; border-radius: 8px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); color: #f1f5f9;">
                </div>

                {{-- Status --}}
                <div>
                    <label style="display: block; font-size: 0.65rem; color: var(--color-gray); margin-bottom: 4px; letter-spacing: 0.06em;">STATUS</label>
                    <select name="status" style="width: 100%; height: 34px; font-size: 0.78rem; padding: 4px 8px; border-radius: 8px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); color: #f1f5f9;">
                        <option value="">Semua status</option>
                        @foreach(['pending','assigned','picked_up','hub_received','cancelled'] as $s)
                            <option value="{{ $s }}" {{ ($filters['status'] ?? '') === $s ? 'selected' : '' }}>{{ strtoupper($s) }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Payment status --}}
                <div>
                    <label style="display: block; font-size: 0.65rem; color: var(--color-gray); margin-bottom: 4px; letter-spacing: 0.06em;">STATUS BAYAR</label>
                    <select name="payment_status" style="width: 100%; height: 34px; font-size: 0.78rem; padding: 4px 8px; border-radius: 8px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); color: #f1f5f9;">
                        <option value="">Semua</option>
                        @foreach(['pending','awaiting_pickup_cash','cash_collected_by_courier','pending_transfer_verification','transfer_rejected','paid'] as $ps)
                            <option value="{{ $ps }}" {{ ($filters['payment_status'] ?? '') === $ps ? 'selected' : '' }}>{{ strtoupper($ps) }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Payment method --}}
                <div>
                    <label style="display: block; font-size: 0.65rem; color: var(--color-gray); margin-bottom: 4px; letter-spacing: 0.06em;">METODE BAYAR</label>
                    <select name="payment_method" style="width: 100%; height: 34px; font-size: 0.78rem; padding: 4px 8px; border-radius: 8px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); color: #f1f5f9;">
                        <option value="">Semua</option>
                        @foreach(['cash_on_pickup','transfer'] as $pm)
                            <option value="{{ $pm }}" {{ ($filters['payment_method'] ?? '') === $pm ? 'selected' : '' }}>{{ strtoupper($pm) }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Date from --}}
                <div>
                    <label style="display: block; font-size: 0.65rem; color: var(--color-gray); margin-bottom: 4px; letter-spacing: 0.06em;">TANGGAL MULAI</label>
                    <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"
                        style="width: 100%; box-sizing: border-box; height: 34px; font-size: 0.78rem; padding: 4px 8px; border-radius: 8px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); color: #f1f5f9;">
                </div>

                {{-- Date to --}}
                <div>
                    <label style="display: block; font-size: 0.65rem; color: var(--color-gray); margin-bottom: 4px; letter-spacing: 0.06em;">TANGGAL AKHIR</label>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"
                        style="width: 100%; box-sizing: border-box; height: 34px; font-size: 0.78rem; padding: 4px 8px; border-radius: 8px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); color: #f1f5f9;">
                </div>

                {{-- Per page --}}
                <div>
                    <label style="display: block; font-size: 0.65rem; color: var(--color-gray); margin-bottom: 4px; letter-spacing: 0.06em;">TAMPILKAN</label>
                    <select name="per_page" onchange="this.form.submit()" style="width: 100%; height: 34px; font-size: 0.78rem; padding: 4px 8px; border-radius: 8px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); color: #f1f5f9;">
                        @foreach([10, 15, 25, 50, 100] as $n)
                            <option value="{{ $n }}" {{ $perPage == $n ? 'selected' : '' }}>{{ $n }} per halaman</option>
                        @endforeach
                    </select>
                </div>

                {{-- Submit --}}
                <div style="display: flex; gap: 0.5rem;">
                    <button type="submit" class="be-btn be-btn--primary be-btn--sm" style="flex: 1; font-size: 0.75rem; min-height: 34px !important; border-radius: 8px !important;">Filter</button>
                </div>

            </div>
        </form>
    </div>

    <div class="hud-panel">
        @if(in_array(auth()->user()->role, ['manager', 'cashier'], true))
            {{-- Standalone bulk form: does NOT wrap the table, avoiding nested-form issues --}}
            <form id="bulk-pickup-form" method="POST" action="{{ route('be.pickups.bulk-auto-assign') }}" style="margin: 0; padding: 0;">
                @csrf

                {{-- Bulk Action Toolbar (hidden until ≥1 checkbox is checked) --}}
                <div id="bulk-toolbar" style="display: none; align-items: center; justify-content: space-between; gap: 1rem; padding: 0.85rem 1.25rem; margin-bottom: 1.2rem; border-radius: 12px; background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(255, 255, 255, 0.08);">
                    <div style="display: flex; align-items: center; gap: 0.85rem;">
                        <span style="font-size: 0.78rem; font-weight: 700; letter-spacing: 0.06em; color: var(--color-accent);">AKSI MASSAL — <span id="selected-count">0</span> terpilih</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                        <button type="button" id="btn-bulk-auto" class="be-btn be-btn--accent be-btn--sm" style="font-size: 0.75rem; padding: 0.35rem 0.85rem; min-height: 32px !important; border-radius: 8px !important;">Auto-Assign Terpilih</button>
                        <span style="color: rgba(255,255,255,0.15)">|</span>
                        <select id="bulk-courier-select" name="bulk_courier_id" style="height: 32px; min-height: 32px !important; font-size: 0.72rem; padding: 2px 8px; border-radius: 8px !important; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); color: #f1f5f9; box-sizing: border-box; width: 160px;">
                            <option value="" disabled selected>Pilih Kurir...</option>
                            @foreach($couriers as $courier)
                                <option value="{{ $courier->id }}">{{ $courier->name }}{{ $courier->vehicle ? ' ('.$courier->vehicle->type.')' : '' }}</option>
                            @endforeach
                        </select>
                        <button type="button" id="btn-bulk-assign" class="be-btn be-btn--primary be-btn--sm" style="font-size: 0.75rem; padding: 0.35rem 0.85rem; min-height: 32px !important; border-radius: 8px !important;">Assign Terpilih</button>
                    </div>
                </div>

                {{-- Hidden pickup IDs injected by JS before submit --}}
                <div id="bulk-hidden-ids"></div>
            </form>
        @endif

        {{-- ── Top info + pagination bar ──────────────────────────────── --}}
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; padding: 0 0 0.85rem;">
            <div style="font-size: 0.75rem; color: var(--color-gray);">
                Menampilkan
                <span style="color: #f1f5f9; font-weight: 600;">{{ $pickups->firstItem() ?? 0 }}–{{ $pickups->lastItem() ?? 0 }}</span>
                dari <span style="color: #f1f5f9; font-weight: 600;">{{ $pickups->total() }}</span> pickup
                @if($pickups->lastPage() > 1)
                    &nbsp;· halaman {{ $pickups->currentPage() }} / {{ $pickups->lastPage() }}
                @endif
            </div>
            <div>
                <x-be.pagination :paginator="$pickups" />
            </div>
        </div>

        <div class="table-responsive">
            <table style="min-width: 1260px;" class="app-table pickup-queue-table">
            <thead>
                <tr style="border-bottom: 2px solid var(--color-panel-border); font-size: 0.85rem; color: var(--color-gray);">
                    @if(in_array(auth()->user()->role, ['manager', 'cashier'], true))
                        <th style="padding: 1rem 0.5rem; width: 40px; text-align: center;">
                            <input type="checkbox" id="select-all-pickups" style="cursor: pointer; width: 16px; height: 16px; accent-color: var(--color-primary);">
                        </th>
                    @endif
                    <th style="padding: 1rem 0.5rem;">CUSTOMER</th>
                    <th style="padding: 1rem 0.5rem;">ADDRESS</th>
                    <th style="padding: 1rem 0.5rem;">SCHEDULE</th>
                    <th style="padding: 1rem 0.5rem;">STATUS</th>
                    <th style="padding: 1rem 0.5rem;">PAYMENT</th>
                    <th style="padding: 1rem 0.5rem;">ASSIGNED UNIT</th>
                    <th style="padding: 1rem 0.5rem;">NEXT</th>
                    <th style="padding: 1rem 0.5rem; text-align: center;">ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pickups as $pickup)
                @php
                    $nextAction = $pickup->nextActionHint(auth()->user()->role);
                @endphp
                <tr style="border-bottom: 1px solid var(--color-panel-border); font-size: 0.9rem;">
                    @if(in_array(auth()->user()->role, ['manager', 'cashier'], true))
                        <td style="padding: 1.2rem 0.5rem; text-align: center;">
                            @if($pickup->status === 'pending')
                                {{-- data-pickup-id: outside any form, JS collects these --}}
                                <input type="checkbox" class="pickup-select-checkbox" data-pickup-id="{{ $pickup->id }}" style="cursor: pointer; width: 16px; height: 16px; accent-color: var(--color-primary);">
                            @else
                                <input type="checkbox" disabled style="opacity: 0.3; width: 16px; height: 16px;">
                            @endif
                        </td>
                    @endif
                    <td style="padding: 1.2rem 0.5rem;">
                        <div style="font-weight: bold;">{{ $pickup->customer_name }}</div>
                        <div style="font-size: 0.75rem; color: var(--color-gray);">{{ $pickup->customer_phone }}</div>
                    </td>
                    <td style="padding: 1.2rem 0.5rem;">
                        <div style="font-size: 0.75rem; color: var(--color-primary); margin-bottom: 3px;">LOKASI JEMPUT</div>
                        <div style="font-size: 0.8rem; max-width: 250px; margin-bottom: 0.8rem;">{{ $pickup->pickup_address }}</div>
                        
                        @if($pickup->receiver_address)
                        <div style="font-size: 0.75rem; color: var(--color-accent); margin-bottom: 3px;">LOKASI ANTAR</div>
                        <div style="font-size: 0.8rem; max-width: 250px;">{{ $pickup->receiver_address }}</div>
                        @endif
                    </td>
                    <td style="padding: 1.2rem 0.5rem;">
                        <span class="text-accent">{{ $pickup->pickup_date }}</span>
                    </td>
                    <td style="padding: 1.2rem 0.5rem;">
                        @php
                            $statusVariants = [
                                'pending' => 'neutral',
                                'assigned' => 'accent',
                                'picked_up' => 'success',
                                'hub_received' => 'primary',
                                'cancelled' => 'danger',
                            ];
                            $statusVariant = $statusVariants[$pickup->status] ?? 'neutral';
                        @endphp
                        <x-be.badge :variant="$statusVariant">{{ strtoupper($pickup->status) }}</x-be.badge>
                    </td>
                    <td style="padding: 1.2rem 0.5rem;">
                        @php
                            $payVariants = [
                                'awaiting_pickup_cash' => 'accent',
                                'cash_collected_by_courier' => 'accent',
                                'pending_transfer_verification' => 'primary',
                                'transfer_rejected' => 'danger',
                                'paid' => 'success',
                                'pending' => 'neutral',
                            ];
                            $payVariant = $payVariants[$pickup->payment_status] ?? 'neutral';
                        @endphp
                        <div style="font-size: 0.75rem; color: var(--color-gray); margin-bottom: 0.35rem;">{{ strtoupper((string) $pickup->payment_method) }}</div>
                        <x-be.badge :variant="$payVariant">{{ strtoupper((string) $pickup->payment_status) }}</x-be.badge>
                        @if($pickup->cash_received_amount)
                            <div style="font-size: 0.72rem; color: var(--color-text-main); margin-top: 0.35rem;">
                                Rp {{ number_format((float) $pickup->cash_received_amount, 0, ',', '.') }}
                            </div>
                        @endif
                        @if($pickup->cashCollector)
                            <div style="font-size: 0.68rem; color: var(--color-gray); margin-top: 0.2rem;">
                                Kolektor: {{ $pickup->cashCollector->name }}
                            </div>
                        @endif
                        @if($pickup->latestStatusAudit)
                            <div style="font-size: 0.65rem; color: var(--color-gray); margin-top: 0.4rem;">
                                Audit: {{ strtoupper($pickup->latestStatusAudit->event) }}
                            </div>
                        @endif
                    </td>
                    <td style="padding: 1.2rem 0.5rem;">
                        @if(auth()->user()->role === 'courier')
                            <span class="font-ui text-primary">TUGAS ANDA</span>
                        @elseif($pickup->shipment)
                            <div class="font-ui text-gray" style="font-size: 0.68rem; line-height: 1.35;">Pickup: {{ $pickup->courier?->name ?? '-' }}</div>
                            <div class="font-ui {{ $pickup->shipment->courier ? 'text-primary' : 'text-gray' }}" style="font-size: 0.78rem; line-height: 1.35; margin-top: 0.25rem;">
                                Shipment: {{ $pickup->shipment->courier?->name ?? 'belum ditugaskan' }}
                            </div>
                        @elseif($pickup->courier)
                            <span class="font-ui text-primary">{{ $pickup->courier->name }}</span>
                        @elseif(in_array(auth()->user()->role, ['manager', 'cashier'], true))
                            @php
                                $transferPendingReview = $pickup->payment_method === 'transfer'
                                    && $pickup->payment_proof
                                    && $pickup->payment_status === 'pending_transfer_verification';
                            @endphp
                            @if($transferPendingReview)
                                {{-- Assignment locked: cashier must review transfer proof first --}}
                                <div style="display: flex; flex-direction: column; gap: 0.5rem; max-width: 200px;">
                                    <div style="display: flex; align-items: flex-start; gap: 0.4rem; padding: 0.55rem 0.65rem; background: rgba(252, 165, 165, 0.08); border: 1px solid rgba(252, 165, 165, 0.2); border-radius: 8px;">
                                        <svg xmlns="http://www.w3.org/2000/svg" style="width:14px;height:14px;flex-shrink:0;margin-top:1px;color:#fca5a5;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                                        </svg>
                                        <span style="font-size: 0.65rem; color: #fca5a5; line-height: 1.5;">Lihat bukti transfer dulu sebelum assign kurir.</span>
                                    </div>
                                    <a href="{{ Storage::url($pickup->payment_proof) }}" target="_blank" class="be-btn be-btn--xs" style="width: 100%; background: rgba(66, 133, 244, 0.1); border-color: rgba(66, 133, 244, 0.3); color: #60a5fa; font-size: 0.65rem; min-height: 28px !important; border-radius: 8px !important; text-decoration: none; text-align: center;">
                                        🔍 LIHAT BUKTI TRANSFER
                                    </a>
                                </div>
                            @else
                                <div style="display: flex; flex-direction: column; gap: 0.5rem; max-width: 180px;">
                                    <form action="{{ route('be.pickups.auto-assign', $pickup) }}" method="POST" style="margin: 0; padding: 0;">
                                        @csrf
                                        <button type="submit" class="be-btn be-btn--accent be-btn--xs" style="width: 100%; padding: 0.25rem 0.5rem; font-size: 0.65rem; min-height: 28px !important; border-radius: 8px !important;">AUTO</button>
                                    </form>
                                    <form action="{{ route('be.pickups.assign', $pickup) }}" method="POST" style="display: flex; flex-direction: column; gap: 0.35rem; margin: 0; padding: 0;">
                                        @csrf
                                        <select name="courier_id" required style="height: 32px; min-height: 32px !important; font-size: 0.72rem; padding: 2px 8px; border-radius: 8px !important; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); color: #f1f5f9; box-sizing: border-box; width: 100%;">
                                            <option value="" disabled selected>Pilih Kurir...</option>
                                            @foreach($couriers as $courier)
                                                <option value="{{ $courier->id }}">{{ $courier->name }}{{ $courier->vehicle ? ' ('.$courier->vehicle->type.')' : '' }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="be-btn be-btn--primary be-btn--xs" style="width: 100%; padding: 0.25rem 0.5rem; font-size: 0.65rem; min-height: 28px !important; border-radius: 8px !important;">ASSIGN</button>
                                    </form>
                                </div>
                            @endif
                        @else
                            <span class="font-ui text-gray" style="font-size: 0.72rem;">Tunggu manager assign</span>
                        @endif
                    </td>
                    <td style="padding: 1.2rem 0.5rem;">
                        <x-be.badge :variant="$nextAction['tone']">{{ $nextAction['label'] }}</x-be.badge>
                        <div class="font-ui text-gray" style="font-size: 0.72rem; line-height: 1.45; margin-top: 0.45rem; max-width: 220px;">
                            {{ $nextAction['description'] }}
                        </div>
                    </td>
                    <td style="padding: 1.2rem 0.5rem; text-align: center;">
                        @if(auth()->user()->role === 'courier')
                            @if($pickup->status === 'assigned')
                                <div style="display: flex; flex-direction: column; gap: 8px; align-items: center;">
                                    @php
                                        $waNumber = preg_replace('/[^0-9]/', '', $pickup->customer_phone);
                                        if (str_starts_with($waNumber, '0')) {
                                            $waNumber = '62' . substr($waNumber, 1);
                                        }
                                        $pickupMapDestination = ($pickup->latitude && $pickup->longitude)
                                            ? $pickup->latitude . ',' . $pickup->longitude
                                            : $pickup->pickup_address;
                                    @endphp
                                    <a href="https://wa.me/{{ $waNumber }}" target="_blank" class="be-btn be-btn--xs w-full" style="background: rgba(37, 211, 102, 0.1); border-color: rgba(37, 211, 102, 0.3); color: #4ade80; font-size: 0.65rem; min-height: 32px !important; border-radius: 8px !important; text-decoration: none;">CHAT WA PELANGGAN</a>
                                    <a href="https://www.google.com/maps/dir/?api=1&destination={{ urlencode($pickupMapDestination) }}&travelmode=driving" target="_blank" class="be-btn be-btn--xs w-full" style="background: rgba(66, 133, 244, 0.1); border-color: rgba(66, 133, 244, 0.3); color: #60a5fa; font-size: 0.65rem; min-height: 32px !important; border-radius: 8px !important; text-decoration: none;">RUTE KE PICKUP</a>
                                    {{-- Proof of pickup upload form --}}
                                    <form action="{{ route('be.pickups.status', $pickup) }}" method="POST" enctype="multipart/form-data" style="width: 100%; margin-top: 4px;">
                                        @csrf
                                        <input type="hidden" name="status" value="picked_up">
                                        <label style="display: block; font-size: 0.65rem; color: var(--color-gray); margin-bottom: 4px; text-align: left;">FOTO BUKTI AMBIL *</label>
                                        <input type="file" name="proof_image" accept="image/*" capture="environment" required
                                            style="font-size: 0.65rem; color: var(--color-text-main); background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.08); padding: 4px; width: 100%; box-sizing: border-box; margin-bottom: 6px; border-radius: 6px;">
                                        @if($pickup->payment_method === 'cash_on_pickup')
                                            <label style="display: block; font-size: 0.65rem; color: var(--color-gray); margin-bottom: 4px; text-align: left;">UANG DITERIMA DARI PENGIRIM *</label>
                                            <input type="number" name="cash_received_amount" min="0" step="0.01" required
                                                style="font-size: 0.7rem; color: var(--color-text-main); background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.08); padding: 6px; width: 100%; box-sizing: border-box; margin-bottom: 6px; border-radius: 8px;"
                                                placeholder="Nominal tunai">
                                        @endif
                                        <button type="submit" class="be-btn be-btn--xs w-full" style="background: rgba(234, 179, 8, 0.1); border-color: rgba(234, 179, 8, 0.3); color: #facc15; font-size: 0.65rem; min-height: 32px !important; border-radius: 8px !important; cursor: pointer;">KONFIRMASI DIAMBIL</button>
                                    </form>
                                </div>
                            @elseif($pickup->status === 'picked_up')
                                @php
                                    $hub = $pickup->branch ?: auth()->user()->branch;
                                    $hubMapDestination = null;
                                    if ($hub) {
                                        $hubMapDestination = ($hub->latitude && $hub->longitude)
                                            ? $hub->latitude . ',' . $hub->longitude
                                            : trim($hub->address ?: ($hub->name . ' ' . $hub->city));
                                    }
                                @endphp
                                <div style="display: flex; flex-direction: column; gap: 8px; align-items: center;">
                                    @if($hubMapDestination)
                                        <a href="https://www.google.com/maps/dir/?api=1&destination={{ urlencode($hubMapDestination) }}&travelmode=driving" target="_blank" class="be-btn be-btn--xs w-full" style="background: rgba(66, 133, 244, 0.1); border-color: rgba(66, 133, 244, 0.3); color: #60a5fa; font-size: 0.65rem; min-height: 32px !important; border-radius: 8px !important; text-decoration: none;">RUTE KE HUB</a>
                                    @endif
                                    <span class="text-accent" style="font-size: 0.75rem; text-align: center; display: block;">DIBAWA KE HUB</span>
                                </div>
                            @elseif($pickup->status === 'hub_received')
                                <span style="font-size: 0.75rem; color: #00ccff; display: block;">SUDAH DI HUB</span>
                            @endif
                        @else
                            {{-- Manager / Cashier actions --}}
                            <div style="display: flex; flex-direction: column; gap: 6px; align-items: center;">
                                <a href="{{ route('be.pickups.show', $pickup) }}" class="be-btn be-btn--primary be-btn--xs w-full" style="font-size: 0.65rem; min-height: 28px !important; border-radius: 8px !important; text-decoration: none;">DETAIL</a>

                                {{-- Proof thumbnail --}}
                                @if($pickup->proof_of_pickup)
                                    <a href="{{ Storage::url($pickup->proof_of_pickup) }}" target="_blank" class="be-btn be-btn--xs w-full" style="background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1); color: #e2e8f0; font-size: 0.65rem; min-height: 28px !important; border-radius: 8px !important; text-decoration: none;">LIHAT BUKTI FOTO</a>
                                @endif

                                @if($pickup->payment_method === 'cash_on_pickup' && $pickup->payment_status === 'cash_collected_by_courier')
                                    <form action="{{ route('be.pickups.payment', $pickup) }}" method="POST" style="width: 100%;">
                                        @csrf
                                        <label style="display: block; font-size: 0.65rem; color: var(--color-gray); margin-bottom: 4px; text-align: left;">VERIFIKASI SETOR KAS *</label>
                                        <input type="number" name="verified_cash_amount" min="0" step="0.01" required
                                            style="font-size: 0.7rem; color: var(--color-text-main); background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.08); padding: 6px; width: 100%; box-sizing: border-box; margin-bottom: 6px; border-radius: 8px;"
                                            value="{{ $pickup->cash_received_amount }}">
                                        <button type="submit" class="be-btn be-btn--success be-btn--xs w-full" style="font-size: 0.65rem; min-height: 28px !important; border-radius: 8px !important; cursor: pointer;">VERIFIKASI SETOR</button>
                                    </form>
                                @endif

                                @if($pickup->payment_method === 'transfer' && $pickup->payment_proof)
                                    <a href="{{ Storage::url($pickup->payment_proof) }}" target="_blank" class="be-btn be-btn--xs w-full" style="background: rgba(66, 133, 244, 0.1); border-color: rgba(66, 133, 244, 0.3); color: #60a5fa; font-size: 0.65rem; min-height: 28px !important; border-radius: 8px !important; text-decoration: none;">LIHAT BUKTI TRANSFER</a>
                                    @if($pickup->payment_status === 'pending_transfer_verification')
                                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 6px; width: 100%;">
                                            <form action="{{ route('be.pickups.transfer', $pickup) }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="decision" value="approve">
                                                <button type="submit" class="be-btn be-btn--success be-btn--xs w-full" style="font-size: 0.62rem; min-height: 28px !important; border-radius: 8px !important; cursor: pointer;">OK</button>
                                            </form>
                                            <form action="{{ route('be.pickups.transfer', $pickup) }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="decision" value="reject">
                                                <button type="submit" class="be-btn be-btn--danger be-btn--xs w-full" style="font-size: 0.62rem; min-height: 28px !important; border-radius: 8px !important; cursor: pointer;">REJECT</button>
                                            </form>
                                        </div>
                                    @endif
                                @endif

                                @if($pickup->status === 'picked_up')
                                    <form action="{{ route('be.pickups.status', $pickup) }}" method="POST" style="width: 100%;">
                                        @csrf
                                        <input type="hidden" name="status" value="hub_received">
                                        <button type="submit" class="be-btn be-btn--primary be-btn--xs w-full" style="font-size: 0.65rem; min-height: 28px !important; border-radius: 8px !important; cursor: pointer;">TERIMA DI HUB</button>
                                    </form>
                                @elseif($pickup->status === 'hub_received')
                                    @if($pickup->shipment)
                                        <a href="{{ route('be.shipments.show', $pickup->shipment) }}" class="be-btn be-btn--success be-btn--xs w-full" style="font-size: 0.65rem; min-height: 28px !important; border-radius: 8px !important; text-decoration: none;">LIHAT SHIPMENT</a>
                                    @elseif($pickup->weight && $pickup->service_type && $pickup->total_price && $pickup->sender_city_id && $pickup->receiver_city_id && in_array($pickup->payment_status, ['paid']))
                                        <form action="{{ route('be.pickups.activate-shipment', $pickup) }}" method="POST" style="width: 100%;">
                                            @csrf
                                            <button type="submit" class="be-btn be-btn--success be-btn--xs w-full" style="font-size: 0.65rem; min-height: 28px !important; border-radius: 8px !important; cursor: pointer;">AKTIFKAN SHIPMENT</button>
                                        </form>
                                    @else
                                        <span style="font-size: 0.68rem; color: var(--color-gray); display: block;">MENUNGGU DATA / PAYMENT CLEAR</span>
                                    @endif
                                    <a href="{{ route('be.pickups.receipt', $pickup) }}" target="_blank" class="be-btn be-btn--success be-btn--xs w-full" style="font-size: 0.65rem; min-height: 28px !important; border-radius: 8px !important; text-decoration: none; margin-top: 4px;">CETAK RESI</a>
                                @elseif($pickup->status === 'pending')
                                    <span class="text-gray" style="font-size: 0.7rem;">Belum ada aksi lanjutan</span>
                                @else
                                    <span class="text-accent" style="font-size: 0.7rem;">Sedang diproses</span>
                                @endif
                            </div>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="{{ in_array(auth()->user()->role, ['manager', 'cashier'], true) ? 9 : 8 }}" class="table-empty">Belum ada request pickup</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        </div>


        {{-- Bottom pagination --}}
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; padding: 1rem 0 0;">
            <div style="font-size: 0.75rem; color: var(--color-gray);">
                Menampilkan
                <span style="color: #f1f5f9; font-weight: 600;">{{ $pickups->firstItem() ?? 0 }}–{{ $pickups->lastItem() ?? 0 }}</span>
                dari <span style="color: #f1f5f9; font-weight: 600;">{{ $pickups->total() }}</span> pickup
            </div>
            <div>
                <x-be.pagination :paginator="$pickups" />
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const selectAllCheckbox = document.getElementById('select-all-pickups');
            const checkboxes       = document.querySelectorAll('.pickup-select-checkbox');
            const bulkToolbar      = document.getElementById('bulk-toolbar');
            const selectedCountEl  = document.getElementById('selected-count');
            const bulkForm         = document.getElementById('bulk-pickup-form');
            const hiddenIdsDiv     = document.getElementById('bulk-hidden-ids');
            const btnBulkAuto      = document.getElementById('btn-bulk-auto');
            const btnBulkAssign    = document.getElementById('btn-bulk-assign');
            const bulkCourierSel   = document.getElementById('bulk-courier-select');

            function getCheckedIds() {
                return Array.from(document.querySelectorAll('.pickup-select-checkbox:checked'))
                    .map(cb => cb.dataset.pickupId);
            }

            function injectHiddenIds(ids) {
                hiddenIdsDiv.innerHTML = '';
                ids.forEach(id => {
                    const inp = document.createElement('input');
                    inp.type  = 'hidden';
                    inp.name  = 'pickup_ids[]';
                    inp.value = id;
                    hiddenIdsDiv.appendChild(inp);
                });
            }

            function updateToolbar() {
                const count = getCheckedIds().length;
                if (count > 0) {
                    bulkToolbar.style.display = 'flex';
                    selectedCountEl.textContent = count;
                } else {
                    bulkToolbar.style.display = 'none';
                }
            }

            // Select-all header checkbox
            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', () => {
                    checkboxes.forEach(cb => { cb.checked = selectAllCheckbox.checked; });
                    updateToolbar();
                });
            }

            // Individual row checkboxes
            checkboxes.forEach(cb => {
                cb.addEventListener('change', () => {
                    const allChecked = Array.from(checkboxes).every(c => c.checked);
                    if (selectAllCheckbox) selectAllCheckbox.checked = allChecked;
                    updateToolbar();
                });
            });

            // Bulk auto-assign button
            if (btnBulkAuto) {
                btnBulkAuto.addEventListener('click', () => {
                    const ids = getCheckedIds();
                    if (!ids.length) return;
                    injectHiddenIds(ids);
                    bulkForm.action = '{{ route('be.pickups.bulk-auto-assign') }}';
                    bulkForm.submit();
                });
            }

            // Bulk manual-assign button
            if (btnBulkAssign) {
                btnBulkAssign.addEventListener('click', () => {
                    const ids = getCheckedIds();
                    if (!ids.length) return;
                    if (!bulkCourierSel.value) {
                        alert('Pilih kurir terlebih dahulu.');
                        bulkCourierSel.focus();
                        return;
                    }
                    injectHiddenIds(ids);
                    bulkForm.action = '{{ route('be.pickups.bulk-assign') }}';
                    bulkForm.submit();
                });
            }
        });
    </script>
    @endpush

@endsection
