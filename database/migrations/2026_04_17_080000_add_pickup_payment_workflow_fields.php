<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pickup_requests', function (Blueprint $table) {
            $table->string('payment_method')->nullable()->after('service_type');
            $table->string('payment_status')->nullable()->after('payment_method');
            $table->decimal('cash_received_amount', 12, 2)->nullable()->after('payment_status');
            $table->timestamp('cash_collected_at')->nullable()->after('cash_received_amount');
            $table->foreignId('cash_collected_by')->nullable()->after('cash_collected_at')->constrained('users');
            $table->timestamp('cash_handover_at')->nullable()->after('cash_collected_by');
            $table->foreignId('cash_verified_by')->nullable()->after('cash_handover_at')->constrained('users');
        });
    }

    public function down(): void
    {
        Schema::table('pickup_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cash_collected_by');
            $table->dropConstrainedForeignId('cash_verified_by');
            $table->dropColumn([
                'payment_method',
                'payment_status',
                'cash_received_amount',
                'cash_collected_at',
                'cash_handover_at',
            ]);
        });
    }
};
