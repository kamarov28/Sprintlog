<?php

namespace App\Support;

use Illuminate\Support\Str;

class HubCrewIdentity
{
    private const ROLE_SLUGS = [
        'manager' => 'manager',
        'cashier' => 'kasir',
        'courier' => 'kurir',
    ];

    public static function hubSlug(string $branchName): string
    {
        $display = trim(Str::replaceFirst('SprintLog Hub ', '', $branchName));

        return Str::lower(Str::slug($display, ''));
    }

    public static function email(string $role, string $branchName): string
    {
        $roleSlug = self::ROLE_SLUGS[$role] ?? $role;

        return $roleSlug.'-'.self::hubSlug($branchName).'@sprintlog.com';
    }
}
