<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
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
        $existing = 0;

        Branch::query()
            ->orderBy('id')
            ->chunk(100, function ($branches) use (&$created, &$existing): void {
                foreach ($branches as $branch) {
                    foreach (self::ROLES as $role => $label) {
                        $hasCrew = User::query()
                            ->where('branch_id', $branch->id)
                            ->where('role', $role)
                            ->exists();

                        if ($hasCrew) {
                            $existing++;

                            continue;
                        }

                        User::updateOrCreate(
                            ['email' => $this->emailForBranchRole((int) $branch->id, $role)],
                            [
                                'name' => $label.' '.$this->hubDisplayName($branch->name),
                                'password' => Hash::make('password'),
                                'role' => $role,
                                'branch_id' => $branch->id,
                                'phone' => $this->phoneForBranchRole((int) $branch->id, $role),
                                'address' => $branch->address,
                                'city' => $branch->city,
                            ],
                        );

                        $created++;
                    }
                }
            });

        $this->command?->info("Hub crew synced. Created: {$created}. Existing role slots: {$existing}.");
    }

    private function hubDisplayName(string $branchName): string
    {
        return trim(Str::replaceFirst('SprintLog Hub ', '', $branchName));
    }

    private function emailForBranchRole(int $branchId, string $role): string
    {
        return "hub{$branchId}.{$role}@sprintlog.local";
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
}
