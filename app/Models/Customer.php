<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $guarded = [];

    public function shipmentsAsSender()
    {
        return $this->hasMany(Shipment::class, 'sender_id');
    }

    public function shipmentsAsReceiver()
    {
        return $this->hasMany(Shipment::class, 'receiver_id');
    }
}
