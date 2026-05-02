<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pickup_requests', function (Blueprint $table) {
            $table->decimal('receiver_latitude', 10, 8)->nullable()->after('receiver_address');
            $table->decimal('receiver_longitude', 11, 8)->nullable()->after('receiver_latitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pickup_requests', function (Blueprint $table) {
            $table->dropColumn(['receiver_latitude', 'receiver_longitude']);
        });
    }
};
