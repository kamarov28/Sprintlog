<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DeploymentAccountSeeder extends Seeder
{
    public function run(): void
    {
        $branch = Branch::updateOrCreate(
            ['name' => env('DEPLOY_BRANCH_NAME', 'SprintLog Hub DKI Jakarta')],
            [
                'city' => env('DEPLOY_BRANCH_CITY', 'DKI Jakarta'),
                'address' => env('DEPLOY_BRANCH_ADDRESS', 'Jl. Operasional SprintLog, DKI Jakarta'),
                'phone' => env('DEPLOY_BRANCH_PHONE', '1500-P001'),
            ],
        );

        $this->upsertUser(
            env('DEPLOY_ADMIN_EMAIL', 'admin@sprintlog.com'),
            env('DEPLOY_ADMIN_NAME', 'Admin Utama'),
            env('DEPLOY_ADMIN_PASSWORD', 'password'),
            'admin',
            $branch->id,
        );

        $this->upsertUser(
            env('DEPLOY_CASHIER_EMAIL', 'kasir@sprintlog.com'),
            env('DEPLOY_CASHIER_NAME', 'Kasir DKI Jakarta'),
            env('DEPLOY_CASHIER_PASSWORD', 'password'),
            'cashier',
            $branch->id,
        );

        $this->upsertUser(
            env('DEPLOY_MANAGER_EMAIL', 'manager@sprintlog.com'),
            env('DEPLOY_MANAGER_NAME', 'Manajer DKI Jakarta'),
            env('DEPLOY_MANAGER_PASSWORD', 'password'),
            'manager',
            $branch->id,
        );

        $this->upsertUser(
            env('DEPLOY_COURIER_EMAIL', 'kurir@sprintlog.com'),
            env('DEPLOY_COURIER_NAME', 'Kurir Satria'),
            env('DEPLOY_COURIER_PASSWORD', 'password'),
            'courier',
            $branch->id,
        );
    }

    private function upsertUser(string $email, string $name, string $password, string $role, int $branchId): void
    {
        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'role' => $role,
                'branch_id' => $branchId,
            ],
        );
    }
}
