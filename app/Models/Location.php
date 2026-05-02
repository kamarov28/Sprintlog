<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $guarded = [];

    public function children()
    {
        return $this->hasMany(Location::class, 'parent_id');
    }

    public function parentLocation()
    {
        return $this->belongsTo(Location::class, 'parent_id');
    }
}
