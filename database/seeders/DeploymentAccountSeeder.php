<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use App\Models\Vehicle;
use App\Support\HubCrewIdentity;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DeploymentAccountSeeder extends Seeder
{
    public function run(): void
    {
        $branchPayload = [
            'city' => env('DEPLOY_BRANCH_CITY', 'DKI Jakarta'),
            'address' => env('DEPLOY_BRANCH_ADDRESS', 'Balai Kota DKI Jakarta, Jl. Medan Merdeka Selatan No.8-9, Jakarta Pusat'),
            'phone' => env('DEPLOY_BRANCH_PHONE', '1500-P001'),
        ];

        if (Schema::hasColumn('branches', 'latitude')) {
            $branchPayload['latitude'] = (float) env('DEPLOY_BRANCH_LATITUDE', -6.1805);
        }

        if (Schema::hasColumn('branches', 'longitude')) {
            $branchPayload['longitude'] = (float) env('DEPLOY_BRANCH_LONGITUDE', 106.8284);
        }

        $branchName = env('DEPLOY_BRANCH_NAME', 'SprintLog Hub DKI Jakarta');

        $branch = Branch::updateOrCreate(
            ['name' => $branchName],
            $branchPayload,
        );

        $this->upsertUser(
            env('DEPLOY_ADMIN_EMAIL', 'admin@sprintlog.com'),
            env('DEPLOY_ADMIN_NAME', 'Admin Utama'),
            env('DEPLOY_ADMIN_PASSWORD', 'password'),
            'admin',
            $branch->id,
        );

        $this->upsertUser(
            env('DEPLOY_CASHIER_EMAIL', HubCrewIdentity::email('cashier', $branchName)),
            env('DEPLOY_CASHIER_NAME', 'Kasir DKI Jakarta'),
            env('DEPLOY_CASHIER_PASSWORD', 'password'),
            'cashier',
            $branch->id,
        );

        $this->upsertUser(
            env('DEPLOY_MANAGER_EMAIL', HubCrewIdentity::email('manager', $branchName)),
            env('DEPLOY_MANAGER_NAME', 'Manajer DKI Jakarta'),
            env('DEPLOY_MANAGER_PASSWORD', 'password'),
            'manager',
            $branch->id,
        );

        $courier = $this->upsertUser(
            env('DEPLOY_COURIER_EMAIL', HubCrewIdentity::email('courier', $branchName)),
            env('DEPLOY_COURIER_NAME', 'Kurir Satria'),
            env('DEPLOY_COURIER_PASSWORD', 'password'),
            'courier',
            $branch->id,
        );

        Vehicle::updateOrCreate(
            ['courier_id' => $courier->id],
            [
                'plate_number' => env('DEPLOY_COURIER_VEHICLE_PLATE', 'B 9001 TRK'),
                'type' => env('DEPLOY_COURIER_VEHICLE_TYPE', 'truck'),
                'capacity_kg' => (float) env('DEPLOY_COURIER_VEHICLE_CAPACITY_KG', 1200),
                'capacity_packages' => (int) env('DEPLOY_COURIER_VEHICLE_CAPACITY_PACKAGES', 180),
                'status' => 'active',
                'branch_id' => $branch->id,
            ],
        );
    }

    private function upsertUser(string $email, string $name, string $password, string $role, int $branchId): User
    {
        return User::updateOrCreate(
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
