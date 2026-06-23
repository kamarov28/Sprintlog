<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vehicle extends Model
{
    protected $guarded = [];

    protected $casts = [
        'capacity_kg' => 'float',
        'capacity_packages' => 'integer',
    ];

    public function courier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'courier_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function canCarry(float $weightKg, int $packageCount = 1): bool
    {
        return $this->isActive()
            && (float) $this->capacity_kg >= $weightKg
            && (int) $this->capacity_packages >= $packageCount;
    }

    public function label(): string
    {
        return strtoupper($this->type).' / '.$this->plate_number.' / '
            .number_format((float) $this->capacity_kg, 0, ',', '.').' KG / '
            .number_format((int) $this->capacity_packages, 0, ',', '.').' paket';
    }
}
