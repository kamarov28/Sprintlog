<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShipmentNotification extends Model
{
    protected $guarded = [];

    protected $casts = [
        'sent_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
