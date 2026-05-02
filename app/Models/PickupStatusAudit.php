<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PickupStatusAudit extends Model
{
    protected $guarded = [];

    public function pickup()
    {
        return $this->belongsTo(PickupRequest::class, 'pickup_request_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
