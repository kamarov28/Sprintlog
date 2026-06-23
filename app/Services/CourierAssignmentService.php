<?php

namespace App\Services;

use App\Models\PickupRequest;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CourierAssignmentService
{
    public function __construct(private RouteDistanceService $distances)
    {
    }

    public function recommendForPickup(PickupRequest $pickup): ?array
    {
        $branchId = $pickup->branch_id ? (int) $pickup->branch_id : null;
        if (! $branchId) {
            return null;
        }

        $weightKg = max(0.1, (float) ($pickup->weight ?: 1));
        $pickupPoint = $this->pickupPoint($pickup);

        return $this->candidateCouriers($pickup)
            ->map(function (User $courier) use ($pickup, $pickupPoint, $weightKg) {
                $vehicle = $courier->vehicle;
                if (! $vehicle || ! $vehicle->canCarry($weightKg, 1)) {
                    return null;
                }

                $route = $this->routeToPickup($courier, $pickupPoint);
                $activePickups = $this->activePickupCount($courier);
                $sameDayPickups = $this->sameDayPickupCount($courier, $pickup);
                $activeShipments = $this->activeShipmentCount($courier);
                $distanceKm = $route['available'] ? (float) $route['distance_km'] : 99.0;
                $durationMinutes = $route['available'] ? (int) $route['duration_minutes'] : null;
                $capacityPenalty = $weightKg / max(1, (float) $vehicle->capacity_kg);
                $locationPenalty = $this->hasCourierPoint($courier) ? 0 : 8;
                $score = $distanceKm + ($activePickups * 12) + ($sameDayPickups * 6) + ($activeShipments * 8) + ($capacityPenalty * 10) + $locationPenalty;

                return [
                    'courier' => $courier,
                    'vehicle' => $vehicle,
                    'score' => round($score, 2),
                    'distance_km' => $route['available'] ? round($distanceKm, 1) : null,
                    'duration_minutes' => $durationMinutes,
                    'provider' => $route['provider'] ?? 'fallback',
                    'active_pickups' => $activePickups,
                    'same_day_pickups' => $sameDayPickups,
                    'active_shipments' => $activeShipments,
                    'location_source' => $this->hasCourierPoint($courier) ? 'courier_location' : 'hub_location',
                ];
            })
            ->filter()
            ->sortBy('score')
            ->first();
    }

    public function assignRecommended(PickupRequest $pickup): ?array
    {
        $recommendation = $this->recommendForPickup($pickup);
        if (! $recommendation) {
            return null;
        }

        $payload = [
            'courier_id' => $recommendation['courier']->id,
            'status' => 'assigned',
        ];

        if (Schema::hasColumn('pickup_requests', 'auto_assignment_score')) {
            $payload['auto_assignment_score'] = $recommendation['score'];
        }

        if (Schema::hasColumn('pickup_requests', 'auto_assignment_meta')) {
            $payload['auto_assignment_meta'] = [
                'courier_id' => $recommendation['courier']->id,
                'courier_name' => $recommendation['courier']->name,
                'vehicle_id' => $recommendation['vehicle']->id,
                'vehicle_label' => $recommendation['vehicle']->label(),
                'distance_km' => $recommendation['distance_km'],
                'duration_minutes' => $recommendation['duration_minutes'],
                'provider' => $recommendation['provider'],
                'active_pickups' => $recommendation['active_pickups'],
                'same_day_pickups' => $recommendation['same_day_pickups'],
                'active_shipments' => $recommendation['active_shipments'],
                'location_source' => $recommendation['location_source'],
                'assigned_at' => now()->toISOString(),
            ];
        }

        $pickup->update($payload);

        return $recommendation;
    }

    public function validatePickupCourier(PickupRequest $pickup, User $courier): ?string
    {
        if ($courier->role !== 'courier') {
            return 'User yang dipilih bukan kurir.';
        }

        if ($pickup->branch_id && (int) $courier->branch_id !== (int) $pickup->branch_id) {
            return 'Kurir harus berasal dari hub pickup.';
        }

        if (Schema::hasColumn('users', 'courier_status') && $courier->courier_status === 'unavailable') {
            return 'Kurir sedang tidak available.';
        }

        $vehicle = $courier->vehicle;
        if (! $vehicle) {
            return 'Kurir '.$courier->name.' belum punya kendaraan terdaftar.';
        }

        if (! $vehicle->isActive()) {
            return 'Kendaraan '.$vehicle->plate_number.' sedang tidak aktif.';
        }

        if ($vehicle->branch_id && (int) $vehicle->branch_id !== (int) $courier->branch_id) {
            return 'Kendaraan '.$vehicle->plate_number.' tidak terdaftar di hub kurir ini.';
        }

        if (! $vehicle->canCarry(max(0.1, (float) ($pickup->weight ?: 1)), 1)) {
            return 'Kapasitas kendaraan '.$vehicle->plate_number.' tidak cukup untuk paket ini.';
        }

        return null;
    }

    private function candidateCouriers(PickupRequest $pickup): Collection
    {
        return User::query()
            ->with(['vehicle', 'branch'])
            ->where('role', 'courier')
            ->where('branch_id', $pickup->branch_id)
            ->when(Schema::hasColumn('users', 'courier_status'), function ($query) {
                $query->where(function ($q) {
                    $q->whereNull('courier_status')
                        ->orWhereIn('courier_status', ['available', 'standby', 'active']);
                });
            })
            ->whereHas('vehicle', function ($query) {
                $query->where('status', 'active');
            })
            ->orderBy('name')
            ->get();
    }

    private function routeToPickup(User $courier, ?array $pickupPoint): array
    {
        if (! $pickupPoint) {
            return [
                'available' => false,
                'provider' => 'unavailable',
                'distance_km' => null,
                'duration_minutes' => null,
            ];
        }

        $origin = $this->courierPoint($courier);
        if (! $origin) {
            return [
                'available' => false,
                'provider' => 'unavailable',
                'distance_km' => null,
                'duration_minutes' => null,
            ];
        }

        return $this->distances->estimateBetweenPoints($origin, $pickupPoint, [
            'speed_kmh' => 28,
        ]);
    }

    private function courierPoint(User $courier): ?array
    {
        if ($this->hasCourierPoint($courier)) {
            return [
                'lat' => (float) $courier->latitude,
                'lng' => (float) $courier->longitude,
                'label' => $courier->name,
            ];
        }

        return $this->distances->pointForBranch($courier->branch);
    }

    private function hasCourierPoint(User $courier): bool
    {
        return is_numeric($courier->latitude) && is_numeric($courier->longitude);
    }

    private function pickupPoint(PickupRequest $pickup): ?array
    {
        $lat = $pickup->sender_latitude ?: $pickup->latitude;
        $lng = $pickup->sender_longitude ?: $pickup->longitude;

        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return $this->distances->pointForBranch($pickup->branch);
        }

        return [
            'lat' => (float) $lat,
            'lng' => (float) $lng,
            'label' => 'Pickup #'.$pickup->id,
        ];
    }

    private function activePickupCount(User $courier): int
    {
        return DB::table('pickup_requests')
            ->where('courier_id', $courier->id)
            ->whereIn('status', ['assigned', 'picked_up'])
            ->count();
    }

    private function sameDayPickupCount(User $courier, PickupRequest $pickup): int
    {
        if (! $pickup->pickup_date) {
            return 0;
        }

        return DB::table('pickup_requests')
            ->where('courier_id', $courier->id)
            ->whereDate('pickup_date', $pickup->pickup_date)
            ->whereIn('status', ['assigned', 'picked_up'])
            ->count();
    }

    private function activeShipmentCount(User $courier): int
    {
        return DB::table('shipments')
            ->where('courier_id', $courier->id)
            ->whereIn('status', ['pending', 'picked_up', 'in_transit', 'arrived_at_branch', 'out_for_delivery'])
            ->count();
    }
}
