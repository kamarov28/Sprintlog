<?php

namespace App\Services;

use App\Models\Branch;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class RouteDistanceService
{
    private const PROVINCE_CENTERS = [
        'aceh' => [5.5483, 95.3238],
        'sumatera utara' => [3.5952, 98.6722],
        'sumatera barat' => [-0.9471, 100.4172],
        'riau' => [0.5071, 101.4478],
        'kepulauan riau' => [0.9186, 104.4665],
        'jambi' => [-1.6101, 103.6131],
        'sumatera selatan' => [-2.9761, 104.7754],
        'kepulauan bangka belitung' => [-2.1296, 106.1138],
        'bengkulu' => [-3.7928, 102.2608],
        'lampung' => [-5.3971, 105.2668],
        'banten' => [-6.1201, 106.1503],
        'dki jakarta' => [-6.2088, 106.8456],
        'jawa barat' => [-6.9175, 107.6191],
        'jawa tengah' => [-6.9667, 110.4167],
        'di yogyakarta' => [-7.7956, 110.3695],
        'jawa timur' => [-7.2575, 112.7521],
        'bali' => [-8.6500, 115.2167],
        'nusa tenggara barat' => [-8.5833, 116.1167],
        'nusa tenggara timur' => [-10.1772, 123.6070],
        'kalimantan barat' => [-0.0263, 109.3425],
        'kalimantan tengah' => [-2.2096, 113.9108],
        'kalimantan selatan' => [-3.3186, 114.5944],
        'kalimantan timur' => [-0.5022, 117.1536],
        'kalimantan utara' => [2.8375, 117.3653],
        'sulawesi utara' => [1.4748, 124.8421],
        'gorontalo' => [0.5435, 123.0568],
        'sulawesi tengah' => [-0.9003, 119.8779],
        'sulawesi barat' => [-2.6748, 118.8950],
        'sulawesi selatan' => [-5.1477, 119.4327],
        'sulawesi tenggara' => [-3.9985, 122.5120],
        'maluku' => [-3.6954, 128.1814],
        'maluku utara' => [0.7893, 127.3842],
        'papua barat' => [-0.8615, 134.0620],
        'papua barat daya' => [-0.8629, 131.2545],
        'papua' => [-2.5916, 140.6690],
        'papua tengah' => [-3.3667, 135.5000],
        'papua pegunungan' => [-4.0000, 138.9500],
        'papua selatan' => [-8.4932, 140.4018],
    ];

    public function estimateBetweenBranches(Branch $origin, Branch $destination, array $options = []): array
    {
        $originPoint = $this->pointForBranch($origin);
        $destinationPoint = $this->pointForBranch($destination);

        if (! $originPoint || ! $destinationPoint) {
            return $this->unavailable('Koordinat hub belum lengkap.', $options);
        }

        return $this->estimateBetweenPoints($originPoint, $destinationPoint, $options);
    }

    public function estimateRoute(array $points, array $options = []): array
    {
        $points = collect($points)
            ->filter(fn ($point) => $this->validPoint($point))
            ->map(fn ($point) => [
                'label' => (string) ($point['label'] ?? 'Point'),
                'address' => (string) ($point['address'] ?? ''),
                'lat' => (float) $point['lat'],
                'lng' => (float) $point['lng'],
            ])
            ->values();

        if ($points->count() < 2) {
            return $this->unavailable('Koordinat asal dan tujuan belum lengkap.', $options, $points->all());
        }

        $legs = [];
        $distance = 0.0;
        $duration = 0;
        $providers = [];

        for ($i = 0; $i < $points->count() - 1; $i++) {
            $estimate = $this->estimateBetweenPoints($points[$i], $points[$i + 1], $options);
            $legs[] = $estimate;
            $distance += (float) ($estimate['distance_km'] ?? 0);
            $duration += (int) ($estimate['duration_minutes'] ?? 0);
            $providers[] = $estimate['provider'] ?? 'fallback';
        }

        return [
            'available' => true,
            'label' => (string) ($options['label'] ?? 'Courier route'),
            'mode' => (string) ($options['mode'] ?? 'driving'),
            'points' => $points,
            'distance_km' => round($distance, 1),
            'duration_minutes' => max(5, $duration),
            'duration_label' => $this->durationLabel(max(5, $duration)),
            'provider' => in_array('osrm', $providers, true) ? 'osrm' : 'fallback',
            'google_url' => $this->googleDirectionsUrl(
                $points->all(),
                (string) ($options['mode'] ?? 'driving'),
                (bool) ($options['use_current_location_origin'] ?? false)
            ),
            'note' => (string) ($options['note'] ?? 'Estimasi rute sistem. Google Maps dapat memberi durasi real-time.'),
            'uses_current_location_origin' => (bool) ($options['use_current_location_origin'] ?? false),
            'legs' => $legs,
        ];
    }

    public function estimateBetweenPoints(array $origin, array $destination, array $options = []): array
    {
        if (! $this->validPoint($origin) || ! $this->validPoint($destination)) {
            return $this->unavailable('Koordinat asal dan tujuan belum lengkap.', $options);
        }

        $origin = ['lat' => (float) $origin['lat'], 'lng' => (float) $origin['lng']];
        $destination = ['lat' => (float) $destination['lat'], 'lng' => (float) $destination['lng']];

        if (! ($options['force_fallback'] ?? false) && $this->osrmEnabled()) {
            $estimate = $this->estimateWithOsrm($origin, $destination);
            if ($estimate) {
                return $estimate;
            }
        }

        return $this->fallbackEstimate($origin, $destination, $options);
    }

    public function pointForBranch(?Branch $branch): ?array
    {
        if (! $branch) {
            return null;
        }

        if (is_numeric($branch->latitude) && is_numeric($branch->longitude)) {
            return [
                'lat' => (float) $branch->latitude,
                'lng' => (float) $branch->longitude,
                'label' => $branch->name,
                'address' => trim((string) ($branch->address ?: $branch->city)),
            ];
        }

        $center = self::PROVINCE_CENTERS[$this->normalizeRegionName($branch->city)] ?? null;
        if (! $center) {
            $haystack = $this->normalizeRegionName($branch->name.' '.$branch->address.' '.$branch->city);
            foreach (self::PROVINCE_CENTERS as $province => $candidate) {
                if (str_contains($haystack, $province)) {
                    $center = $candidate;
                    break;
                }
            }
        }

        if (! $center) {
            return null;
        }

        return [
            'lat' => $center[0],
            'lng' => $center[1],
            'label' => $branch->name,
            'address' => trim((string) ($branch->address ?: $branch->city)),
            'source' => 'province_center',
        ];
    }

    private function estimateWithOsrm(array $origin, array $destination): ?array
    {
        $cacheKey = 'routing.osrm.'.md5(json_encode([$origin, $destination]));

        return Cache::remember($cacheKey, $this->cacheTtl(), function () use ($origin, $destination) {
            $baseUrl = rtrim((string) config('services.routing.osrm_base_url'), '/');
            $coordinates = $origin['lng'].','.$origin['lat'].';'.$destination['lng'].','.$destination['lat'];

            try {
                $response = Http::timeout((int) config('services.routing.timeout', 4))
                    ->acceptJson()
                    ->get($baseUrl.'/route/v1/driving/'.$coordinates, [
                        'overview' => 'false',
                        'alternatives' => 'false',
                        'steps' => 'false',
                    ]);
            } catch (\Throwable) {
                return null;
            }

            if (! $response->ok()) {
                return null;
            }

            $route = $response->json('routes.0');
            if (! is_array($route) || ! isset($route['distance'], $route['duration'])) {
                return null;
            }

            $durationMinutes = (int) max(5, ceil(((float) $route['duration']) / 60));

            return [
                'available' => true,
                'provider' => 'osrm',
                'distance_km' => round(((float) $route['distance']) / 1000, 1),
                'duration_minutes' => $durationMinutes,
                'duration_label' => $this->durationLabel($durationMinutes),
            ];
        });
    }

    private function fallbackEstimate(array $origin, array $destination, array $options = []): array
    {
        $roadFactor = (float) ($options['road_factor'] ?? config('services.routing.fallback_road_factor', 1.28));
        $speedKmh = max(8, (float) ($options['speed_kmh'] ?? config('services.routing.fallback_speed_kmh', 42)));
        $distance = $this->haversineKm($origin['lat'], $origin['lng'], $destination['lat'], $destination['lng']) * $roadFactor;
        $durationMinutes = (int) max(5, ceil(($distance / $speedKmh) * 60));

        return [
            'available' => true,
            'provider' => 'fallback',
            'distance_km' => round($distance, 1),
            'duration_minutes' => $durationMinutes,
            'duration_label' => $this->durationLabel($durationMinutes),
        ];
    }

    private function unavailable(string $reason, array $options = [], array $points = []): array
    {
        return [
            'available' => false,
            'reason' => $reason,
            'points' => $points,
            'provider' => 'unavailable',
            'note' => (string) ($options['note'] ?? $reason),
        ];
    }

    private function osrmEnabled(): bool
    {
        return (bool) config('services.routing.osrm_enabled', false)
            && (string) config('services.routing.osrm_base_url', '') !== '';
    }

    private function cacheTtl(): int
    {
        return max(60, (int) config('services.routing.cache_ttl', 86400));
    }

    private function validPoint(array $point): bool
    {
        return isset($point['lat'], $point['lng']) && is_numeric($point['lat']) && is_numeric($point['lng']);
    }

    private function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadiusKm = 6371;
        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lngDelta / 2) ** 2;

        return $earthRadiusKm * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function durationLabel(int $minutes): string
    {
        if ($minutes < 60) {
            return $minutes.' menit';
        }

        $hours = intdiv($minutes, 60);
        $remaining = $minutes % 60;

        return trim($hours.' jam'.($remaining ? ' '.$remaining.' menit' : ''));
    }

    private function googleDirectionsUrl(array $points, string $mode, bool $useCurrentLocationOrigin = false): string
    {
        $destinationPoint = $points[count($points) - 1];
        $destination = $destinationPoint['lat'].','.$destinationPoint['lng'];
        $waypoints = $useCurrentLocationOrigin
            ? array_slice($points, 0, -1)
            : array_slice($points, 1, -1);

        $query = [
            'api' => '1',
            'destination' => $destination,
            'travelmode' => $mode,
        ];

        if (! $useCurrentLocationOrigin) {
            $query['origin'] = $points[0]['lat'].','.$points[0]['lng'];
        }

        if ($waypoints !== []) {
            $query['waypoints'] = implode('|', array_map(fn ($point) => $point['lat'].','.$point['lng'], $waypoints));
        }

        return 'https://www.google.com/maps/dir/?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    private function normalizeRegionName(?string $value): string
    {
        $value = strtolower(trim((string) $value));
        $value = preg_replace('/^(provinsi|kota|kabupaten|kab\.?)\s+/i', '', $value) ?? $value;

        return preg_replace('/\s+/', ' ', $value) ?? $value;
    }
}
