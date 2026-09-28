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
        Schema::table('distribution_free_issues', function (Blueprint $table) {
            if (!Schema::hasColumn('distribution_free_issues', 'free_products')) {
                $table->text('free_products')->nullable()->after('product_subcategory');
            }
            if (!Schema::hasColumn('distribution_free_issues', 'status')) {
                $table->tinyInteger('status')->default(1)->after('free_qty');
            }
            if (!Schema::hasColumn('distribution_free_issues', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('status');
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
            $table->dropColumn(['free_products', 'status', 'created_by']);
        });
    }
};
