<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShipmentManifest extends Model
{
    protected $guarded = [];

    protected $casts = [
        'departed_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(ShipmentManifestItem::class);
    }

    public function originBranch()
    {
        return $this->belongsTo(Branch::class, 'origin_branch_id');
    }

    public function destinationBranch()
    {
        return $this->belongsTo(Branch::class, 'destination_branch_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function courier()
    {
        return $this->belongsTo(User::class, 'courier_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
}
