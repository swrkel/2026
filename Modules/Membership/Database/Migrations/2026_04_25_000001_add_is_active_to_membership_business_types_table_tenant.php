<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsActiveToMembershipBusinessTypesTableTenant extends Migration
{
    public function up()
    {
        if (Schema::hasTable('membership_business_types') && ! Schema::hasColumn('membership_business_types', 'is_active')) {
            Schema::table('membership_business_types', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('created_by');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('membership_business_types') && Schema::hasColumn('membership_business_types', 'is_active')) {
            Schema::table('membership_business_types', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }
    }
}
