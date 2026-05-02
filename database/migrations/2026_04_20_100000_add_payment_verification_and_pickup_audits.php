<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE payments MODIFY COLUMN payment_status ENUM('pending','pending_verification','paid','failed') NOT NULL DEFAULT 'pending'");
        DB::statement("ALTER TABLE shipment_trackings MODIFY COLUMN status ENUM('pending','picked_up','in_transit','arrived_at_branch','out_for_delivery','delivered','cancelled') NOT NULL");

        Schema::create('pickup_status_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pickup_request_id')->constrained('pickup_requests')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event');
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->string('from_payment_status')->nullable();
            $table->string('to_payment_status')->nullable();
            $table->text('description')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pickup_status_audits');

        DB::statement("ALTER TABLE shipment_trackings MODIFY COLUMN status ENUM('picked_up','in_transit','arrived_at_branch','out_for_delivery','delivered') NOT NULL");
        DB::statement("ALTER TABLE payments MODIFY COLUMN payment_status ENUM('pending','paid','failed') NOT NULL DEFAULT 'pending'");
    }
};
