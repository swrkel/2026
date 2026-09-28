<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('distribution_free_issues', function (Blueprint $table) {
            if (!Schema::hasColumn('distribution_free_issues', 'is_free')) {
                $table->tinyInteger('is_free')->default(0)->after('free_qty');
            }
            if (!Schema::hasColumn('distribution_free_issues', 'is_free_bottles')) {
                $table->tinyInteger('is_free_bottles')->default(0)->after('is_free');
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
        Schema::table('distribution_free_issues', function (Blueprint $table) {
            $table->dropColumn(['is_free', 'is_free_bottles']);
        });
    }
};
