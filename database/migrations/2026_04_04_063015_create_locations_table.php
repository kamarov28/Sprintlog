<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['provinsi', 'kota']);
            $table->string('name');
            $table->unsignedBigInteger('parent_id')->nullable(); // null for provinsi, provinsi_id for kota
            $table->unsignedTinyInteger('zone'); // 1-6 (shipping zone)
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('locations')->onDelete('cascade');
            $table->index('parent_id');
            $table->index('zone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
