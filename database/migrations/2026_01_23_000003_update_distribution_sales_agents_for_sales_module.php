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
        Schema::table('distribution_sales_agents', function (Blueprint $table) {
            if (!Schema::hasColumn('distribution_sales_agents', 'joined_date')) {
                $table->date('joined_date')->nullable()->after('address');
            }
            if (!Schema::hasColumn('distribution_sales_agents', 'employment_grade')) {
                $table->string('employment_grade')->nullable()->after('joined_date');
            }
            if (!Schema::hasColumn('distribution_sales_agents', 'salary')) {
                $table->decimal('salary', 22, 4)->default(0)->after('employment_grade');
            }
            if (!Schema::hasColumn('distribution_sales_agents', 'commission')) {
                $table->decimal('commission', 22, 4)->default(0)->after('salary');
            }
            if (!Schema::hasColumn('distribution_sales_agents', 'location_id')) {
                $table->unsignedInteger('location_id')->nullable()->after('commission');
                $table->index('location_id');
            }
            if (!Schema::hasColumn('distribution_sales_agents', 'user_id')) {
                $table->unsignedInteger('user_id')->nullable()->after('location_id');
                $table->index('user_id');
            }
            if (!Schema::hasColumn('distribution_sales_agents', 'commission_history')) {
                $table->json('commission_history')->nullable()->after('user_id');
            }
            if (!Schema::hasColumn('distribution_sales_agents', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('distribution_sales_agents', function (Blueprint $table) {
            if (Schema::hasColumn('distribution_sales_agents', 'commission_history')) {
                $table->dropColumn('commission_history');
            }
            if (Schema::hasColumn('distribution_sales_agents', 'user_id')) {
                $table->dropIndex(['user_id']);
                $table->dropColumn('user_id');
            }
            if (Schema::hasColumn('distribution_sales_agents', 'location_id')) {
                $table->dropIndex(['location_id']);
                $table->dropColumn('location_id');
            }
            if (Schema::hasColumn('distribution_sales_agents', 'commission')) {
                $table->dropColumn('commission');
            }
            if (Schema::hasColumn('distribution_sales_agents', 'salary')) {
                $table->dropColumn('salary');
            }
            if (Schema::hasColumn('distribution_sales_agents', 'employment_grade')) {
                $table->dropColumn('employment_grade');
            }
            if (Schema::hasColumn('distribution_sales_agents', 'joined_date')) {
                $table->dropColumn('joined_date');
            }
            if (Schema::hasColumn('distribution_sales_agents', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
