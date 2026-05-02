<?php

namespace App\Services;

use App\Models\Shipment;
use App\Models\ShipmentNotification;

class ShipmentNotifier
{
    public function record(Shipment $shipment, string $event, string $title, string $message): ShipmentNotification
    {
        return ShipmentNotification::create([
            'shipment_id' => $shipment->id,
            'user_id' => $shipment->user_id,
            'event' => $event,
            'channel' => 'system',
            'title' => $title,
            'message' => $message,
            'sent_at' => now(),
        ]);
    }
}
