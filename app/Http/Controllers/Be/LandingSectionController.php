<?php

namespace App\Http\Controllers\Be;

use App\Http\Controllers\Controller;
use App\Models\LandingSection;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class LandingSectionController extends Controller
{
    private function ensureAdmin(): void
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Landing CMS hanya untuk admin.');
        }
    }

    public function index(Request $request)
    {
        $this->ensureAdmin();

        $typeOptions = [
            'hero' => 'Hero',
            'route_step' => 'Route Step',
            'service_card' => 'Service Card',
            'feature_panel' => 'Feature Panel',
            'cta' => 'CTA',
        ];

        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'type' => (string) $request->query('type', ''),
            'status' => (string) $request->query('status', ''),
        ];

        $query = LandingSection::query()
            ->when($filters['search'] !== '', function ($query) use ($filters) {
                $search = $filters['search'];

                $query->where(function ($query) use ($search) {
                    $query->where('key', 'like', '%'.$search.'%')
                        ->orWhere('title', 'like', '%'.$search.'%')
                        ->orWhere('subtitle', 'like', '%'.$search.'%')
                        ->orWhere('body', 'like', '%'.$search.'%');
                });
            })
            ->when(array_key_exists($filters['type'], $typeOptions), fn ($query) => $query->where('type', $filters['type']))
            ->when($filters['status'] === 'active', fn ($query) => $query->where('is_active', true))
            ->when($filters['status'] === 'hidden', fn ($query) => $query->where('is_active', false));

        $sections = $query
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate(12)
            ->withQueryString();

        $totalCount = LandingSection::count();
        $activeCount = LandingSection::where('is_active', true)->count();
        $hiddenCount = max(0, $totalCount - $activeCount);
        $ctaCount = LandingSection::whereNotNull('button_text')
            ->where('button_text', '!=', '')
            ->whereNotNull('button_url')
            ->where('button_url', '!=', '')
            ->count();
        $copyReadyCount = LandingSection::whereNotNull('body')
            ->where('body', '!=', '')
            ->count();
        $lastUpdated = LandingSection::latest('updated_at')->value('updated_at');
        $lastUpdated = $lastUpdated ? Carbon::parse($lastUpdated) : null;
        $typeCounts = LandingSection::query()
            ->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        return view('be.landing-sections.index', compact(
            'sections',
            'activeCount',
            'copyReadyCount',
            'ctaCount',
            'filters',
            'hiddenCount',
            'lastUpdated',
            'totalCount',
            'typeCounts',
            'typeOptions',
        ));
    }

    public function create()
    {
        $this->ensureAdmin();

        return view('be.landing-sections.form', [
            'section' => new LandingSection([
                'type' => 'feature_panel',
                'sort_order' => LandingSection::max('sort_order') + 10,
                'is_active' => true,
            ]),
            'mode' => 'create',
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureAdmin();

        $data = $this->validatedData($request);
        LandingSection::create($data);

        return redirect()->route('be.landing-sections.index')->with('success', 'Landing module berhasil dibuat.');
    }

    public function edit(LandingSection $landingSection)
    {
        $this->ensureAdmin();

        return view('be.landing-sections.form', [
            'section' => $landingSection,
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, LandingSection $landingSection)
    {
        $this->ensureAdmin();

        $landingSection->update($this->validatedData($request, $landingSection));

        return redirect()->route('be.landing-sections.index')->with('success', 'Landing module berhasil diperbarui.');
    }

    public function destroy(LandingSection $landingSection)
    {
        $this->ensureAdmin();

        $landingSection->delete();

        return redirect()->route('be.landing-sections.index')->with('success', 'Landing module dihapus.');
    }

    public function seedDefaults()
    {
        $this->ensureAdmin();

        foreach ($this->defaultSections() as $section) {
            LandingSection::firstOrCreate(['key' => $section['key']], $section);
        }

        return redirect()->route('be.landing-sections.index')->with('success', 'Default landing modules yang belum ada berhasil dibuat.');
    }

    private function validatedData(Request $request, ?LandingSection $section = null): array
    {
        $ignoreId = $section?->id ? ','.$section->id : '';

        $data = $request->validate([
            'key' => 'required|string|max:120|alpha_dash|unique:landing_sections,key'.$ignoreId,
            'type' => 'required|in:hero,route_step,service_card,feature_panel,cta',
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'body' => 'nullable|string',
            'button_text' => 'nullable|string|max:120',
            'button_url' => 'nullable|string|max:255',
            'sort_order' => 'required|integer|min:0',
            'is_active' => 'nullable|boolean',
            'settings_kicker' => 'nullable|string|max:120',
            'settings_secondary_button_text' => 'nullable|string|max:120',
            'settings_secondary_button_url' => 'nullable|string|max:255',
            'settings_variant' => 'nullable|in:primary,accent,neutral',
            'settings_json' => 'nullable|string',
        ]);

        $settings = [];
        if (! empty($data['settings_json'])) {
            $settings = json_decode($data['settings_json'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                back()->withErrors(['settings_json' => 'Settings JSON tidak valid.'])->throwResponse();
            }
            if (! is_array($settings)) {
                back()->withErrors(['settings_json' => 'Settings JSON harus berbentuk object, contoh: {"variant":"primary"}'])->throwResponse();
            }
        }

        foreach (['button_url', 'settings_secondary_button_url'] as $urlField) {
            if (! empty($data[$urlField]) && ! $this->isSafeLandingUrl($data[$urlField])) {
                back()->withErrors([$urlField => 'URL harus diawali /, #, http://, atau https://.'])->withInput()->throwResponse();
            }
        }

        if ($data['type'] === 'hero') {
            $settings = array_filter(array_merge($settings, [
                'kicker' => $data['settings_kicker'] ?? null,
                'secondary_button_text' => $data['settings_secondary_button_text'] ?? null,
                'secondary_button_url' => $data['settings_secondary_button_url'] ?? null,
            ]), fn ($value) => filled($value));
        }

        if ($data['type'] === 'service_card') {
            $settings = array_filter(array_merge($settings, [
                'variant' => $data['settings_variant'] ?? 'neutral',
            ]), fn ($value) => filled($value));
        }

        return [
            'key' => $data['key'],
            'type' => $data['type'],
            'title' => $data['title'],
            'subtitle' => $data['subtitle'] ?? null,
            'body' => $data['body'] ?? null,
            'button_text' => $data['button_text'] ?? null,
            'button_url' => $data['button_url'] ?? null,
            'sort_order' => $data['sort_order'],
            'is_active' => (bool) ($data['is_active'] ?? false),
            'settings' => $settings ?: null,
        ];
    }

    private function isSafeLandingUrl(string $url): bool
    {
        return str_starts_with($url, '/')
            || str_starts_with($url, '#')
            || str_starts_with($url, 'http://')
            || str_starts_with($url, 'https://');
    }

    private function defaultSections(): array
    {
        return [
            [
                'key' => 'hero_main',
                'type' => 'hero',
                'title' => 'SPRINTLOG',
                'subtitle' => 'LOGISTICS PROTOCOL',
                'body' => '> Pickup-first shipment intake, live route estimation, and hub-verified delivery operations in one control surface.',
                'button_text' => 'INITIALIZE TRACKING',
                'button_url' => '/track',
                'sort_order' => 10,
                'is_active' => true,
                'settings' => [
                    'kicker' => 'INDONESIA ROUTING INTERFACE',
                    'secondary_button_text' => 'GET QUOTE',
                    'secondary_button_url' => '#rates',
                ],
            ],
            [
                'key' => 'route_request',
                'type' => 'route_step',
                'title' => '01 / REQUEST',
                'body' => 'Customer submits pickup-first shipment details.',
                'sort_order' => 20,
                'is_active' => true,
            ],
            [
                'key' => 'route_collect',
                'type' => 'route_step',
                'title' => '02 / COLLECT',
                'body' => 'Courier confirms pickup and cash collection when selected.',
                'sort_order' => 30,
                'is_active' => true,
            ],
            [
                'key' => 'route_verify',
                'type' => 'route_step',
                'title' => '03 / VERIFY',
                'body' => 'Cashier verifies package intake and payment handover.',
                'sort_order' => 40,
                'is_active' => true,
            ],
            [
                'key' => 'route_track',
                'type' => 'route_step',
                'title' => '04 / TRACK',
                'body' => 'Shipment record moves through hub tracking states.',
                'sort_order' => 50,
                'is_active' => true,
            ],
            [
                'key' => 'service_best',
                'type' => 'service_card',
                'title' => 'BEST',
                'subtitle' => '[ PRIORITY_S_CLASS ]',
                'body' => "1 Day Guaranteed Delivery.\n+30% Surcharge Applied.",
                'sort_order' => 80,
                'is_active' => true,
                'settings' => ['variant' => 'primary'],
            ],
            [
                'key' => 'service_regular',
                'type' => 'service_card',
                'title' => 'REGULAR',
                'subtitle' => '[ STANDARD_PROTOCOL ]',
                'body' => "Reliable 2-4 Day Routing.\nGlobal Node Accessibility.",
                'sort_order' => 90,
                'is_active' => true,
                'settings' => ['variant' => 'neutral'],
            ],
            [
                'key' => 'service_kargo',
                'type' => 'service_card',
                'title' => 'KARGO',
                'subtitle' => '[ HEAVY_H_CLASS ]',
                'body' => "Economic Bulk Shipping.\n-30% OFF | MIN 10KG.",
                'sort_order' => 100,
                'is_active' => true,
                'settings' => ['variant' => 'accent'],
            ],
        ];
    }
}
