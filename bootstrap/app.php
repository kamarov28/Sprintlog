<?php

use App\Http\Middleware\EnsureBackendStaff;
use App\Http\Middleware\EnsureAdminOnly;
use App\Http\Middleware\EnsurePersonnelManager;
use App\Http\Middleware\EnsurePickupHubStaff;
use App\Http\Middleware\EnsureShipmentHubStaff;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request as HttpRequest;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(
            at: env('TRUSTED_PROXIES', '*'),
            headers: HttpRequest::HEADER_X_FORWARDED_FOR
                | HttpRequest::HEADER_X_FORWARDED_HOST
                | HttpRequest::HEADER_X_FORWARDED_PORT
                | HttpRequest::HEADER_X_FORWARDED_PROTO
                | HttpRequest::HEADER_X_FORWARDED_PREFIX
        );

        $middleware->alias([
            'be.admin' => EnsureAdminOnly::class,
            'be.staff' => EnsureBackendStaff::class,
            'pickup.hub' => EnsurePickupHubStaff::class,
            'shipment.hub' => EnsureShipmentHubStaff::class,
            'personnel.manager' => EnsurePersonnelManager::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
