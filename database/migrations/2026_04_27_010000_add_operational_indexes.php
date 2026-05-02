<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->index(['name', 'city'], 'branches_name_city_index');
            $table->index(['latitude', 'longitude'], 'branches_coordinates_index');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index(['role', 'branch_id'], 'users_role_branch_id_index');
        });

        Schema::table('shipments', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'shipments_status_created_at_index');
            $table->index(['origin_branch_id', 'status'], 'shipments_origin_status_index');
            $table->index(['destination_branch_id', 'status'], 'shipments_destination_status_index');
            $table->index(['courier_id', 'status'], 'shipments_courier_status_index');
        });

        Schema::table('pickup_requests', function (Blueprint $table) {
            $table->index(['branch_id', 'status'], 'pickup_requests_branch_status_index');
            $table->index(['courier_id', 'status'], 'pickup_requests_courier_status_index');
            $table->index(['status', 'created_at'], 'pickup_requests_status_created_at_index');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->index(['payment_status', 'created_at'], 'payments_status_created_at_index');
            $table->index(['shipment_id', 'payment_status'], 'payments_shipment_status_index');
        });

        Schema::table('shipment_trackings', function (Blueprint $table) {
            $table->index(['shipment_id', 'tracked_at'], 'shipment_trackings_shipment_tracked_index');
        });

        Schema::table('pickup_status_audits', function (Blueprint $table) {
            $table->index(['pickup_request_id', 'created_at'], 'pickup_audits_pickup_created_index');
        });
    }

    public function down(): void
    {
        Schema::table('pickup_status_audits', function (Blueprint $table) {
            $table->dropIndex('pickup_audits_pickup_created_index');
        });

        Schema::table('shipment_trackings', function (Blueprint $table) {
            $table->dropIndex('shipment_trackings_shipment_tracked_index');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_status_created_at_index');
            $table->dropIndex('payments_shipment_status_index');
        });

        Schema::table('pickup_requests', function (Blueprint $table) {
            $table->dropIndex('pickup_requests_branch_status_index');
            $table->dropIndex('pickup_requests_courier_status_index');
            $table->dropIndex('pickup_requests_status_created_at_index');
        });

        Schema::table('shipments', function (Blueprint $table) {
            $table->dropIndex('shipments_status_created_at_index');
            $table->dropIndex('shipments_origin_status_index');
            $table->dropIndex('shipments_destination_status_index');
            $table->dropIndex('shipments_courier_status_index');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_role_branch_id_index');
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->dropIndex('branches_name_city_index');
            $table->dropIndex('branches_coordinates_index');
        });
    }
};
