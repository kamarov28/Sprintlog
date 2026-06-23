<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use App\Models\Vehicle;
use App\Support\HubCrewIdentity;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class HubCrewSeeder extends Seeder
{
    private const ROLES = [
        'manager' => 'Manajer',
        'cashier' => 'Kasir',
        'courier' => 'Kurir',
    ];

    public function run(): void
    {
        $created = 0;
        $updated = 0;

        Branch::query()
            ->orderBy('id')
            ->chunk(100, function ($branches) use (&$created, &$updated): void {
                foreach ($branches as $branch) {
                    foreach (self::ROLES as $role => $label) {
                        $email = HubCrewIdentity::email($role, $branch->name);

                        $crew = User::query()
                            ->where('branch_id', $branch->id)
                            ->where('role', $role)
                            ->orderBy('id')
                            ->first();

                        $payload = [
                            'email' => $email,
                            'name' => $label.' '.$this->hubDisplayName($branch->name),
                            'password' => Hash::make('password'),
                            'role' => $role,
                            'branch_id' => $branch->id,
                            'phone' => $this->phoneForBranchRole((int) $branch->id, $role),
                            'address' => $branch->address,
                            'city' => $branch->city,
                        ];

                        if ($crew) {
                            $crew->update($payload);
                            $updated++;
                        } else {
                            $crew = User::create($payload);
                            $created++;
                        }

                        if ($role === 'courier') {
                            $this->syncCourierVehicle($crew, $branch);
                        }
                    }
                }
            });

        $this->command?->info("Hub crew synced. Created: {$created}. Updated: {$updated}.");
    }

    private function hubDisplayName(string $branchName): string
    {
        return trim(Str::replaceFirst('SprintLog Hub ', '', $branchName));
    }

    private function phoneForBranchRole(int $branchId, string $role): string
    {
        $roleCode = [
            'manager' => '1',
            'cashier' => '2',
            'courier' => '3',
        ][$role] ?? '0';

        return '08'.$roleCode.str_pad((string) $branchId, 9, '0', STR_PAD_LEFT);
    }

    private function syncCourierVehicle(User $courier, Branch $branch): void
    {
        Vehicle::updateOrCreate(
            ['courier_id' => $courier->id],
            [
                'plate_number' => 'TRK-'.$branch->id,
                'type' => 'truck',
                'capacity_kg' => 1200,
                'capacity_packages' => 180,
                'status' => 'active',
                'branch_id' => $branch->id,
            ],
        );
    }
}
