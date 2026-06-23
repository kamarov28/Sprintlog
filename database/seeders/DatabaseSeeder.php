<?php

namespace Database\Seeders;

use App\Models\BankAccount;
use App\Models\Branch;
use App\Models\Customer;
use App\Support\HubCrewIdentity;
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
        $this->call(LocationSeeder::class);
        $this->call(NationalBranchSeeder::class);
        $this->call(HubCrewSeeder::class);
        $this->call(LandingSectionSeeder::class);

        $rateMatrix = [
            [1, 1, 9000, 1], [1, 2, 18000, 2], [1, 3, 22000, 2], [1, 4, 28000, 3], [1, 5, 38000, 4], [1, 6, 55000, 5],
            [2, 1, 18000, 2], [2, 2, 9000, 1], [2, 3, 25000, 2], [2, 4, 32000, 3], [2, 5, 42000, 4], [2, 6, 60000, 5],
            [3, 1, 22000, 2], [3, 2, 25000, 2], [3, 3, 9000, 1], [3, 4, 22000, 2], [3, 5, 35000, 3], [3, 6, 50000, 4],
            [4, 1, 28000, 3], [4, 2, 32000, 3], [4, 3, 22000, 2], [4, 4, 9000, 1], [4, 5, 30000, 3], [4, 6, 45000, 4],
            [5, 1, 38000, 4], [5, 2, 42000, 4], [5, 3, 35000, 3], [5, 4, 30000, 3], [5, 5, 9000, 1], [5, 6, 35000, 3],
            [6, 1, 55000, 5], [6, 2, 60000, 5], [6, 3, 50000, 4], [6, 4, 45000, 4], [6, 5, 35000, 3], [6, 6, 9000, 1],
        ];

        foreach ($rateMatrix as [$oz, $dz, $price, $days]) {
            Rate::updateOrCreate(
                ['origin_zone' => $oz, 'destination_zone' => $dz],
                ['price_per_kg' => $price, 'estimated_days' => $days],
            );
        }

        $branchJkt = Branch::updateOrCreate(
            ['name' => 'SprintLog Hub DKI Jakarta'],
            [
                'city' => 'DKI Jakarta',
                'address' => 'Balai Kota DKI Jakarta, Jl. Medan Merdeka Selatan No.8-9, Jakarta Pusat',
                'phone' => '1500-P001',
                'latitude' => -6.1805,
                'longitude' => 106.8284,
            ],
        );

        $branchMks = Branch::updateOrCreate(
            ['name' => 'SprintLog Hub Sulawesi Selatan'],
            [
                'city' => 'Sulawesi Selatan',
                'address' => 'Kantor Gubernur Sulawesi Selatan, Jl. Urip Sumoharjo No.269, Makassar',
                'phone' => '1500-P073',
                'latitude' => -5.1477,
                'longitude' => 119.4327,
            ],
        );

        $admin = User::updateOrCreate(
            ['email' => 'admin@sprintlog.com'],
            [
                'name' => 'Admin Utama',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'branch_id' => $branchJkt->id,
            ],
        );

        BankAccount::updateOrCreate(
            ['branch_id' => $branchJkt->id, 'account_number' => '1234567890'],
            ['bank_name' => 'BCA', 'account_holder' => 'SprintLog DKI Jakarta', 'created_by' => $admin->id],
        );

        BankAccount::updateOrCreate(
            ['branch_id' => $branchJkt->id, 'account_number' => '0987654321'],
            ['bank_name' => 'Mandiri', 'account_holder' => 'SprintLog DKI Jakarta', 'created_by' => $admin->id],
        );

        BankAccount::updateOrCreate(
            ['branch_id' => $branchMks->id, 'account_number' => '1122334455'],
            ['bank_name' => 'BCA', 'account_holder' => 'SprintLog Sulawesi Selatan', 'created_by' => $admin->id],
        );

        BankAccount::updateOrCreate(
            ['branch_id' => $branchMks->id, 'account_number' => '5566778899'],
            ['bank_name' => 'Mandiri', 'account_holder' => 'SprintLog Sulawesi Selatan', 'created_by' => $admin->id],
        );

        User::updateOrCreate(
            ['email' => HubCrewIdentity::email('cashier', $branchJkt->name)],
            [
                'name' => 'Kasir DKI Jakarta',
                'password' => Hash::make('password'),
                'role' => 'cashier',
                'branch_id' => $branchJkt->id,
            ],
        );

        User::updateOrCreate(
            ['email' => HubCrewIdentity::email('manager', $branchJkt->name)],
            [
                'name' => 'Manajer DKI Jakarta',
                'password' => Hash::make('password'),
                'role' => 'manager',
                'branch_id' => $branchJkt->id,
            ],
        );

        $courier = User::updateOrCreate(
            ['email' => HubCrewIdentity::email('courier', $branchJkt->name)],
            [
                'name' => 'Kurir Satria',
                'password' => Hash::make('password'),
                'role' => 'courier',
                'branch_id' => $branchJkt->id,
            ],
        );

        $truckCourier = User::updateOrCreate(
            ['email' => 'kurirtruk-'.HubCrewIdentity::hubSlug($branchJkt->name).'@sprintlog.com'],
            [
                'name' => 'Kurir Truk Jakarta',
                'password' => Hash::make('password'),
                'role' => 'courier',
                'branch_id' => $branchJkt->id,
            ],
        );

        Vehicle::updateOrCreate(
            ['plate_number' => 'B 1234 SPL'],
            [
                'type' => 'motor',
                'capacity_kg' => 35,
                'capacity_packages' => 8,
                'status' => 'active',
                'courier_id' => $courier->id,
                'branch_id' => $branchJkt->id,
            ],
        );

        Vehicle::updateOrCreate(
            ['plate_number' => 'B 9001 TRK'],
            [
                'type' => 'truck',
                'capacity_kg' => 1200,
                'capacity_packages' => 180,
                'status' => 'active',
                'courier_id' => $truckCourier->id,
                'branch_id' => $branchJkt->id,
            ],
        );

        $sender = Customer::updateOrCreate(
            ['email' => 'andi@example.com'],
            [
                'name' => 'Andi Pratama',
                'password' => Hash::make('password'),
                'address' => 'Jl. Saharjo No. 10, Tebet',
                'city' => 'Jakarta Selatan',
                'phone' => '08123456789',
            ],
        );

        $receiver = Customer::updateOrCreate(
            ['email' => 'sinta@example.com'],
            [
                'name' => 'Sinta Dewi',
                'password' => Hash::make('password'),
                'address' => 'Jl. Veteran No. 50, Makassar',
                'city' => 'Makassar',
                'phone' => '08998877665',
            ],
        );

        $rate = Rate::where('origin_zone', 1)
            ->where('destination_zone', 4)
            ->first();

        $shipment = Shipment::updateOrCreate(
            ['tracking_number' => 'SPRINT-12345678'],
            [
                'sender_id' => $sender->id,
                'receiver_id' => $receiver->id,
                'origin_branch_id' => $branchJkt->id,
                'destination_branch_id' => $branchMks->id,
                'courier_id' => $truckCourier->id,
                'rate_id' => $rate->id,
                'total_weight' => 2,
                'total_price' => $rate->price_per_kg * 2,
                'status' => 'in_transit',
                'shipment_date' => now()->toDateString(),
            ],
        );

        ShipmentItem::updateOrCreate(
            [
                'shipment_id' => $shipment->id,
                'item_name' => 'Paket Kaos Ekspedisi',
            ],
            [
                'quantity' => 2,
                'weight' => 2,
            ],
        );

        Payment::updateOrCreate(
            ['shipment_id' => $shipment->id],
            [
                'amount' => $rate->price_per_kg * 2,
                'payment_method' => 'transfer',
                'payment_status' => 'paid',
                'payment_date' => now()->toDateString(),
            ],
        );

        foreach ([
            ['Gudang SprintLog Jakarta', 'Paket diterima & ditimbang oleh kasir (Pick Up)', 'picked_up', now()->subHours(6)],
            ['SprintLog Hub Jakarta', 'Paket masuk Gudang Sortir & menunggu pengiriman intra-kota', 'in_transit', now()->subHours(4)],
            ['Bandara Soekarno-Hatta', 'Paket dimuat ke pesawat tujuan Makassar', 'in_transit', now()->subHours(2)],
        ] as [$loc, $desc, $status, $time]) {
            $shipment->trackings()->updateOrCreate(
                [
                    'location' => $loc,
                    'description' => $desc,
                ],
                [
                    'status' => $status,
                    'tracked_at' => $time,
                ],
            );
        }
    }
}
