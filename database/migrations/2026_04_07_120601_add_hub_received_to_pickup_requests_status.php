<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE pickup_requests MODIFY COLUMN status ENUM('pending','assigned','picked_up','hub_received','cancelled') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE pickup_requests MODIFY COLUMN status ENUM('pending','assigned','picked_up','cancelled') NOT NULL DEFAULT 'pending'");
    }
};
