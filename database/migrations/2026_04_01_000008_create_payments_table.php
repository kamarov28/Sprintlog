<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('shipments')->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->enum('payment_method', ['cash', 'transfer', 'e-wallet']);
            $table->enum('payment_status', ['pending', 'paid', 'failed'])->default('pending');
            $table->date('payment_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
