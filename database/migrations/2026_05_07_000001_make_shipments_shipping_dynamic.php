<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql' && Schema::hasColumn('shipments', 'rate_id')) {
            $foreignKey = DB::selectOne("
                SELECT CONSTRAINT_NAME AS name
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = 'shipments'
                    AND COLUMN_NAME = 'rate_id'
                    AND REFERENCED_TABLE_NAME = 'rates'
                LIMIT 1
            ");

            if ($foreignKey?->name) {
                DB::statement('ALTER TABLE shipments DROP FOREIGN KEY '.$foreignKey->name);
            }

            DB::statement('ALTER TABLE shipments MODIFY rate_id BIGINT UNSIGNED NULL');
            DB::statement('ALTER TABLE shipments ADD CONSTRAINT shipments_rate_id_foreign FOREIGN KEY (rate_id) REFERENCES rates(id) ON DELETE SET NULL');
        }

        Schema::table('shipments', function (Blueprint $table) {
            if (! Schema::hasColumn('shipments', 'shipping_cost_source')) {
                $table->string('shipping_cost_source')->nullable()->after('total_price');
            }
            if (! Schema::hasColumn('shipments', 'shipping_courier_code')) {
                $table->string('shipping_courier_code')->nullable()->after('shipping_cost_source');
            }
            if (! Schema::hasColumn('shipments', 'shipping_courier_name')) {
                $table->string('shipping_courier_name')->nullable()->after('shipping_courier_code');
            }
            if (! Schema::hasColumn('shipments', 'shipping_courier_service')) {
                $table->string('shipping_courier_service')->nullable()->after('shipping_courier_name');
            }
            if (! Schema::hasColumn('shipments', 'shipping_courier_description')) {
                $table->string('shipping_courier_description')->nullable()->after('shipping_courier_service');
            }
            if (! Schema::hasColumn('shipments', 'shipping_origin_ro_id')) {
                $table->unsignedBigInteger('shipping_origin_ro_id')->nullable()->after('shipping_courier_description');
            }
            if (! Schema::hasColumn('shipments', 'shipping_destination_ro_id')) {
                $table->unsignedBigInteger('shipping_destination_ro_id')->nullable()->after('shipping_origin_ro_id');
            }
            if (! Schema::hasColumn('shipments', 'shipping_estimated_days')) {
                $table->unsignedSmallInteger('shipping_estimated_days')->nullable()->after('shipping_destination_ro_id');
            }
            if (! Schema::hasColumn('shipments', 'shipping_quote_payload')) {
                $table->json('shipping_quote_payload')->nullable()->after('shipping_estimated_days');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $columns = array_values(array_filter([
                Schema::hasColumn('shipments', 'shipping_cost_source') ? 'shipping_cost_source' : null,
                Schema::hasColumn('shipments', 'shipping_courier_code') ? 'shipping_courier_code' : null,
                Schema::hasColumn('shipments', 'shipping_courier_name') ? 'shipping_courier_name' : null,
                Schema::hasColumn('shipments', 'shipping_courier_service') ? 'shipping_courier_service' : null,
                Schema::hasColumn('shipments', 'shipping_courier_description') ? 'shipping_courier_description' : null,
                Schema::hasColumn('shipments', 'shipping_origin_ro_id') ? 'shipping_origin_ro_id' : null,
                Schema::hasColumn('shipments', 'shipping_destination_ro_id') ? 'shipping_destination_ro_id' : null,
                Schema::hasColumn('shipments', 'shipping_estimated_days') ? 'shipping_estimated_days' : null,
                Schema::hasColumn('shipments', 'shipping_quote_payload') ? 'shipping_quote_payload' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });

        if (DB::getDriverName() === 'mysql' && Schema::hasColumn('shipments', 'rate_id')) {
            $fallbackRateId = DB::table('rates')->orderBy('id')->value('id');
            if ($fallbackRateId) {
                DB::table('shipments')->whereNull('rate_id')->update(['rate_id' => $fallbackRateId]);
            }

            $foreignKey = DB::selectOne("
                SELECT CONSTRAINT_NAME AS name
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = 'shipments'
                    AND COLUMN_NAME = 'rate_id'
                    AND REFERENCED_TABLE_NAME = 'rates'
                LIMIT 1
            ");

            if ($foreignKey?->name) {
                DB::statement('ALTER TABLE shipments DROP FOREIGN KEY '.$foreignKey->name);
            }

            DB::statement('ALTER TABLE shipments MODIFY rate_id BIGINT UNSIGNED NOT NULL');
            DB::statement('ALTER TABLE shipments ADD CONSTRAINT shipments_rate_id_foreign FOREIGN KEY (rate_id) REFERENCES rates(id)');
        }
    }
};
