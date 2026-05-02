<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('pickup_requests', 'branch_id')) {
            Schema::table('pickup_requests', function (Blueprint $table) {
                $table->foreignId('branch_id')->nullable()->after('courier_id');
            });

            DB::statement('
                UPDATE pickup_requests pr
                LEFT JOIN users u ON u.id = pr.user_id
                SET pr.branch_id = u.branch_id
                WHERE pr.branch_id IS NULL
            ');

            Schema::table('pickup_requests', function (Blueprint $table) {
                $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('pickup_requests', 'branch_id')) {
            Schema::table('pickup_requests', function (Blueprint $table) {
                $table->dropForeign(['branch_id']);
                $table->dropColumn('branch_id');
            });
        }
    }
};
