<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pickup_requests', function (Blueprint $table) {
            // Add user_id if not exists (already handled in previous migrations but for safety)
            if (! Schema::hasColumn('pickup_requests', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('id')->constrained('users');
            }

            // Expanded Sender Info
            $table->string('sender_name')->nullable()->after('user_id');
            $table->string('sender_phone')->nullable()->after('sender_name');
            $table->text('sender_address')->nullable()->after('sender_phone');
            $table->decimal('sender_latitude', 10, 8)->nullable()->after('sender_address');
            $table->decimal('sender_longitude', 11, 8)->nullable()->after('sender_latitude');

            // Receiver Info (Simplified per user request)
            $table->string('receiver_name')->nullable()->after('sender_longitude');
            $table->string('receiver_phone')->nullable()->after('receiver_name');
            $table->text('receiver_address')->nullable()->after('receiver_phone');
            $table->foreignId('receiver_city_id')->nullable()->after('receiver_address')->constrained('locations');

            // Cargo & Payment Info
            $table->decimal('weight', 8, 2)->nullable()->after('receiver_city_id');
            $table->string('service_type')->nullable()->after('weight'); // BEST, REGULAR, GOKIL
            $table->decimal('total_price', 12, 2)->nullable()->after('service_type');
            $table->string('payment_proof')->nullable()->after('total_price');

            // Adjust Status Enum (Laravel way to modify enum is sometimes tricky with SQLite, but we use MySQL)
            // For now we assume we can just use the status string or modify it.
        });
    }

    public function down(): void
    {
        Schema::table('pickup_requests', function (Blueprint $table) {
            $table->dropForeign(['receiver_city_id']);
            $table->dropColumn([
                'sender_name', 'sender_phone', 'sender_address', 'sender_latitude', 'sender_longitude',
                'receiver_name', 'receiver_phone', 'receiver_address', 'receiver_city_id',
                'weight', 'service_type', 'total_price', 'payment_proof',
            ]);
        });
    }
};
