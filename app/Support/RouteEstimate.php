<?php

namespace App\Support;

class RouteEstimate
{
    public static function make(array $points, array $options = []): array
    {
        $points = collect($points)
            ->filter(fn ($point) => self::validPoint($point))
            ->map(fn ($point) => [
                'label' => (string) ($point['label'] ?? 'Point'),
                'address' => (string) ($point['address'] ?? ''),
                'lat' => (float) $point['lat'],
                'lng' => (float) $point['lng'],
            ])
            ->values();

        if ($points->count() < 2) {
            return [
                'available' => false,
                'reason' => 'Koordinat asal dan tujuan belum lengkap.',
                'points' => $points,
            ];
        }

        $airDistance = 0.0;
        for ($i = 0; $i < $points->count() - 1; $i++) {
            $airDistance += self::haversineKm($points[$i]['lat'], $points[$i]['lng'], $points[$i + 1]['lat'], $points[$i + 1]['lng']);
        }

        $roadFactor = (float) ($options['road_factor'] ?? 1.28);
        $speedKmh = max(8, (float) ($options['speed_kmh'] ?? 28));
        $distanceKm = round($airDistance * $roadFactor, 1);
        $durationMinutes = (int) max(5, ceil(($distanceKm / $speedKmh) * 60));

        return [
            'available' => true,
            'label' => (string) ($options['label'] ?? 'Courier route'),
            'mode' => (string) ($options['mode'] ?? 'driving'),
            'points' => $points,
            'distance_km' => $distanceKm,
            'duration_minutes' => $durationMinutes,
            'duration_label' => self::durationLabel($durationMinutes),
            'google_url' => self::googleDirectionsUrl($points->all(), (string) ($options['mode'] ?? 'driving')),
            'note' => (string) ($options['note'] ?? 'Estimasi sistem. Google Maps dapat memberi durasi real-time.'),
        ];
    }

    private static function validPoint(array $point): bool
    {
        return isset($point['lat'], $point['lng']) && is_numeric($point['lat']) && is_numeric($point['lng']);
    }

    private static function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadiusKm = 6371;
        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lngDelta / 2) ** 2;

        return $earthRadiusKm * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private static function durationLabel(int $minutes): string
    {
        if ($minutes < 60) {
            return $minutes.' menit';
        }

        $hours = intdiv($minutes, 60);
        $remaining = $minutes % 60;

        return trim($hours.' jam'.($remaining ? ' '.$remaining.' menit' : ''));
    }

    private static function googleDirectionsUrl(array $points, string $mode): string
    {
        $origin = $points[0]['lat'].','.$points[0]['lng'];
        $destinationPoint = $points[count($points) - 1];
        $destination = $destinationPoint['lat'].','.$destinationPoint['lng'];
        $waypoints = array_slice($points, 1, -1);

        $query = [
            'api' => '1',
            'origin' => $origin,
            'destination' => $destination,
            'travelmode' => $mode,
        ];

        if ($waypoints !== []) {
            $query['waypoints'] = implode('|', array_map(fn ($point) => $point['lat'].','.$point['lng'], $waypoints));
        }

        return 'https://www.google.com/maps/dir/?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }
}
