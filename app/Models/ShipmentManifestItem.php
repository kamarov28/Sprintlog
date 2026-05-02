<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShipmentManifestItem extends Model
{
    protected $guarded = [];

    public function manifest()
    {
        return $this->belongsTo(ShipmentManifest::class, 'shipment_manifest_id');
    }

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }

    public function leg()
    {
        return $this->belongsTo(ShipmentLeg::class, 'shipment_leg_id');
    }
}
