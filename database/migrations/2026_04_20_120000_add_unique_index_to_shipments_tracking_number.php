<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function ($table) {
            $table->unique('tracking_number', 'shipments_tracking_number_unique');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function ($table) {
            $table->dropUnique('shipments_tracking_number_unique');
        });
    }
};
