<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Branch;
use App\Models\Location;
use App\Services\ShipmentRoutePlanner;
use App\Services\ShippingCostService;

$cases = [
    ['Bandung', 'Surabaya', 5, 'REGULAR'],
    ['Jakarta', 'Medan', 10, 'REGULAR'],
    ['Surabaya', 'Malang', 3, 'REGULAR'],
];

$planner = $app->make(ShipmentRoutePlanner::class);
$estimator = $app->make(ShippingCostService::class);

function findBranchByName($name)
{
    return Branch::where('city', 'like', "%{$name}%")
        ->orWhere('name', 'like', "%{$name}%")
        ->first();
}

function findCityByName($name)
{
    return Location::where('type', 'kota')
        ->where('name', 'like', "%{$name}%")
        ->first();
}

$results = [];

foreach ($cases as $case) {
    [$originName, $destName, $weight, $service] = $case;

    $originBranch = findBranchByName($originName);
    $destBranch = findBranchByName($destName);

    $originCity = findCityByName($originName);
    $destCity = findCityByName($destName);

    $routeNames = null;
    $legsCount = null;
    $estimate = null;
    $finalPrice = null;
    $multiplier = null;

    if ($originBranch && $destBranch) {
        $route = $planner->branchesFor($originBranch, $destBranch);
        $routeNames = $route->map(fn($b) => $b->name)->values()->toArray();
        $legsCount = max(0, $route->count() - 1);
    }

    if ($originCity && $destCity) {
        $estimate = $estimator->estimateFromCities($originCity, $destCity, (float) $weight, $service);
    }

    if ($estimate) {
        $extraLegs = max(0, ($legsCount ?: 0) - 1);
        $multiplier = 1 + (0.10 * $extraLegs);
        $finalPrice = round(((float) $estimate['total_price']) * $multiplier, 2);
    }

    $results[] = [
        'pair' => "{$originName} -> {$destName}",
        'origin_branch' => $originBranch?->name ?? null,
        'destination_branch' => $destBranch?->name ?? null,
        'route' => $routeNames,
        'legs_count' => $legsCount,
        'estimate_total_price' => $estimate['total_price'] ?? null,
        'estimate_source' => $estimate['source'] ?? null,
        'multiplier' => $multiplier,
        'final_price' => $finalPrice,
    ];
}

echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
