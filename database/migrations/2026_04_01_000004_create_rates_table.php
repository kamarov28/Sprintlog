<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rates', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('origin_zone'); // 1-6
            $table->unsignedTinyInteger('destination_zone'); // 1-6
            $table->decimal('price_per_kg', 15, 2);
            $table->integer('estimated_days');
            $table->timestamps();
            $table->unique(['origin_zone', 'destination_zone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rates');
    }
};
