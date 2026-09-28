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
        Schema::table('distribution_route_user_maps', function (Blueprint $table) {
            if (! Schema::hasColumn('distribution_route_user_maps', 'status_changed_by')) {
                $table->unsignedBigInteger('status_changed_by')->nullable()->after('status_changed_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('distribution_route_user_maps', function (Blueprint $table) {
            if (Schema::hasColumn('distribution_route_user_maps', 'status_changed_by')) {
                $table->dropColumn('status_changed_by');
            }
        });
    }
};
