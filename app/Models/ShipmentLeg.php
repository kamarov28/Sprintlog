<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShipmentLeg extends Model
{
    protected $guarded = [];

    protected $casts = [
        'departed_at' => 'datetime',
        'arrived_at' => 'datetime',
        'planned_departure_at' => 'datetime',
        'planned_arrival_at' => 'datetime',
        'distance_km' => 'float',
        'route_meta' => 'array',
    ];

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }

    public function originBranch()
    {
        return $this->belongsTo(Branch::class, 'origin_branch_id');
    }

    public function destinationBranch()
    {
        return $this->belongsTo(Branch::class, 'destination_branch_id');
    }

    public function handler()
    {
        return $this->belongsTo(User::class, 'handler_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function exceptions()
    {
        return $this->hasMany(ShipmentException::class, 'shipment_leg_id');
    }
}
