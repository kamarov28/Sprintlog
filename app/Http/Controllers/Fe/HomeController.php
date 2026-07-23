<?php

namespace App\Http\Controllers\Fe;

use App\Http\Controllers\Controller;
use App\Models\LandingSection;
use App\Models\Location;
use App\Models\Shipment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        // 1. Get all provinces for the cascading dropdown (Cached as array for 24 hours to prevent unserialize issues)
        $provinces = collect();
        if (Schema::hasTable('locations')) {
            $provincesArray = \Illuminate\Support\Facades\Cache::remember('locations_provinces_arr', 86400, function () {
                return Location::query()
                    ->where('type', 'provinsi')
                    ->selectRaw('MIN(id) as id, name, zone')
                    ->groupBy('name', 'zone')
                    ->orderBy('name')
                    ->get()
                    ->toArray();
            });
            $provinces = collect($provincesArray)->map(function ($item) {
                return (object) $item;
            });
        }

        // 2. Logic: Tracking (Cek Resi)
        $shipment = null;
        if ($request->filled('receipt')) {
            $shipment = Shipment::with('trackings')
                ->where('tracking_number', strtoupper(trim($request->receipt)))
                ->first();
        }

        // 3. Logic: Rate Check (Cek Ongkir)
        $rateResult = null;

        // Cache landing sections active data as array for 24 hours to prevent unserialize issues
        $landingSectionsArray = Schema::hasTable('landing_sections')
            ? \Illuminate\Support\Facades\Cache::remember('landing_sections_active_arr', 86400, function () {
                return LandingSection::where('is_active', true)->orderBy('sort_order')->orderBy('id')->get()->toArray();
            })
            : [];

        $landingSections = collect($landingSectionsArray)->map(function ($item) {
            if (isset($item['settings']) && is_string($item['settings'])) {
                $item['settings'] = json_decode($item['settings'], true);
            }
            return (object) $item;
        });

        $landing = [
            'hero' => $landingSections->firstWhere('type', 'hero'),
            'route_steps' => $landingSections->where('type', 'route_step')->values(),
            'service_cards' => $landingSections->where('type', 'service_card')->values(),
            'feature_panels' => $landingSections->where('type', 'feature_panel')->values(),
            'ctas' => $landingSections->where('type', 'cta')->values(),
        ];

        return view('fe.home', compact('provinces', 'shipment', 'rateResult', 'landing'));
    }
}
