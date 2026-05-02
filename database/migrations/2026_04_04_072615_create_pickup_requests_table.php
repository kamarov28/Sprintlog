<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pickup_requests', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->text('pickup_address');
            $table->date('pickup_date');
            $table->enum('status', ['pending', 'assigned', 'picked_up', 'cancelled'])->default('pending');
            $table->foreignId('courier_id')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pickup_requests');
    }
};
