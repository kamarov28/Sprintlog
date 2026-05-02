<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShipmentItem extends Model
{
    protected $guarded = [];

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }
}
