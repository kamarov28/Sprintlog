<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Services\ShippingCostService;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function provinsi()
    {
        // Fixing the relationship/scope name logic: we'll just use the where clause directly
        $provinsi = Location::query()
            ->where('type', 'provinsi')
            ->selectRaw('MIN(id) as id, name, zone')
            ->groupBy('name', 'zone')
            ->orderBy('name')
            ->get();

        return response()->json($provinsi);
    }

    public function kota(Request $request)
    {
        $request->validate(['provinsi_id' => 'required|integer|exists:locations,id']);
        $kota = Location::query()
            ->where('type', 'kota')
            ->where('parent_id', $request->provinsi_id)
            ->selectRaw('MIN(id) as id, name, zone')
            ->groupBy('name', 'zone', 'parent_id')
            ->orderBy('name')
            ->get(['id', 'name', 'zone']);

        return response()->json($kota);
    }

    public function calculateRate(Request $request)
    {
        $request->validate([
            'origin_kota_id' => 'required|integer|exists:locations,id',
            'destination_kota_id' => 'required|integer|exists:locations,id',
            'weight' => 'required|numeric|min:0.1',
            'service_type' => 'nullable|string|in:BEST,REGULAR,KARGO',
            'force_local' => 'nullable|boolean',
        ]);

        $serviceType = $request->service_type ?: 'REGULAR';

        // Validation for KARGO (Min 10kg)
        if ($serviceType === 'KARGO' && $request->weight < 10) {
            return response()->json(['error' => 'LAYANAN_KARGO: MINIMAL_BERAT_10KG.'], 422);
        }

        $originKota = Location::find($request->origin_kota_id);
        $destinationKota = Location::find($request->destination_kota_id);

        if ($request->boolean('force_local')) {
            $estimate = app(ShippingCostService::class)->localEstimateFromCities(
                $originKota,
                $destinationKota,
                (float) $request->weight,
                $serviceType
            );
        } else {
            $estimate = app(ShippingCostService::class)->estimateFromCities(
                $originKota,
                $destinationKota,
                (float) $request->weight,
                $serviceType
            );
        }

        if (! $estimate) {
            return response()->json(['error' => 'Rute tidak tersedia.'], 404);
        }

        // Optional debug info: append origin/destination zone and request inputs
        if ($request->boolean('debug')) {
            $estimate['debug'] = [
                'origin_kota_id' => $originKota?->id ?? null,
                'origin_zone' => $originKota?->zone ?? null,
                'destination_kota_id' => $destinationKota?->id ?? null,
                'destination_zone' => $destinationKota?->zone ?? null,
                'service_type_requested' => $serviceType,
                'weight_requested' => (float) $request->weight,
            ];
        }

        return response()->json($estimate);
    }
}
