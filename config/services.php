<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'komerce' => [
        'shipping_cost' => [
            'enabled' => env('RAJAONGKIR_SHIPPING_ENABLED', false),
            'key' => env('RAJAONGKIR_SHIPPING_API_KEY'),
            'base_url' => rtrim((string) env('RAJAONGKIR_SHIPPING_BASE_URL', 'https://rajaongkir.komerce.id/api/v1'), '/'),
            'timeout' => (int) env('RAJAONGKIR_SHIPPING_TIMEOUT', 10),
            'cache_ttl' => (int) env('RAJAONGKIR_SHIPPING_CACHE_TTL', 86400),
            'couriers' => env('RAJAONGKIR_SHIPPING_COURIERS', 'jne:jnt:sicepat:pos'),
            'price_mode' => env('RAJAONGKIR_SHIPPING_PRICE_MODE', 'lowest'),
        ],
    ],

    'routing' => [
        'osrm_enabled' => env('ROUTING_OSRM_ENABLED', true),
        'osrm_base_url' => rtrim((string) env('ROUTING_OSRM_BASE_URL', 'https://router.project-osrm.org'), '/'),
        'timeout' => (int) env('ROUTING_TIMEOUT', 4),
        'cache_ttl' => (int) env('ROUTING_CACHE_TTL', 86400),
        'fallback_road_factor' => (float) env('ROUTING_FALLBACK_ROAD_FACTOR', 1.28),
        'fallback_speed_kmh' => (float) env('ROUTING_FALLBACK_SPEED_KMH', 42),
    ],

];
