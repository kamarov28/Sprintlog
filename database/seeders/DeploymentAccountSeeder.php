<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DeploymentAccountSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => env('DEPLOY_ADMIN_EMAIL', 'admin@sprintlog.com')],
            [
                'name' => env('DEPLOY_ADMIN_NAME', 'Admin Utama'),
                'password' => Hash::make(env('DEPLOY_ADMIN_PASSWORD', 'password')),
                'role' => 'admin',
            ],
        );
    }
}
