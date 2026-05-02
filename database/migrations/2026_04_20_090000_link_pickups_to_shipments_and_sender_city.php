<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pickup_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('pickup_requests', 'sender_city_id')) {
                $table->foreignId('sender_city_id')
                    ->nullable()
                    ->after('sender_address')
                    ->constrained('locations')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('pickup_requests', 'shipment_id')) {
                $table->foreignId('shipment_id')
                    ->nullable()
                    ->after('branch_id')
                    ->constrained('shipments')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('pickup_requests', function (Blueprint $table) {
            if (Schema::hasColumn('pickup_requests', 'shipment_id')) {
                $table->dropConstrainedForeignId('shipment_id');
            }

            if (Schema::hasColumn('pickup_requests', 'sender_city_id')) {
                $table->dropConstrainedForeignId('sender_city_id');
            }
        });
    }
};
