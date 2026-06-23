<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Location;
use App\Models\Rate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ShippingCostService
{
    public function estimateFromCities(
        Location $originCity,
        Location $destinationCity,
        float $weightKg,
        string $serviceType = 'REGULAR'
    ): ?array {
        return $this->estimate(
            $originCity->name,
            $destinationCity->name,
            $weightKg,
            $serviceType,
            fn () => $this->localRateEstimate($originCity->zone, $destinationCity->zone, $weightKg, $serviceType)
        );
    }

    public function estimateFromBranch(
        Branch $originBranch,
        Location $destinationCity,
        float $weightKg,
        string $serviceType = 'REGULAR',
        ?Rate $fallbackRate = null
    ): ?array {
        return $this->estimate(
            $originBranch->city,
            $destinationCity->name,
            $weightKg,
            $serviceType,
            fn () => $fallbackRate ? $this->formatLocalEstimate($fallbackRate, $weightKg, $serviceType) : null
        );
    }

    /**
     * Force local rate calculation using `rates` table only.
     */
    public function localEstimateFromCities(Location $originCity, Location $destinationCity, float $weightKg, string $serviceType = 'REGULAR'): ?array
    {
        return $this->localRateEstimate($originCity?->zone ?? null, $destinationCity?->zone ?? null, $weightKg, $serviceType);
    }

    private function estimate(string $originSearch, string $destinationSearch, float $weightKg, string $serviceType, callable $fallback): ?array
    {
        if ($this->apiEnabled()) {
            $estimate = null;

            try {
                $estimate = $this->rajaOngkirEstimate($originSearch, $destinationSearch, $weightKg, $serviceType);
            } catch (\Throwable) {
                $estimate = null;
            }

            if ($estimate) {
                return $estimate;
            }
        }

        return $fallback();
    }

    private function rajaOngkirEstimate(string $originSearch, string $destinationSearch, float $weightKg, string $serviceType): ?array
    {
        $originId = $this->domesticDestinationId($originSearch);
        $destinationId = $this->domesticDestinationId($destinationSearch);

        if (! $originId || ! $destinationId) {
            \Log::warning('RajaOngkir ID lookup failed', [
                'originSearch' => $originSearch,
                'originId' => $originId,
                'destinationSearch' => $destinationSearch,
                'destinationId' => $destinationId,
            ]);
            return null;
        }

        $response = Http::asForm()
            ->timeout($this->timeout())
            ->withHeaders(['key' => $this->apiKey()])
            ->post($this->baseUrl().'/calculate/domestic-cost', [
                'origin' => $originId,
                'destination' => $destinationId,
                'weight' => max(1, (int) ceil($weightKg * 1000)),
                'courier' => $this->couriers(),
                'price' => $this->priceMode(),
            ]);

        if (! $response->successful()) {
            \Log::warning('RajaOngkir API call failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'originId' => $originId,
                'destinationId' => $destinationId,
            ]);
            return null;
        }

        $options = collect($response->json('data') ?? [])
            ->filter(fn ($option) => isset($option['cost']))
            ->values();

        if ($options->isEmpty()) {
            \Log::warning('RajaOngkir API returned no options', [
                'originId' => $originId,
                'destinationId' => $destinationId,
                'weight' => $weightKg,
                'response' => $response->json(),
            ]);
            return null;
        }

        $selected = $this->selectApiOption($options, $serviceType);
        
        $multiplier = match ($serviceType) {
            'BEST' => 1.3,
            'KARGO' => 0.7,
            default => 1.0,
        };

        $cost = ((float) ($selected['cost'] ?? 0)) * $multiplier;

        if ($cost <= 0) {
            return null;
        }

        return [
            'total_price' => $cost,
            'total_price_fmt' => 'Rp '.number_format($cost, 0, ',', '.'),
            'price_per_kg' => $cost / max(0.1, $weightKg),
            'estimated_days' => $this->parseEtdDays((string) ($selected['etd'] ?? '')),
            'service_type' => $serviceType,
            'source' => 'rajaongkir',
            'courier' => $selected['code'] ?? null,
            'courier_name' => $selected['name'] ?? null,
            'courier_service' => $selected['service'] ?? null,
            'courier_description' => $selected['description'] ?? null,
            'origin_ro_id' => $originId,
            'destination_ro_id' => $destinationId,
            'quote_payload' => $selected,
        ];
    }

    private function domesticDestinationId(string $search): ?int
    {
        $normalizedSearch = trim($search);

        if ($normalizedSearch === '') {
            return null;
        }

        $cacheKey = 'rajaongkir:domestic-destination:'.Str::slug(Str::lower($normalizedSearch));

        return Cache::remember($cacheKey, $this->cacheTtl(), function () use ($normalizedSearch) {
            try {
                $response = Http::timeout($this->timeout())
                    ->withHeaders(['key' => $this->apiKey()])
                    ->get($this->baseUrl().'/destination/domestic-destination', [
                        'search' => $normalizedSearch,
                        'limit' => 10,
                        'offset' => 0,
                    ]);
            } catch (\Throwable) {
                return null;
            }

            if (! $response->successful()) {
                return null;
            }

            $results = collect($response->json('data') ?? []);

            if ($results->isEmpty()) {
                return null;
            }

            $needle = Str::lower($normalizedSearch);
            $selected = $results->first(function ($result) use ($needle) {
                return Str::lower((string) ($result['city_name'] ?? '')) === $needle
                    || Str::contains(Str::lower((string) ($result['label'] ?? '')), $needle);
            }) ?? $results->first();

            return isset($selected['id']) ? (int) $selected['id'] : null;
        });
    }

    private function selectApiOption($options, string $serviceType): array
    {
        if ($serviceType === 'BEST') {
            return $options
                ->sortBy(fn ($option) => sprintf(
                    '%03d-%012.2f',
                    $this->parseEtdDays((string) ($option['etd'] ?? '')) ?? 999,
                    (float) ($option['cost'] ?? PHP_INT_MAX)
                ))
                ->first();
        }

        return $options->sortBy(fn ($option) => (float) ($option['cost'] ?? PHP_INT_MAX))->first();
    }

    private function localRateEstimate(?int $originZone, ?int $destinationZone, float $weightKg, string $serviceType): ?array
    {
        if (! $originZone || ! $destinationZone) {
            return null;
        }

        if (! Schema::hasTable('rates')) {
            return null;
        }

        $rate = Rate::where('origin_zone', $originZone)
            ->where('destination_zone', $destinationZone)
            ->first();

        return $rate ? $this->formatLocalEstimate($rate, $weightKg, $serviceType) : null;
    }

    private function formatLocalEstimate(Rate $rate, float $weightKg, string $serviceType): array
    {
        $multiplier = match ($serviceType) {
            'BEST' => 1.3,
            'KARGO' => 0.7,
            default => 1.0,
        };
        $totalPrice = ((float) $rate->price_per_kg * $multiplier) * max(0.1, $weightKg);

        return [
            'total_price' => $totalPrice,
            'total_price_fmt' => 'Rp '.number_format($totalPrice, 0, ',', '.'),
            'price_per_kg' => (float) $rate->price_per_kg * $multiplier,
            'estimated_days' => $serviceType === 'BEST' ? 1 : $rate->estimated_days,
            'service_type' => $serviceType,
            'source' => 'local',
            'rate_id' => $rate->id,
            'origin_zone' => $rate->origin_zone,
            'dest_zone' => $rate->destination_zone,
            'quote_payload' => [
                'source' => 'local',
                'rate_id' => $rate->id,
                'origin_zone' => $rate->origin_zone,
                'destination_zone' => $rate->destination_zone,
            ],
        ];
    }

    private function parseEtdDays(string $etd): ?int
    {
        if (preg_match('/\d+/', $etd, $matches)) {
            return (int) $matches[0];
        }

        return null;
    }

    private function apiEnabled(): bool
    {
        return (bool) config('services.komerce.shipping_cost.enabled') && $this->apiKey() !== '';
    }

    private function apiKey(): string
    {
        return (string) config('services.komerce.shipping_cost.key', '');
    }

    private function baseUrl(): string
    {
        return (string) config('services.komerce.shipping_cost.base_url');
    }

    private function timeout(): int
    {
        return max(1, (int) config('services.komerce.shipping_cost.timeout', 10));
    }

    private function cacheTtl(): int
    {
        return max(60, (int) config('services.komerce.shipping_cost.cache_ttl', 86400));
    }

    private function couriers(): string
    {
        return (string) config('services.komerce.shipping_cost.couriers', 'jne:jnt:sicepat:pos');
    }

    private function priceMode(): string
    {
        return (string) config('services.komerce.shipping_cost.price_mode', 'lowest');
    }
}
