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
        if (!Schema::hasTable('myauto_settings')) {
            return;
        }

        Schema::table('myauto_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('myauto_settings', 'passcode')) {
                $table->string('passcode')->nullable()->after('is_locked');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('myauto_settings') || !Schema::hasColumn('myauto_settings', 'passcode')) {
            return;
        }

        Schema::table('myauto_settings', function (Blueprint $table) {
            $table->dropColumn('passcode');
        });
    }
};
