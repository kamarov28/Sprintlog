<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePersonnelManager
{
    public function handle(Request $request, Closure $next): Response
    {
        $role = Auth::user()->role ?? null;

        if (! in_array($role, ['admin', 'manager'])) {
            return redirect()->route('be.dashboard')
                ->with('error', 'Akses ditolak. Fitur ini khusus Admin dan Manager.');
        }

        return $next($request);
    }
}
