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
        Schema::table('petro_daily_shifts', function (Blueprint $table) {
            if (!Schema::hasColumn('petro_daily_shifts', 'type')) {
                $table->string('type')->default('daily_shift');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('petro_daily_shifts', function (Blueprint $table) {
            if (Schema::hasColumn('petro_daily_shifts', 'type')) {
                $table->dropColumn('type');
            }
        });
    }
};
