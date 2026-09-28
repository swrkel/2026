<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMembershipSettingIdAndRegionToMembershipMembers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('membership_members', function (Blueprint $table) {
            if (!Schema::hasColumn('membership_members', 'membership_setting_id')) {
                $table->unsignedBigInteger('membership_setting_id')->nullable()->after('business_id');
            }
            if (!Schema::hasColumn('membership_members', 'region')) {
                $table->string('region')->nullable()->after('membership_setting_id');
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
        Schema::table('membership_members', function (Blueprint $table) {
            $table->dropColumn(['membership_setting_id', 'region']);
        });
    }
}
