<?php

namespace App\Http\Controllers\Fe;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrackingController extends Controller
{
    private const STATUS_META = [
        'pending' => [
            'label' => 'Paket terdaftar',
            'message' => 'Shipment sudah aktif dan menunggu scan hub asal.',
            'tone' => 'neutral',
        ],
        'picked_up' => [
            'label' => 'Diproses hub asal',
            'message' => 'Paket sudah diterima dan sedang disiapkan untuk berangkat.',
            'tone' => 'primary',
        ],
        'in_transit' => [
            'label' => 'Dalam perjalanan',
            'message' => 'Paket sedang bergerak antar hub menuju kota tujuan.',
            'tone' => 'accent',
        ],
        'arrived_at_branch' => [
            'label' => 'Tiba di hub',
            'message' => 'Paket sudah tiba di hub transit atau hub tujuan.',
            'tone' => 'primary',
        ],
        'out_for_delivery' => [
            'label' => 'Diantar kurir',
            'message' => 'Kurir sedang mengantar paket ke alamat penerima.',
            'tone' => 'accent',
        ],
        'delivered' => [
            'label' => 'Terkirim',
            'message' => 'Paket sudah berhasil diterima.',
            'tone' => 'success',
        ],
        'cancelled' => [
            'label' => 'Dibatalkan',
            'message' => 'Shipment dibatalkan dan tidak bergerak lagi.',
            'tone' => 'danger',
        ],
        'delivery_failed' => [
            'label' => 'Pengantaran tertunda',
            'message' => 'Kurir belum berhasil menyerahkan paket. Hub akan menjadwalkan percobaan berikutnya.',
            'tone' => 'danger',
        ],
        'rescheduled' => [
            'label' => 'Dijadwalkan ulang',
            'message' => 'Pengantaran sudah dijadwalkan ulang dan menunggu kurir berangkat kembali.',
            'tone' => 'accent',
        ],
        'returned_to_hub' => [
            'label' => 'Kembali ke hub',
            'message' => 'Paket kembali ke hub untuk pengecekan dan instruksi lanjutan.',
            'tone' => 'danger',
        ],
        'held' => [
            'label' => 'Ditahan sementara',
            'message' => 'Paket sedang ditahan sementara oleh hub untuk pemeriksaan operasional.',
            'tone' => 'danger',
        ],
        'damaged' => [
            'label' => 'Perlu pemeriksaan',
            'message' => 'Paket ditandai perlu pemeriksaan kondisi oleh hub.',
            'tone' => 'danger',
        ],
        'lost' => [
            'label' => 'Dalam investigasi',
            'message' => 'Paket sedang dalam proses investigasi oleh tim operasional.',
            'tone' => 'danger',
        ],
        'exception' => [
            'label' => 'Ada kendala rute',
            'message' => 'Ada kendala operasional pada perjalanan paket dan sedang ditangani hub.',
            'tone' => 'danger',
        ],
    ];

    private const FLOW_STEPS = [
        'pending',
        'picked_up',
        'in_transit',
        'arrived_at_branch',
        'out_for_delivery',
        'delivered',
    ];

    public function show(Request $request)
    {
        $shipment = null;
        $trackingFlow = null;

        if ($request->filled('receipt')) {
            $trackingNumber = strtoupper(trim((string) $request->query('receipt')));
            $shipment = $this->findShipment($trackingNumber);
            $trackingFlow = $shipment ? $this->buildTrackingFlow($shipment) : null;
        }

        return view('fe.track', compact('shipment', 'trackingFlow'));
    }

    public function apiShow(string $trackingNumber): JsonResponse
    {
        $shipment = $this->findShipment(strtoupper(trim($trackingNumber)));

        if (! $shipment) {
            return response()->json([
                'message' => 'Tracking number not found.',
            ], 404);
        }

        $trackingFlow = $this->buildTrackingFlow($shipment);
        $statusMeta = $this->statusMeta($shipment->status);

        return response()->json([
            'tracking_number' => $shipment->tracking_number,
            'status' => $shipment->status,
            'status_label' => $statusMeta['label'],
            'status_message' => $statusMeta['message'],
            'shipment_date' => optional($shipment->shipment_date)->format('Y-m-d'),
            'origin_branch' => optional($shipment->originBranch)->name,
            'destination_branch' => optional($shipment->destinationBranch)->name,
            'progress' => $trackingFlow['progress'],
            'steps' => $trackingFlow['steps'],
            'route_legs' => $shipment->legs->map(function ($leg) {
                return [
                    'sequence' => $leg->sequence,
                    'origin_branch' => optional($leg->originBranch)->name,
                    'destination_branch' => optional($leg->destinationBranch)->name,
                    'status' => $leg->status,
                    'planned_departure_at' => optional($leg->planned_departure_at)->toIso8601String(),
                    'planned_arrival_at' => optional($leg->planned_arrival_at)->toIso8601String(),
                    'departed_at' => optional($leg->departed_at)->toIso8601String(),
                    'arrived_at' => optional($leg->arrived_at)->toIso8601String(),
                    'delay_reason' => $leg->delay_reason,
                ];
            })->values(),
            'timeline' => $shipment->trackings->map(function ($tracking) {
                $meta = $this->statusMeta($tracking->status);

                return [
                    'status' => $tracking->status,
                    'status_label' => $meta['label'],
                    'location' => $tracking->location,
                    'description' => $tracking->description,
                    'tracked_at' => optional($tracking->tracked_at)->toIso8601String(),
                ];
            })->values(),
        ]);
    }

    private function findShipment(string $trackingNumber): ?Shipment
    {
        return Shipment::with(['originBranch', 'destinationBranch', 'trackings', 'latestTracking', 'legs.originBranch', 'legs.destinationBranch', 'exceptions'])
            ->where('tracking_number', $trackingNumber)
            ->first();
    }

    private function buildTrackingFlow(Shipment $shipment): array
    {
        $currentStatus = (string) $shipment->status;
        $currentIndex = array_search($currentStatus, self::FLOW_STEPS, true);
        $isCancelled = $currentStatus === 'cancelled';

        if ($currentIndex === false) {
            $currentIndex = match ($currentStatus) {
                'delivery_failed', 'rescheduled', 'returned_to_hub' => array_search('out_for_delivery', self::FLOW_STEPS, true),
                'held', 'damaged', 'lost', 'exception' => array_search('in_transit', self::FLOW_STEPS, true),
                default => 0,
            };
        }
        $displayCurrentStatus = self::FLOW_STEPS[$currentIndex] ?? 'pending';

        $steps = collect(self::FLOW_STEPS)->map(function (string $status, int $index) use ($currentIndex, $displayCurrentStatus, $isCancelled) {
            $meta = $this->statusMeta($status);

            return [
                'status' => $status,
                'label' => $meta['label'],
                'message' => $meta['message'],
                'is_done' => ! $isCancelled && $index < $currentIndex,
                'is_current' => ! $isCancelled && $status === $displayCurrentStatus,
                'is_pending' => $isCancelled || $index > $currentIndex,
            ];
        })->values();

        $progress = $isCancelled
            ? 0
            : (int) round(($currentIndex / (count(self::FLOW_STEPS) - 1)) * 100);

        $latestTracking = $shipment->latestTracking;
        $statusMeta = $this->statusMeta($currentStatus);

        return [
            'status_label' => $statusMeta['label'],
            'status_message' => $statusMeta['message'],
            'status_tone' => $isCancelled ? 'danger' : $statusMeta['tone'],
            'progress' => $progress,
            'latest_tracking' => $latestTracking,
            'steps' => $steps,
        ];
    }

    private function statusMeta(?string $status): array
    {
        return self::STATUS_META[$status] ?? [
            'label' => strtoupper((string) $status ?: 'Unknown'),
            'message' => 'Status terbaru sedang diproses oleh sistem.',
            'tone' => 'neutral',
        ];
    }
}
