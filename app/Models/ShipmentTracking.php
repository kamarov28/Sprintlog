<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShipmentTracking extends Model
{
    protected $guarded = [];

    protected $casts = [
        'tracked_at' => 'datetime',
    ];

    public function shipment()
    {
        return $this->belongsTo(Shipment::class, 'shipment_id');
    }
}
