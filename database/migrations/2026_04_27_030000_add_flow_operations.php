<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE shipments MODIFY COLUMN status ENUM('pending','picked_up','in_transit','arrived_at_branch','out_for_delivery','delivered','cancelled','delivery_failed','rescheduled','returned_to_hub','held','damaged','lost','exception') NOT NULL DEFAULT 'pending'");
        DB::statement("ALTER TABLE shipment_trackings MODIFY COLUMN status ENUM('pending','picked_up','in_transit','arrived_at_branch','out_for_delivery','delivered','cancelled','delivery_failed','rescheduled','returned_to_hub','held','damaged','lost','exception') NOT NULL");

        Schema::table('shipment_legs', function (Blueprint $table) {
            $table->timestamp('planned_departure_at')->nullable()->after('handler_id');
            $table->timestamp('planned_arrival_at')->nullable()->after('planned_departure_at');
            $table->string('delay_reason')->nullable()->after('notes');
        });

        Schema::create('shipment_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('shipments')->cascadeOnDelete();
            $table->foreignId('shipment_leg_id')->nullable()->constrained('shipment_legs')->nullOnDelete();
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type');
            $table->string('severity')->default('medium');
            $table->string('status')->default('open');
            $table->string('location')->nullable();
            $table->text('description');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['shipment_id', 'status']);
            $table->index(['type', 'status']);
        });

        Schema::create('shipment_manifests', function (Blueprint $table) {
            $table->id();
            $table->string('manifest_number')->unique();
            $table->foreignId('origin_branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('destination_branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('dispatched');
            $table->timestamp('departed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('shipment_manifest_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_manifest_id')->constrained('shipment_manifests')->cascadeOnDelete();
            $table->foreignId('shipment_id')->constrained('shipments')->cascadeOnDelete();
            $table->foreignId('shipment_leg_id')->nullable()->constrained('shipment_legs')->nullOnDelete();
            $table->string('status')->default('loaded');
            $table->timestamps();

            $table->unique(['shipment_manifest_id', 'shipment_id']);
            $table->index(['shipment_id', 'status']);
        });

        Schema::create('shipment_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('shipments')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event');
            $table->string('channel')->default('system');
            $table->string('title');
            $table->text('message');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['shipment_id', 'event']);
            $table->index(['user_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_notifications');
        Schema::dropIfExists('shipment_manifest_items');
        Schema::dropIfExists('shipment_manifests');
        Schema::dropIfExists('shipment_exceptions');

        Schema::table('shipment_legs', function (Blueprint $table) {
            $table->dropColumn(['planned_departure_at', 'planned_arrival_at', 'delay_reason']);
        });

        DB::statement("UPDATE shipments SET status = 'cancelled' WHERE status IN ('delivery_failed','rescheduled','returned_to_hub','held','damaged','lost','exception')");
        DB::statement("UPDATE shipment_trackings SET status = 'cancelled' WHERE status IN ('delivery_failed','rescheduled','returned_to_hub','held','damaged','lost','exception')");
        DB::statement("ALTER TABLE shipments MODIFY COLUMN status ENUM('pending','picked_up','in_transit','arrived_at_branch','out_for_delivery','delivered','cancelled') NOT NULL DEFAULT 'pending'");
        DB::statement("ALTER TABLE shipment_trackings MODIFY COLUMN status ENUM('pending','picked_up','in_transit','arrived_at_branch','out_for_delivery','delivered','cancelled') NOT NULL");
    }
};
