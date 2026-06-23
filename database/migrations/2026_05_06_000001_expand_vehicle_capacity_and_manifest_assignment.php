<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            if (! Schema::hasColumn('vehicles', 'branch_id')) {
                $table->foreignId('branch_id')->nullable()->after('courier_id')->constrained('branches')->nullOnDelete();
            }

            if (! Schema::hasColumn('vehicles', 'capacity_kg')) {
                $table->decimal('capacity_kg', 10, 2)->nullable()->after('type');
            }

            if (! Schema::hasColumn('vehicles', 'capacity_packages')) {
                $table->unsignedInteger('capacity_packages')->nullable()->after('capacity_kg');
            }

            if (! Schema::hasColumn('vehicles', 'status')) {
                $table->string('status')->default('active')->after('capacity_packages');
            }
        });

        DB::table('vehicles')
            ->leftJoin('users', 'users.id', '=', 'vehicles.courier_id')
            ->whereNull('vehicles.branch_id')
            ->whereNotNull('users.branch_id')
            ->update(['vehicles.branch_id' => DB::raw('users.branch_id')]);

        DB::table('vehicles')
            ->whereNull('capacity_kg')
            ->update([
                'capacity_kg' => DB::raw("CASE type WHEN 'truck' THEN 1200 WHEN 'mobil' THEN 250 ELSE 35 END"),
                'capacity_packages' => DB::raw("CASE type WHEN 'truck' THEN 180 WHEN 'mobil' THEN 45 ELSE 8 END"),
                'status' => 'active',
            ]);

        Schema::table('shipment_legs', function (Blueprint $table) {
            if (! Schema::hasColumn('shipment_legs', 'vehicle_id')) {
                $table->foreignId('vehicle_id')->nullable()->after('handler_id')->constrained('vehicles')->nullOnDelete();
            }
        });

        Schema::table('shipment_manifests', function (Blueprint $table) {
            if (! Schema::hasColumn('shipment_manifests', 'courier_id')) {
                $table->foreignId('courier_id')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('shipment_manifests', 'vehicle_id')) {
                $table->foreignId('vehicle_id')->nullable()->after('courier_id')->constrained('vehicles')->nullOnDelete();
            }

            if (! Schema::hasColumn('shipment_manifests', 'package_count')) {
                $table->unsignedInteger('package_count')->default(0)->after('vehicle_id');
            }

            if (! Schema::hasColumn('shipment_manifests', 'total_weight')) {
                $table->decimal('total_weight', 10, 2)->default(0)->after('package_count');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shipment_manifests', function (Blueprint $table) {
            if (Schema::hasColumn('shipment_manifests', 'vehicle_id')) {
                $table->dropForeign(['vehicle_id']);
            }
            if (Schema::hasColumn('shipment_manifests', 'courier_id')) {
                $table->dropForeign(['courier_id']);
            }

            $columns = array_values(array_filter([
                Schema::hasColumn('shipment_manifests', 'courier_id') ? 'courier_id' : null,
                Schema::hasColumn('shipment_manifests', 'vehicle_id') ? 'vehicle_id' : null,
                Schema::hasColumn('shipment_manifests', 'package_count') ? 'package_count' : null,
                Schema::hasColumn('shipment_manifests', 'total_weight') ? 'total_weight' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });

        Schema::table('shipment_legs', function (Blueprint $table) {
            if (Schema::hasColumn('shipment_legs', 'vehicle_id')) {
                $table->dropForeign(['vehicle_id']);
                $table->dropColumn('vehicle_id');
            }
        });

        Schema::table('vehicles', function (Blueprint $table) {
            if (Schema::hasColumn('vehicles', 'branch_id')) {
                $table->dropForeign(['branch_id']);
            }

            $columns = array_values(array_filter([
                Schema::hasColumn('vehicles', 'branch_id') ? 'branch_id' : null,
                Schema::hasColumn('vehicles', 'capacity_kg') ? 'capacity_kg' : null,
                Schema::hasColumn('vehicles', 'capacity_packages') ? 'capacity_packages' : null,
                Schema::hasColumn('vehicles', 'status') ? 'status' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
