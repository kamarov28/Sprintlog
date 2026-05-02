<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PickupRequest extends Model
{
    protected $guarded = [];

    protected $casts = [
        'cash_collected_at' => 'datetime',
        'cash_handover_at' => 'datetime',
    ];

    public function courier()
    {
        return $this->belongsTo(User::class, 'courier_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }

    public function senderCity()
    {
        return $this->belongsTo(Location::class, 'sender_city_id');
    }

    public function receiverCity()
    {
        return $this->belongsTo(Location::class, 'receiver_city_id');
    }

    public function statusAudits()
    {
        return $this->hasMany(PickupStatusAudit::class, 'pickup_request_id')->latest();
    }

    public function latestStatusAudit()
    {
        return $this->hasOne(PickupStatusAudit::class, 'pickup_request_id')->latestOfMany();
    }

    public function cashCollector()
    {
        return $this->belongsTo(User::class, 'cash_collected_by');
    }

    public function cashVerifier()
    {
        return $this->belongsTo(User::class, 'cash_verified_by');
    }
}
