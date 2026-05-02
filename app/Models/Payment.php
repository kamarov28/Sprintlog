<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $guarded = [];

    protected $casts = [
        'payment_date' => 'date',
    ];

    public function shipment()
    {
        return $this->belongsTo(Shipment::class, 'shipment_id');
    }
}
