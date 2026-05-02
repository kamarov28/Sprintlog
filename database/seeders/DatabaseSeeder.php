<?php

namespace Database\Seeders;

use App\Models\BankAccount;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Rate;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Locations first (all Provinces & Cities)
        $this->call(LocationSeeder::class);
        $this->call(NationalBranchSeeder::class);
        $this->call(HubCrewSeeder::class);

        // 2. Zone-Based Rate Matrix (6 zones × 6 zones = 36 entries)
        // Zone 1=Jawa&Bali, 2=Sumatera, 3=Kalimantan, 4=Sulawesi&NTB, 5=NTT&Maluku, 6=Papua
        $rateMatrix = [
            // [origin_zone, destination_zone, price_per_kg, estimated_days]
            [1, 1, 9000,  1], [1, 2, 18000, 2], [1, 3, 22000, 2], [1, 4, 28000, 3], [1, 5, 38000, 4], [1, 6, 55000, 5],
            [2, 1, 18000, 2], [2, 2, 9000,  1], [2, 3, 25000, 2], [2, 4, 32000, 3], [2, 5, 42000, 4], [2, 6, 60000, 5],
            [3, 1, 22000, 2], [3, 2, 25000, 2], [3, 3, 9000,  1], [3, 4, 22000, 2], [3, 5, 35000, 3], [3, 6, 50000, 4],
            [4, 1, 28000, 3], [4, 2, 32000, 3], [4, 3, 22000, 2], [4, 4, 9000,  1], [4, 5, 30000, 3], [4, 6, 45000, 4],
            [5, 1, 38000, 4], [5, 2, 42000, 4], [5, 3, 35000, 3], [5, 4, 30000, 3], [5, 5, 9000,  1], [5, 6, 35000, 3],
            [6, 1, 55000, 5], [6, 2, 60000, 5], [6, 3, 50000, 4], [6, 4, 45000, 4], [6, 5, 35000, 3], [6, 6, 9000,  1],
        ];

        foreach ($rateMatrix as [$oz, $dz, $price, $days]) {
            Rate::updateOrCreate(
                ['origin_zone' => $oz, 'destination_zone' => $dz],
                ['price_per_kg' => $price, 'estimated_days' => $days],
            );
        }

        // 3. Sample branches now point at province hubs instead of city-level hubs.
        $branchJkt = Branch::updateOrCreate(
            ['name' => 'SprintLog Hub DKI Jakarta'],
            [
                'city' => 'DKI Jakarta',
                'address' => 'Jl. Operasional SprintLog, DKI Jakarta',
                'phone' => '1500-P001',
            ],
        );

        $branchMks = Branch::updateOrCreate(
            ['name' => 'SprintLog Hub Sulawesi Selatan'],
            [
                'city' => 'Sulawesi Selatan',
                'address' => 'Jl. Operasional SprintLog, Sulawesi Selatan',
                'phone' => '1500-P073',
            ],
        );

        $admin = User::updateOrCreate(
            ['email' => 'admin@sprintlog.com'],
            ['name' => 'Admin Utama', 'password' => Hash::make('password'), 'role' => 'admin', 'branch_id' => $branchJkt->id],
        );

        // Bank Accounts
        BankAccount::updateOrCreate(['branch_id' => $branchJkt->id, 'account_number' => '1234567890'], ['bank_name' => 'BCA', 'account_holder' => 'SprintLog DKI Jakarta', 'created_by' => $admin->id]);
        BankAccount::updateOrCreate(['branch_id' => $branchJkt->id, 'account_number' => '0987654321'], ['bank_name' => 'Mandiri', 'account_holder' => 'SprintLog DKI Jakarta', 'created_by' => $admin->id]);
        BankAccount::updateOrCreate(['branch_id' => $branchMks->id, 'account_number' => '1122334455'], ['bank_name' => 'BCA', 'account_holder' => 'SprintLog Sulawesi Selatan', 'created_by' => $admin->id]);
        BankAccount::updateOrCreate(['branch_id' => $branchMks->id, 'account_number' => '5566778899'], ['bank_name' => 'Mandiri', 'account_holder' => 'SprintLog Sulawesi Selatan', 'created_by' => $admin->id]);

        // 4. Staff
        User::updateOrCreate(['email' => 'kasir@sprintlog.com'], ['name' => 'Kasir DKI Jakarta', 'password' => Hash::make('password'), 'role' => 'cashier', 'branch_id' => $branchJkt->id]);
        User::updateOrCreate(['email' => 'manager@sprintlog.com'], ['name' => 'Manajer DKI Jakarta', 'password' => Hash::make('password'), 'role' => 'manager', 'branch_id' => $branchJkt->id]);

        $courier = User::updateOrCreate(
            ['email' => 'kurir@sprintlog.com'],
            ['name' => 'Kurir Satria', 'password' => Hash::make('password'), 'role' => 'courier', 'branch_id' => $branchJkt->id],
        );

        Vehicle::create(['plate_number' => 'B 1234 SPL', 'type' => 'motor', 'courier_id' => $courier->id]);

        // 5. Sample Customers
        $sender = Customer::create([
            'name' => 'Andi Pratama', 'email' => 'andi@example.com',
            'password' => Hash::make('password'),
            'address' => 'Jl. Saharjo No. 10, Tebet', 'city' => 'Jakarta Selatan', 'phone' => '08123456789',
        ]);

        $receiver = Customer::create([
            'name' => 'Sinta Dewi', 'email' => 'sinta@example.com',
            'password' => Hash::make('password'),
            'address' => 'Jl. Veteran No. 50, Makassar', 'city' => 'Makassar', 'phone' => '08998877665',
        ]);

        // 6. Sample Shipment (Jakarta Zone 1 → Makassar Zone 4)
        $rate = Rate::where('origin_zone', 1)->where('destination_zone', 4)->first();

        $shipment = Shipment::create([
            'tracking_number' => 'SPRINT-12345678',
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'origin_branch_id' => $branchJkt->id,
            'destination_branch_id' => $branchMks->id,
            'courier_id' => $courier->id,
            'rate_id' => $rate->id,
            'total_weight' => 2,
            'total_price' => $rate->price_per_kg * 2,
            'status' => 'in_transit',
            'shipment_date' => now()->toDateString(),
        ]);

        ShipmentItem::create(['shipment_id' => $shipment->id, 'item_name' => 'Paket Kaos Ekspedisi', 'quantity' => 2, 'weight' => 2]);
        Payment::create(['shipment_id' => $shipment->id, 'amount' => $rate->price_per_kg * 2, 'payment_method' => 'transfer', 'payment_status' => 'paid', 'payment_date' => now()->toDateString()]);

        // 7. Tracking Logs
        foreach ([
            ['Gudang SprintLog Jakarta',    'Paket diterima & ditimbang oleh kasir (Pick Up)',          'picked_up',  now()->subHours(6)],
            ['SprintLog Hub Jakarta',        'Paket masuk Gudang Sortir & menunggu pengiriman intra-kota', 'in_transit', now()->subHours(4)],
            ['Bandara Soekarno-Hatta',       'Paket dimuat ke pesawat tujuan Makassar',                  'in_transit', now()->subHours(2)],
        ] as [$loc, $desc, $status, $time]) {
            $shipment->trackings()->create(['location' => $loc, 'description' => $desc, 'status' => $status, 'tracked_at' => $time]);
        }
    }
}
