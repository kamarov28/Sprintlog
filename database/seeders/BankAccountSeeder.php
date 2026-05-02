<?php

namespace Database\Seeders;

use App\Models\BankAccount;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;

class BankAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();
        $branches = Branch::all();

        foreach ($branches as $branch) {
            BankAccount::create([
                'branch_id' => $branch->id,
                'bank_name' => 'BCA',
                'account_number' => '123456789'.$branch->id,
                'account_holder' => 'SprintLog '.$branch->city,
                'created_by' => $admin->id,
            ]);

            BankAccount::create([
                'branch_id' => $branch->id,
                'bank_name' => 'Mandiri',
                'account_number' => '987654321'.$branch->id,
                'account_holder' => 'SprintLog '.$branch->city,
                'created_by' => $admin->id,
            ]);
        }
    }
}
