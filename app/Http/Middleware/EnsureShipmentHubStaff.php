<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureShipmentHubStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! in_array(Auth::user()->role, ['manager', 'cashier', 'courier'], true)) {
            return redirect()->route('be.dashboard')
                ->with('error', 'Manifes pengiriman hanya untuk manajer cabang, kasir, dan kurir.');
        }

        return $next($request);
    }
}
