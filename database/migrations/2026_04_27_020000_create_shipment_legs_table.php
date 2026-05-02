<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_legs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('shipments')->cascadeOnDelete();
            $table->unsignedSmallInteger('sequence');
            $table->foreignId('origin_branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('destination_branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->foreignId('handler_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('departed_at')->nullable();
            $table->timestamp('arrived_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['shipment_id', 'sequence']);
            $table->index(['shipment_id', 'status']);
            $table->index(['origin_branch_id', 'status']);
            $table->index(['destination_branch_id', 'status']);
        });

        DB::table('shipments')
            ->select(['id', 'origin_branch_id', 'destination_branch_id', 'status', 'created_at', 'updated_at'])
            ->orderBy('id')
            ->chunkById(100, function ($shipments): void {
                foreach ($shipments as $shipment) {
                    DB::table('shipment_legs')->insert([
                        'shipment_id' => $shipment->id,
                        'sequence' => 1,
                        'origin_branch_id' => $shipment->origin_branch_id,
                        'destination_branch_id' => $shipment->destination_branch_id,
                        'status' => match ($shipment->status) {
                            'delivered', 'arrived_at_branch', 'out_for_delivery' => 'arrived',
                            'in_transit' => 'departed',
                            default => 'pending',
                        },
                        'departed_at' => in_array($shipment->status, ['in_transit', 'arrived_at_branch', 'out_for_delivery', 'delivered'], true) ? $shipment->updated_at : null,
                        'arrived_at' => in_array($shipment->status, ['arrived_at_branch', 'out_for_delivery', 'delivered'], true) ? $shipment->updated_at : null,
                        'created_at' => $shipment->created_at,
                        'updated_at' => $shipment->updated_at,
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_legs');
    }
};
