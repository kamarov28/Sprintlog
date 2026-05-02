<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Rate;
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
        ]);

        $serviceType = $request->service_type ?: 'REGULAR';

        // Validation for KARGO (Min 10kg)
        if ($serviceType === 'KARGO' && $request->weight < 10) {
            return response()->json(['error' => 'LAYANAN_KARGO: MINIMAL_BERAT_10KG.'], 422);
        }

        $originKota = Location::find($request->origin_kota_id);
        $destinationKota = Location::find($request->destination_kota_id);

        $rate = Rate::where('origin_zone', $originKota->zone)
            ->where('destination_zone', $destinationKota->zone)
            ->first();

        if (! $rate) {
            return response()->json(['error' => 'Rute tidak tersedia.'], 404);
        }

        $weight = (float) $request->weight;
        $basePrice = $rate->price_per_kg * $weight;

        // Multipliers
        $multiplier = 1.0;
        if ($serviceType === 'BEST') {
            $multiplier = 1.3;
        }
        if ($serviceType === 'KARGO') {
            $multiplier = 0.7;
        }

        $totalPrice = $basePrice * $multiplier;

        return response()->json([
            'total_price' => $totalPrice,
            'total_price_fmt' => 'Rp '.number_format($totalPrice, 0, ',', '.'),
            'price_per_kg' => $rate->price_per_kg * $multiplier,
            'estimated_days' => $serviceType === 'BEST' ? 1 : $rate->estimated_days,
            'service_type' => $serviceType,
            'origin_zone' => $originKota->zone,
            'dest_zone' => $destinationKota->zone,
        ]);
    }
}
