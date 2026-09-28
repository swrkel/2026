<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexesToDistributionFreeIssuesTable extends Migration
{
    public function up()
    {
        Schema::table('distribution_free_issues', function (Blueprint $table) {
            $table->index('business_id', 'idx_business_id');
            $table->index('status', 'idx_status');
            $table->index('product_name', 'idx_product_name');
            $table->index('form_no', 'idx_form_no');
            $table->index(['date_since', 'date_till'], 'idx_date_since_till');
            $table->index('created_at', 'idx_created_at');
            $table->index(['business_id', 'status'], 'idx_business_status');
            $table->index('qty_type', 'idx_qty_type');
            $table->index('unit_id', 'idx_unit_id');
        });
    }

    public function down()
    {
        Schema::table('distribution_free_issues', function (Blueprint $table) {
            $table->dropIndex('idx_business_id');
            $table->dropIndex('idx_status');
            $table->dropIndex('idx_product_name');
            $table->dropIndex('idx_form_no');
            $table->dropIndex('idx_date_since_till');
            $table->dropIndex('idx_created_at');
            $table->dropIndex('idx_business_status');
            $table->dropIndex('idx_qty_type');
            $table->dropIndex('idx_unit_id');
        });
    }
}