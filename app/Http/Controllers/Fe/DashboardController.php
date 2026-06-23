<?php

namespace App\Http\Controllers\Fe;

use App\Http\Controllers\Controller;
use App\Models\ShipmentNotification;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    private const PAYMENT_WAITING_STATUSES = [
        'pending_transfer_verification',
        'pending_verification',
        'awaiting_pickup_cash',
        'cash_collected_by_courier',
    ];

    private const STEP_LABELS = [
        'queued' => 'ORDER QUEUED',
        'assigned' => 'COURIER ASSIGNED',
        'picked_up' => 'PICKED UP',
        'hub_received' => 'AT HUB',
        'shipment_active' => 'SHIPMENT ACTIVE',
        'in_transit' => 'IN TRANSIT',
        'delivered' => 'DELIVERED',
    ];

    public function index()
    {
        $user = Auth::user();

        if ($user->role !== 'customer') {
            return redirect()->route('be.dashboard');
        }

        $linkedShipmentIds = $user->pickups()
            ->whereNotNull('shipment_id')
            ->pluck('shipment_id');

        $standaloneShipmentScope = $user->shipments()
            ->when($linkedShipmentIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $linkedShipmentIds));

        $summary = $this->customerSummary($user, $standaloneShipmentScope);

        $pickups = $user->pickups()
            ->with([
                'shipment.receiver',
                'shipment.destinationBranch',
                'shipment.latestTracking',
                'shipment.payment',
                'receiverCity',
                'senderCity',
                'branch',
                'courier',
            ])
            ->latest()
            ->limit(30)
            ->get();

        $standaloneShipments = $user->shipments()
            ->with(['receiver', 'destinationBranch', 'latestTracking', 'payment'])
            ->when($linkedShipmentIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $linkedShipmentIds))
            ->latest()
            ->limit(30)
            ->get();

        $orderLifecycles = $pickups
            ->map(fn ($pickup) => $this->pickupLifecycle($pickup))
            ->concat($standaloneShipments->map(fn ($shipment) => $this->shipmentLifecycle($shipment)))
            ->sortByDesc('updated_at')
            ->take(30)
            ->values();

        $notificationQuery = ShipmentNotification::query()
            ->with('shipment')
            ->where('user_id', $user->id);

        $summary['unread_notifications'] = (clone $notificationQuery)
            ->whereNull('read_at')
            ->count();

        $notifications = $notificationQuery
            ->latest('sent_at')
            ->latest()
            ->limit(8)
            ->get();

        return view('fe.dashboard', [
            'user' => $user,
            'orderLifecycles' => $orderLifecycles,
            'summary' => $summary,
            'notifications' => $this->notificationCards($notifications),
            'stepLabels' => self::STEP_LABELS,
        ]);
    }

    public function markNotificationRead(ShipmentNotification $notification)
    {
        abort_unless((int) $notification->user_id === (int) Auth::id(), 403);

        if (! $notification->read_at) {
            $notification->update(['read_at' => now()]);
        }

        return back()->with('success', 'Notifikasi ditandai sudah dibaca.');
    }

    private function pickupLifecycle($pickup): array
    {
        $shipment = $pickup->shipment;
        $shipmentStatus = $shipment?->status;
        $mainStatus = $shipmentStatus ?: $pickup->status;
        $paymentStatus = $pickup->payment_status;
        $hasShipment = (bool) $shipment;

        $steps = [
            'queued' => true,
            'assigned' => in_array($pickup->status, ['assigned', 'picked_up', 'hub_received'], true) || $hasShipment,
            'picked_up' => in_array($pickup->status, ['picked_up', 'hub_received'], true) || $hasShipment,
            'hub_received' => $pickup->status === 'hub_received' || $hasShipment,
            'shipment_active' => $hasShipment,
            'in_transit' => $shipment && in_array($shipmentStatus, ['in_transit', 'arrived_at_branch', 'out_for_delivery', 'delivered'], true),
            'delivered' => $shipmentStatus === 'delivered',
        ];

        return [
            'kind' => 'pickup',
            'id' => $pickup->id,
            'reference' => $shipment?->tracking_number ?: 'REQ_UNIT_'.str_pad($pickup->id, 5, '0', STR_PAD_LEFT),
            'receiver_name' => $pickup->receiver_name ?: $shipment?->receiver?->name,
            'destination' => $pickup->receiverCity?->name ?: $shipment?->destinationBranch?->city ?: $pickup->receiver_address,
            'pickup_address' => $pickup->pickup_address,
            'pickup_date' => $pickup->pickup_date,
            'service_type' => $pickup->service_type,
            'total_price' => $pickup->total_price,
            'payment_method' => $pickup->payment_method,
            'payment_status' => $pickup->payment_status,
            'pickup_status' => $pickup->status,
            'shipment_status' => $shipmentStatus,
            'main_status' => $mainStatus,
            'status_class' => $this->statusChipClass($mainStatus),
            'payment_class' => $this->paymentChipClass($paymentStatus),
            'next_action' => $this->nextActionFor($hasShipment, $shipmentStatus, $paymentStatus),
            'has_shipment' => $hasShipment,
            'latest_tracking' => $shipment?->latestTracking,
            'steps' => $steps,
            'can_reschedule' => ! $shipment && $pickup->status === 'pending',
            'can_cancel' => ! $shipment && $pickup->status === 'pending',
            'can_reupload_payment' => ! $shipment && $pickup->payment_method === 'transfer' && $pickup->payment_status === 'transfer_rejected' && $pickup->status !== 'cancelled',
            'updated_at' => $shipment?->updated_at ?: $pickup->updated_at,
        ];
    }

    private function shipmentLifecycle($shipment): array
    {
        $paymentStatus = $shipment->payment?->payment_status;

        $steps = [
            'queued' => true,
            'assigned' => true,
            'picked_up' => true,
            'hub_received' => true,
            'shipment_active' => true,
            'in_transit' => in_array($shipment->status, ['in_transit', 'arrived_at_branch', 'out_for_delivery', 'delivered'], true),
            'delivered' => $shipment->status === 'delivered',
        ];

        return [
            'kind' => 'shipment',
            'id' => $shipment->id,
            'reference' => $shipment->tracking_number,
            'receiver_name' => $shipment->receiver?->name,
            'destination' => $shipment->destinationBranch?->city,
            'pickup_address' => null,
            'pickup_date' => $shipment->shipment_date,
            'service_type' => null,
            'total_price' => $shipment->total_price,
            'payment_method' => $shipment->payment?->payment_method,
            'payment_status' => $paymentStatus,
            'pickup_status' => null,
            'shipment_status' => $shipment->status,
            'main_status' => $shipment->status,
            'status_class' => $this->statusChipClass($shipment->status),
            'payment_class' => $this->paymentChipClass($paymentStatus),
            'next_action' => $this->nextActionFor(true, $shipment->status, $paymentStatus),
            'has_shipment' => true,
            'latest_tracking' => $shipment->latestTracking,
            'steps' => $steps,
            'can_reschedule' => false,
            'can_cancel' => false,
            'can_reupload_payment' => false,
            'updated_at' => $shipment->updated_at,
        ];
    }

    private function customerSummary($user, $standaloneShipmentScope): array
    {
        return [
            'total_orders' => $user->pickups()->count() + (clone $standaloneShipmentScope)->count(),
            'waiting_payment' => $user->pickups()
                ->whereIn('payment_status', self::PAYMENT_WAITING_STATUSES)
                ->count()
                + (clone $standaloneShipmentScope)
                    ->whereHas('payment', fn ($query) => $query->whereIn('payment_status', [...self::PAYMENT_WAITING_STATUSES, 'pending']))
                    ->count(),
            'active_shipments' => $user->pickups()
                ->whereHas('shipment', fn ($query) => $query->where('status', '!=', 'delivered'))
                ->count()
                + (clone $standaloneShipmentScope)->where('status', '!=', 'delivered')->count(),
            'delivered' => $user->pickups()
                ->whereHas('shipment', fn ($query) => $query->where('status', 'delivered'))
                ->count()
                + (clone $standaloneShipmentScope)->where('status', 'delivered')->count(),
        ];
    }

    private function notificationCards($notifications)
    {
        return $notifications->map(function (ShipmentNotification $notification): array {
            $trackingNumber = $notification->shipment?->tracking_number;

            return [
                'id' => $notification->id,
                'is_unread' => ! $notification->read_at,
                'sent_at_label' => optional($notification->sent_at ?: $notification->created_at)->format('d M Y H:i'),
                'title' => $notification->title,
                'message' => $notification->message,
                'tracking_number' => $trackingNumber,
                'tracking_url' => $trackingNumber ? route('track.show', ['receipt' => $trackingNumber]) : null,
            ];
        });
    }

    private function statusChipClass(?string $status): string
    {
        return match ($status) {
            'delivered', 'hub_received' => 'status-chip--success',
            'cancelled', 'failed', 'transfer_rejected' => 'status-chip--danger',
            'in_transit', 'arrived_at_branch', 'out_for_delivery' => 'status-chip--accent',
            default => 'status-chip--waiting',
        };
    }

    private function paymentChipClass(?string $status): string
    {
        return match ($status) {
            'paid' => 'status-chip--success',
            'transfer_rejected', 'failed' => 'status-chip--danger',
            'pending_transfer_verification', 'pending_verification', 'cash_collected_by_courier' => 'status-chip--accent',
            default => 'status-chip--waiting',
        };
    }

    private function nextActionFor(bool $hasShipment, ?string $shipmentStatus, ?string $paymentStatus): string
    {
        return match (true) {
            ! $hasShipment && $paymentStatus === 'awaiting_pickup_cash' => 'Siapkan uang tunai untuk pickup. Kasir hub akan verifikasi setelah kurir menyetor cash.',
            ! $hasShipment && in_array($paymentStatus, ['pending_transfer_verification', 'pending_verification'], true) => 'Bukti transfer sedang menunggu review kasir hub.',
            ! $hasShipment && $paymentStatus === 'cash_collected_by_courier' => 'Kurir sudah menerima cash. Menunggu kasir hub verifikasi setoran.',
            ! $hasShipment => 'Menunggu pickup kurir dan aktivasi shipment dari hub.',
            $shipmentStatus === 'pending' => 'Shipment sudah aktif dan menunggu pergerakan pertama.',
            in_array($shipmentStatus, ['picked_up', 'in_transit'], true) => 'Paket sedang bergerak di jalur hub. Cek tracking untuk scan berikutnya.',
            $shipmentStatus === 'arrived_at_branch' => 'Paket sudah tiba di hub tujuan dan menunggu delivery terakhir.',
            $shipmentStatus === 'out_for_delivery' => 'Kurir sedang menuju penerima. Pastikan nomor penerima aktif.',
            $shipmentStatus === 'delivered' => 'Pengiriman selesai. Timeline tersedia di halaman tracking.',
            default => 'Hub sedang memproses langkah operasional berikutnya.',
        };
    }
}
