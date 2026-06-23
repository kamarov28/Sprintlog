<?php

namespace App\Support;

use App\Services\RouteDistanceService;

class RouteEstimate
{
    public static function make(array $points, array $options = []): array
    {
        return app(RouteDistanceService::class)->estimateRoute($points, $options);
    }
}
