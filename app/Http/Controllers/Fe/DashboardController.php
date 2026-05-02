<?php

namespace App\Http\Controllers\Fe;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
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

        $pickupCount = $user->pickups()->count();
        $standaloneShipmentCount = (clone $standaloneShipmentScope)->count();

        $summary = [
            'total_orders' => $pickupCount + $standaloneShipmentCount,
            'waiting_payment' => $user->pickups()
                ->whereIn('payment_status', ['pending_transfer_verification', 'pending_verification', 'awaiting_pickup_cash', 'cash_collected_by_courier'])
                ->count()
                + (clone $standaloneShipmentScope)
                    ->whereHas('payment', fn ($query) => $query->whereIn('payment_status', ['pending_transfer_verification', 'pending_verification', 'awaiting_pickup_cash', 'cash_collected_by_courier', 'pending']))
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

        return view('fe.dashboard', compact('user', 'orderLifecycles', 'summary'));
    }

    private function pickupLifecycle($pickup): array
    {
        $shipment = $pickup->shipment;
        $shipmentStatus = $shipment?->status;

        $steps = [
            'queued' => true,
            'assigned' => in_array($pickup->status, ['assigned', 'picked_up', 'hub_received'], true) || (bool) $shipment,
            'picked_up' => in_array($pickup->status, ['picked_up', 'hub_received'], true) || (bool) $shipment,
            'hub_received' => $pickup->status === 'hub_received' || (bool) $shipment,
            'shipment_active' => (bool) $shipment,
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
            'has_shipment' => (bool) $shipment,
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
            'payment_status' => $shipment->payment?->payment_status,
            'pickup_status' => null,
            'shipment_status' => $shipment->status,
            'has_shipment' => true,
            'latest_tracking' => $shipment->latestTracking,
            'steps' => $steps,
            'can_reschedule' => false,
            'can_cancel' => false,
            'can_reupload_payment' => false,
            'updated_at' => $shipment->updated_at,
        ];
    }
}
