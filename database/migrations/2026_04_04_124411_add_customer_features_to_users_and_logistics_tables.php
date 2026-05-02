<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Update Users table (Handle Enum for Customer role and add profile fields)
        // Note: MySQL/MariaDB doesn't support easy enum modification. We'll use raw SQL or change the column type.
        // For simplicity in this env, we'll re-define the column.
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->after('email');
            $table->text('address')->nullable()->after('phone');
            $table->string('city')->nullable()->after('address');
            $table->string('photo')->nullable()->after('city');
        });

        // Add 'customer' to enum (Specific for MySQL)
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'cashier', 'courier', 'manager', 'customer') DEFAULT 'customer'");

        // 2. Add user_id to pickups
        Schema::table('pickup_requests', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->onDelete('set null');
        });

        // 3. Add user_id to shipments
        Schema::table('shipments', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });

        Schema::table('pickup_requests', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'address', 'city', 'photo']);
        });

        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'cashier', 'courier', 'manager') DEFAULT 'admin'");
    }
};
