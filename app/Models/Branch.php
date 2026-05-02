<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $guarded = [];

    public function bankAccounts()
    {
        return $this->hasMany(BankAccount::class);
    }

    public function manager()
    {
        return $this->hasOne(User::class)->where('role', 'manager');
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
