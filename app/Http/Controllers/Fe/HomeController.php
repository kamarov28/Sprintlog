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
        // 1. Get all provinces for the cascading dropdown
        $provinces = collect();
        if (Schema::hasTable('locations')) {
            $provinces = Location::query()
                ->where('type', 'provinsi')
                ->selectRaw('MIN(id) as id, name, zone')
                ->groupBy('name', 'zone')
                ->orderBy('name')
                ->get();
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

        $landingSections = Schema::hasTable('landing_sections')
            ? LandingSection::where('is_active', true)->orderBy('sort_order')->orderBy('id')->get()
            : collect();

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
