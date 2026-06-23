<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'courier_status')) {
                $table->string('courier_status')->default('available')->after('role');
            }

            if (! Schema::hasColumn('users', 'last_location_at')) {
                $table->timestamp('last_location_at')->nullable()->after('longitude');
            }
        });

        Schema::table('pickup_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('pickup_requests', 'auto_assignment_score')) {
                $table->decimal('auto_assignment_score', 10, 2)->nullable()->after('courier_id');
            }

            if (! Schema::hasColumn('pickup_requests', 'auto_assignment_meta')) {
                $table->json('auto_assignment_meta')->nullable()->after('auto_assignment_score');
            }
        });

        Schema::table('shipment_legs', function (Blueprint $table) {
            if (! Schema::hasColumn('shipment_legs', 'distance_km')) {
                $table->decimal('distance_km', 10, 2)->nullable()->after('vehicle_id');
            }

            if (! Schema::hasColumn('shipment_legs', 'duration_minutes')) {
                $table->unsignedInteger('duration_minutes')->nullable()->after('distance_km');
            }

            if (! Schema::hasColumn('shipment_legs', 'routing_provider')) {
                $table->string('routing_provider')->nullable()->after('duration_minutes');
            }

            if (! Schema::hasColumn('shipment_legs', 'route_meta')) {
                $table->json('route_meta')->nullable()->after('routing_provider');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shipment_legs', function (Blueprint $table) {
            $columns = array_values(array_filter([
                Schema::hasColumn('shipment_legs', 'distance_km') ? 'distance_km' : null,
                Schema::hasColumn('shipment_legs', 'duration_minutes') ? 'duration_minutes' : null,
                Schema::hasColumn('shipment_legs', 'routing_provider') ? 'routing_provider' : null,
                Schema::hasColumn('shipment_legs', 'route_meta') ? 'route_meta' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });

        Schema::table('pickup_requests', function (Blueprint $table) {
            $columns = array_values(array_filter([
                Schema::hasColumn('pickup_requests', 'auto_assignment_score') ? 'auto_assignment_score' : null,
                Schema::hasColumn('pickup_requests', 'auto_assignment_meta') ? 'auto_assignment_meta' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });

        Schema::table('users', function (Blueprint $table) {
            $columns = array_values(array_filter([
                Schema::hasColumn('users', 'courier_status') ? 'courier_status' : null,
                Schema::hasColumn('users', 'last_location_at') ? 'last_location_at' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
