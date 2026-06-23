<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Location;
use App\Models\PickupRequest;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\ShipmentRoutePlanner;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SimulateFlow extends Command
{
    protected $signature = 'simulate:flow';
    protected $description = 'Simulate the full package flow from pickup to shipment activation';

    public function handle()
    {
        $this->info("Memulai simulasi alur paket...");

        // 1. Setup locations and branches if not exist
        $originCity = Location::firstOrCreate(['name' => 'Kota Simulasi Asal', 'type' => 'kota', 'zone' => 1]);
        $destCity = Location::firstOrCreate(['name' => 'Kota Simulasi Tujuan', 'type' => 'kota', 'zone' => 2]);

        $branch = Branch::firstOrCreate(
            ['name' => 'Hub Asal Simulasi'],
            ['city' => 'Kota Simulasi Asal', 'latitude' => -6.2, 'longitude' => 106.8, 'address' => 'Jl. Simulasi Asal', 'phone' => '0811']
        );
        $destBranch = Branch::firstOrCreate(
            ['name' => 'Hub Tujuan Simulasi'],
            ['city' => 'Kota Simulasi Tujuan', 'latitude' => -6.9, 'longitude' => 107.6, 'address' => 'Jl. Simulasi Tujuan', 'phone' => '0822']
        );

        // 2. Setup users (manager, courier, customer)
        $manager = User::firstOrCreate(
            ['email' => 'manager.sim@sprintlog.local'],
            ['name' => 'Manager Sim', 'role' => 'manager', 'branch_id' => $branch->id, 'password' => bcrypt('password')]
        );
        $cashier = User::firstOrCreate(
            ['email' => 'cashier.sim@sprintlog.local'],
            ['name' => 'Cashier Sim', 'role' => 'cashier', 'branch_id' => $branch->id, 'password' => bcrypt('password')]
        );
        $courier = User::firstOrCreate(
            ['email' => 'courier.sim@sprintlog.local'],
            ['name' => 'Courier Sim', 'role' => 'courier', 'branch_id' => $branch->id, 'password' => bcrypt('password')]
        );
        $customer = User::firstOrCreate(
            ['email' => 'customer.sim@sprintlog.local'],
            ['name' => 'Customer Sim', 'role' => 'customer', 'password' => bcrypt('password')]
        );

        // Vehicle for courier
        Vehicle::firstOrCreate(
            ['courier_id' => $courier->id],
            ['type' => 'motor', 'plate_number' => 'B 1234 SIM']
        );

        $this->info("1. Customer membuat pesanan pickup...");
        $pickup = PickupRequest::create([
            'user_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_phone' => '081234567890',
            'pickup_address' => 'Jl. Customer Sim',
            'pickup_date' => now()->addDay()->toDateString(),
            'receiver_name' => 'Penerima Sim',
            'receiver_phone' => '089876543210',
            'receiver_address' => 'Jl. Penerima Sim',
            'weight' => 2,
            'service_type' => 'reg',
            'sender_city_id' => $originCity->id,
            'receiver_city_id' => $destCity->id,
            'total_price' => 20000,
            'payment_method' => 'cash_on_pickup',
            'branch_id' => $branch->id,
            'status' => 'pending'
        ]);

        $this->info("   -> Pickup Request ID: {$pickup->id}");

        $this->info("2. Manager menugaskan kurir...");
        $pickup->update([
            'courier_id' => $courier->id,
            'status' => 'assigned'
        ]);

        $this->info("3. Kurir melakukan pickup dan menerima uang tunai...");
        $pickup->update([
            'status' => 'picked_up',
            'payment_status' => 'cash_collected_by_courier',
            'cash_received_amount' => 20000,
            'cash_collected_at' => now(),
            'cash_collected_by' => $courier->id
        ]);

        $this->info("4. Manager mengonfirmasi paket tiba di Hub Asal...");
        $pickup->update([
            'status' => 'hub_received'
        ]);

        $this->info("5. Kasir memverifikasi setoran uang tunai...");
        $pickup->update([
            'payment_status' => 'paid',
            'cash_handover_at' => now(),
            'cash_verified_by' => $cashier->id
        ]);

        $this->info("6. Manager mengaktifkan paket menjadi Shipment resmi...");
        
        $sender = Customer::firstOrCreate(
            ['phone' => '081234567890'],
            ['name' => $customer->name, 'city' => $originCity->name, 'email' => 'sender@sim.local', 'password' => bcrypt('password'), 'address' => 'Jl. Customer Sim']
        );
        $receiver = Customer::firstOrCreate(
            ['phone' => '089876543210'],
            ['name' => 'Penerima Sim', 'city' => $destCity->name, 'email' => 'recv@sim.local', 'password' => bcrypt('password'), 'address' => 'Jl. Penerima Sim']
        );

        $shipment = Shipment::create([
            'user_id' => $customer->id,
            'tracking_number' => 'SIM-'.date('Ymd').'-'.strtoupper(Str::random(4)),
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'origin_branch_id' => $branch->id,
            'destination_branch_id' => $destBranch->id,
            'total_weight' => $pickup->weight,
            'total_price' => $pickup->total_price,
            'status' => 'pending',
            'shipment_date' => now(),
            'shipping_estimated_days' => 2,
        ]);

        $pickup->update(['shipment_id' => $shipment->id]);
        
        $shipment->trackings()->create([
            'location' => $branch->name,
            'description' => 'Pickup sudah diterima hub dan shipment aktif (Simulasi).',
            'status' => 'pending',
            'tracked_at' => now(),
        ]);
        
        if (class_exists(ShipmentRoutePlanner::class)) {
            app(ShipmentRoutePlanner::class)->createLegsFor($shipment);
        }

        $this->info("   -> Sukses! Nomor Resi: {$shipment->tracking_number}");
        $this->info("Simulasi selesai. Anda bisa mengecek resi tersebut di sistem.");
    }
}
