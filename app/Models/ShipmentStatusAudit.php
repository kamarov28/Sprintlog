<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShipmentStatusAudit extends Model
{
    protected $guarded = [];

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
