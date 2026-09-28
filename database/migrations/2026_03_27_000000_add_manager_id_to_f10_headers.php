<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('mpcs_f10_headers') && !Schema::hasColumn('mpcs_f10_headers', 'manager_id')) {
            Schema::table('mpcs_f10_headers', function (Blueprint $table) {
                $table->integer('manager_id')->nullable()->after('location_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('mpcs_f10_headers')) {
            Schema::table('mpcs_f10_headers', function (Blueprint $table) {
                $table->dropColumn('manager_id');
            });
        }
    }
};
