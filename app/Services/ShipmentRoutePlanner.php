<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Shipment;
use App\Models\ShipmentLeg;
use Illuminate\Support\Collection;

class ShipmentRoutePlanner
{
    public function createLegsFor(Shipment $shipment): Collection
    {
        $shipment->loadMissing(['originBranch', 'destinationBranch', 'rate']);

        $route = $this->branchesFor($shipment->originBranch, $shipment->destinationBranch);

        $shipment->legs()->delete();

        $pairs = $route
            ->zip($route->slice(1))
            ->filter(fn ($pair) => $pair[0] && $pair[1] && (int) $pair[0]->id !== (int) $pair[1]->id)
            ->values();

        $legCount = max(1, $pairs->count());
        $estimatedHours = max(24, (int) (($shipment->rate?->estimated_days ?? $legCount) * 24));
        $hoursPerLeg = (int) ceil($estimatedHours / $legCount);
        $plannedStart = now();

        return $pairs
            ->map(function ($pair, int $index) use ($hoursPerLeg, $plannedStart, $shipment) {
                $plannedDeparture = $plannedStart->copy()->addHours($hoursPerLeg * $index);

                return ShipmentLeg::create([
                    'shipment_id' => $shipment->id,
                    'sequence' => $index + 1,
                    'origin_branch_id' => $pair[0]->id,
                    'destination_branch_id' => $pair[1]->id,
                    'status' => 'pending',
                    'planned_departure_at' => $plannedDeparture,
                    'planned_arrival_at' => $plannedDeparture->copy()->addHours($hoursPerLeg),
                ]);
            });
    }

    public function applyShipmentStatus(Shipment $shipment, string $nextStatus): void
    {
        $shipment->loadMissing('legs');

        match ($nextStatus) {
            'in_transit' => $this->departNextLeg($shipment),
            'arrived_at_branch' => $this->arriveActiveLeg($shipment),
            'out_for_delivery', 'delivered' => $this->arriveAllLegs($shipment),
            default => null,
        };
    }

    private function branchesFor(?Branch $origin, ?Branch $destination): Collection
    {
        if (! $origin || ! $destination || (int) $origin->id === (int) $destination->id) {
            return collect([$origin, $destination])->filter();
        }

        $gateway = $this->gatewayBranch();
        $branches = collect([$origin]);

        if ($gateway && ! in_array((int) $gateway->id, [(int) $origin->id, (int) $destination->id], true)) {
            $branches->push($gateway);
        }

        return $branches->push($destination)->unique('id')->values();
    }

    private function gatewayBranch(): ?Branch
    {
        return Branch::query()
            ->where('name', 'like', '%DKI Jakarta%')
            ->orWhere('city', 'like', '%DKI Jakarta%')
            ->orWhere('name', 'like', '%Jakarta%')
            ->orderBy('id')
            ->first();
    }

    private function departNextLeg(Shipment $shipment): void
    {
        $leg = $shipment->legs()
            ->where('status', 'pending')
            ->orderBy('sequence')
            ->first();

        if (! $leg) {
            return;
        }

        $leg->update([
            'status' => 'departed',
            'handler_id' => auth()->id(),
            'departed_at' => now(),
        ]);
    }

    private function arriveActiveLeg(Shipment $shipment): void
    {
        $leg = $shipment->legs()
            ->whereIn('status', ['departed', 'pending'])
            ->orderByRaw("CASE WHEN status = 'departed' THEN 0 ELSE 1 END")
            ->orderBy('sequence')
            ->first();

        if (! $leg) {
            return;
        }

        $leg->update([
            'status' => 'arrived',
            'handler_id' => auth()->id(),
            'departed_at' => $leg->departed_at ?: now(),
            'arrived_at' => now(),
        ]);
    }

    private function arriveAllLegs(Shipment $shipment): void
    {
        $shipment->legs()
            ->where('status', '!=', 'arrived')
            ->update([
                'status' => 'arrived',
                'handler_id' => auth()->id(),
                'departed_at' => now(),
                'arrived_at' => now(),
                'updated_at' => now(),
            ]);
    }
}
