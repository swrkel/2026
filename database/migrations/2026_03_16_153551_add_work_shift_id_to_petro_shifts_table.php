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
        Schema::table('petro_shifts', function (Blueprint $table) {
            $table->integer('work_shift_id')->nullable()->after('shift_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('petro_shifts', function (Blueprint $table) {
            $table->dropColumn('work_shift_id');
        });
    }
};
