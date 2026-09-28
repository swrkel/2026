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
        Schema::table('default_accounts', function (Blueprint $table) {
            if (! Schema::hasColumn('default_accounts', 'is_main_account')) {
                $table->boolean('is_main_account')->default(false)->after('is_closed');
            }

            if (! Schema::hasColumn('default_accounts', 'parent_account_id')) {
                $table->unsignedInteger('parent_account_id')->nullable()->after('is_main_account');
            }

            if (! Schema::hasColumn('default_accounts', 'visible')) {
                $table->boolean('visible')->default(true)->after('parent_account_id');
            }

            if (! Schema::hasColumn('default_accounts', 'show_in_balance_sheet')) {
                $table->boolean('show_in_balance_sheet')->nullable()->default(true)->after('visible');
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
        Schema::table('default_accounts', function (Blueprint $table) {
            $dropColumns = [];

            foreach (['is_main_account', 'parent_account_id', 'visible', 'show_in_balance_sheet'] as $column) {
                if (Schema::hasColumn('default_accounts', $column)) {
                    $dropColumns[] = $column;
                }
            }

            if (! empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
