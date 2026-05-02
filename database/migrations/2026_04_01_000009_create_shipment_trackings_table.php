<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_trackings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('shipments')->onDelete('cascade');
            $table->string('location');
            $table->text('description')->nullable();
            $table->enum('status', ['picked_up', 'in_transit', 'arrived_at_branch', 'out_for_delivery', 'delivered']);
            $table->timestamp('tracked_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_trackings');
    }
};
