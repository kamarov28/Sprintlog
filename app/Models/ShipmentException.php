<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShipmentException extends Model
{
    protected $guarded = [];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }

    public function leg()
    {
        return $this->belongsTo(ShipmentLeg::class, 'shipment_leg_id');
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }
}
