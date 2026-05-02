<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingSection extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
    ];
}
