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
            $table->string('last_status_from', 20)->nullable()->after('status');
            $table->string('last_status_to', 20)->nullable()->after('last_status_from');
            $table->timestamp('status_changed_at')->nullable()->after('last_status_to');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('distribution_route_user_maps', function (Blueprint $table) {
            $table->dropColumn(['last_status_from', 'last_status_to', 'status_changed_at']);
        });
    }
};
