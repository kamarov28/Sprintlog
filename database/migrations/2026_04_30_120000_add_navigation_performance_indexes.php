<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->index(['payment_status', 'payment_date'], 'payments_status_payment_date_index');
        });

        Schema::table('pickup_requests', function (Blueprint $table) {
            $table->index(['user_id', 'created_at'], 'pickup_requests_user_created_index');
        });

        Schema::table('shipments', function (Blueprint $table) {
            $table->index(['user_id', 'created_at'], 'shipments_user_created_index');
        });

        Schema::table('locations', function (Blueprint $table) {
            $table->index(['type', 'name'], 'locations_type_name_index');
            $table->index(['parent_id', 'name'], 'locations_parent_name_index');
        });
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropIndex('locations_type_name_index');
            $table->dropIndex('locations_parent_name_index');
        });

        Schema::table('shipments', function (Blueprint $table) {
            $table->dropIndex('shipments_user_created_index');
        });

        Schema::table('pickup_requests', function (Blueprint $table) {
            $table->dropIndex('pickup_requests_user_created_index');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_status_payment_date_index');
        });
    }
};
