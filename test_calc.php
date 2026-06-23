<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$req = Illuminate\Http\Request::create('/api/calculate-rate', 'GET', [
    'origin_kota_id' => 54, // Bogor
    'destination_kota_id' => 133, // Gresik
    'weight' => 15,
    'service_type' => 'REGULAR'
]);

$res = app()->handle($req);
echo $res->getContent();
