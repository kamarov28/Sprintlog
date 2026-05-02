<?php

namespace Database\Seeders;

use App\Models\LandingSection;
use Illuminate\Database\Seeder;

class LandingSectionSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->sections() as $section) {
            LandingSection::updateOrCreate(
                ['key' => $section['key']],
                $section
            );
        }
    }

    private function sections(): array
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
