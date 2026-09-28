<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_store_permissions', function (Blueprint $table) {
            if (!Schema::hasColumn('user_store_permissions', 'offline_access')) {
                $table->integer('offline_access')->nullable()->default(0)->after('sell_return');
            }
            if (!Schema::hasColumn('user_store_permissions', 'offline_sync_manage')) {
                $table->integer('offline_sync_manage')->nullable()->default(0)->after('offline_access');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_store_permissions', function (Blueprint $table) {
            if (Schema::hasColumn('user_store_permissions', 'offline_access')) {
                $table->dropColumn('offline_access');
            }
            if (Schema::hasColumn('user_store_permissions', 'offline_sync_manage')) {
                $table->dropColumn('offline_sync_manage');
            }
        });
    }
};
