<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\ShipmentLeg;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class ShipmentRoutePlanner
{
    public function __construct(private RouteDistanceService $distances)
    {
    }

    public function createLegsFor(Shipment $shipment): Collection
    {
        $shipment->loadMissing(['originBranch', 'destinationBranch', 'rate']);

        $route = $this->branchesFor($shipment->originBranch, $shipment->destinationBranch);

        $shipment->legs()->delete();

        $pairs = $route
            ->zip($route->slice(1))
            ->filter(fn ($pair) => $pair[0] && $pair[1] && (int) $pair[0]->id !== (int) $pair[1]->id)
            ->values();

        $plannedStart = now();
        $cursor = $plannedStart->copy();

        return $pairs
            ->map(function ($pair, int $index) use (&$cursor, $shipment) {
                $estimate = $this->distances->estimateBetweenBranches($pair[0], $pair[1], [
                    'speed_kmh' => 42,
                    'label' => 'Hub leg',
                ]);
                $durationMinutes = max(60, (int) ($estimate['duration_minutes'] ?? 1440));
                $plannedDeparture = $cursor->copy();
                $plannedArrival = $plannedDeparture->copy()->addMinutes($durationMinutes);
                $cursor = $plannedArrival->copy();

                $payload = [
                    'shipment_id' => $shipment->id,
                    'sequence' => $index + 1,
                    'origin_branch_id' => $pair[0]->id,
                    'destination_branch_id' => $pair[1]->id,
                    'status' => 'pending',
                    'planned_departure_at' => $plannedDeparture,
                    'planned_arrival_at' => $plannedArrival,
                ];

                if (Schema::hasColumn('shipment_legs', 'distance_km')) {
                    $payload['distance_km'] = $estimate['available'] ? ($estimate['distance_km'] ?? null) : null;
                }
                if (Schema::hasColumn('shipment_legs', 'duration_minutes')) {
                    $payload['duration_minutes'] = $estimate['available'] ? $durationMinutes : null;
                }
                if (Schema::hasColumn('shipment_legs', 'routing_provider')) {
                    $payload['routing_provider'] = $estimate['provider'] ?? null;
                }
                if (Schema::hasColumn('shipment_legs', 'route_meta')) {
                    $payload['route_meta'] = [
                        'source' => 'shipment_route_planner',
                        'estimate' => $estimate,
                    ];
                }

                return ShipmentLeg::create($payload);
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

        $originLocation = $this->branchProvinceLocation($origin);
        $destinationLocation = $this->branchProvinceLocation($destination);
        $sameProvince = $originLocation && $destinationLocation && (int) $originLocation->id === (int) $destinationLocation->id;

        // Treat only same-province branches as direct origin->destination.
        // Do not use the broader 'zone' grouping for direct routing so
        // each province is treated independently.
        if ($sameProvince) {
            return collect([$origin, $destination])->unique('id')->values();
        }

        $gateway = $this->bestGatewayBranch($origin, $destination);

        return collect([$origin, $gateway, $destination])->filter()->unique('id')->values();
    }

    private function bestGatewayBranch(Branch $origin, Branch $destination): ?Branch
    {
        $best = $this->gatewayCandidates([$origin->id, $destination->id])
            ->map(function (Branch $gateway) use ($origin, $destination) {
                $first = $this->distances->estimateBetweenBranches($origin, $gateway, ['speed_kmh' => 42, 'force_fallback' => true]);
                $second = $this->distances->estimateBetweenBranches($gateway, $destination, ['speed_kmh' => 42, 'force_fallback' => true]);

                if (! ($first['available'] ?? false) || ! ($second['available'] ?? false)) {
                    return null;
                }

                return [
                    'branch' => $gateway,
                    'distance_km' => (float) $first['distance_km'] + (float) $second['distance_km'],
                    'duration_minutes' => (int) $first['duration_minutes'] + (int) $second['duration_minutes'],
                ];
            })
            ->filter()
            ->sortBy([
                ['duration_minutes', 'asc'],
                ['distance_km', 'asc'],
            ])
            ->first();

        return $best['branch'] ?? $this->nationalGatewayBranch([$origin->id, $destination->id]);
    }

    private function gatewayCandidates(array $excludedIds = []): Collection
    {
        $priority = ['DKI Jakarta', 'Jawa Timur', 'Jawa Barat', 'Sulawesi Selatan', 'Kalimantan Timur'];

        return Branch::query()
            ->whereNotIn('id', $excludedIds)
            ->where(function ($query) use ($priority) {
                foreach ($priority as $region) {
                    $query->orWhere('name', 'like', '%'.$region.'%')
                        ->orWhere('city', 'like', '%'.$region.'%');
                }
            })
            ->orderBy('id')
            ->get();
    }

    private function nationalGatewayBranch(array $excludedIds = []): ?Branch
    {
        return Branch::query()
            ->whereNotIn('id', $excludedIds)
            ->where(function ($query) {
                $query->where('name', 'like', '%DKI Jakarta%')
                    ->orWhere('city', 'like', '%DKI Jakarta%')
                    ->orWhere('name', 'like', '%Jakarta%');
            })
            ->orderBy('id')
            ->first();
    }

    private function branchProvinceLocation(Branch $branch): ?Location
    {
        $city = trim((string) $branch->city);
        $haystack = strtolower(trim($branch->name.' '.$branch->city.' '.$branch->address));
        $normalizedCity = preg_replace('/^(kota|kab\.?|kabupaten)\s+/i', '', $city);
        $provinceNameFromText = Location::query()
            ->where('type', 'provinsi')
            ->pluck('name')
            ->first(fn (string $name) => str_contains($haystack, strtolower($name)));

        $province = Location::query()
            ->where('type', 'provinsi')
            ->where(function ($query) use ($city, $normalizedCity, $provinceNameFromText) {
                $query->where('name', $city)
                    ->orWhere('name', 'like', '%'.$normalizedCity.'%');

                if ($provinceNameFromText) {
                    $query->orWhere('name', $provinceNameFromText);
                }
            })
            ->orderByRaw('CASE WHEN name = ? THEN 0 ELSE 1 END', [$city])
            ->first();

        if ($province) {
            return $province;
        }

        return Location::query()
            ->with('parentLocation')
            ->where('type', 'kota')
            ->where(function ($query) use ($city, $normalizedCity) {
                $query->where('name', $city)
                    ->orWhere('name', 'like', '%'.$normalizedCity.'%');
            })
            ->first()
            ?->parentLocation;
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

        // Preserve existing handler (courier) if present — do not overwrite with the
        // current actor (which may be hub staff). This ensures the leg keeps the
        // original courier as the handler when the arrival is just recorded by
        // hub personnel.
        $leg->update([
            'status' => 'arrived',
            'handler_id' => $leg->handler_id ?: auth()->id(),
            'departed_at' => $leg->departed_at ?: now(),
            'arrived_at' => now(),
        ]);
    }

    private function arriveAllLegs(Shipment $shipment): void
    {
        // Update each non-arrived leg individually so we can preserve any
        // existing handler_id (courier) instead of overwriting with the
        // current actor. This keeps the historical responsibility intact.
        $shipment->legs()
            ->where('status', '!=', 'arrived')
            ->get()
            ->each(function (ShipmentLeg $leg) {
                $leg->update([
                    'status' => 'arrived',
                    'handler_id' => $leg->handler_id ?: auth()->id(),
                    'departed_at' => $leg->departed_at ?: now(),
                    'arrived_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }
}
