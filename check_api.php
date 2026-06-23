<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$res = Illuminate\Support\Facades\Http::asForm()->withHeaders([
    'key' => config('services.komerce.shipping_cost.key')
])->post(config('services.komerce.shipping_cost.base_url').'/calculate/domestic-cost', [
    'origin' => 54,
    'destination' => 133,
    'weight' => 20000,
    'courier' => 'jne:jnt:sicepat:pos',
    'price' => 'lowest'
]);

echo json_encode($res->json(), JSON_PRETTY_PRINT);
