<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUnitIdToDistributionFreeIssuesTable extends Migration
{
    public function up()
    {
        // Check if column already exists
        if (!Schema::hasColumn('distribution_free_issues', 'unit_id')) {
            Schema::table('distribution_free_issues', function (Blueprint $table) {
                $table->unsignedInteger('unit_id')->nullable()->after('product_subcategory');
                // Using unsignedInteger to match units.id (int(10) unsigned)
            });
        }
        
        // Add foreign key constraint if it doesn't exist
        try {
            Schema::table('distribution_free_issues', function (Blueprint $table) {
                $table->foreign('unit_id')
                    ->references('id')
                    ->on('units')
                    ->onDelete('set null');
            });
        } catch (\Exception $e) {
            // Foreign key might already exist or there's an issue
        }
    }

    public function down()
    {
        Schema::table('distribution_free_issues', function (Blueprint $table) {
            // Drop foreign key if it exists
            try {
                $table->dropForeign(['unit_id']);
            } catch (\Exception $e) {
                // Ignore if foreign key doesn't exist
            }
            
            // Drop column if it exists
            if (Schema::hasColumn('distribution_free_issues', 'unit_id')) {
                $table->dropColumn('unit_id');
            }
        });
    }
}