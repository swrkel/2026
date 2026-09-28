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
        if (!Schema::hasTable('agents')) {
            return;
        }

        if (!Schema::hasColumn('agents', 'agent_code')) {
            Schema::table('agents', function (Blueprint $table) {
                $table->string('agent_code')->nullable()->after('date');
            });
        }

        if (!Schema::hasColumn('agents', 'country_id')) {
            Schema::table('agents', function (Blueprint $table) {
                $table->integer('country_id')->nullable()->after('address');
            });
        }

        if (!Schema::hasColumn('agents', 'district_id')) {
            Schema::table('agents', function (Blueprint $table) {
                $table->unsignedInteger('district_id')->nullable()->after('country_id');
            });
        }

        if (!Schema::hasColumn('agents', 'city')) {
            Schema::table('agents', function (Blueprint $table) {
                $table->string('city')->nullable()->after('district_id');
            });
        }

        if (!Schema::hasColumn('agents', 'mobile_no_2')) {
            Schema::table('agents', function (Blueprint $table) {
                $table->string('mobile_no_2')->nullable()->after('mobile_number');
            });
        }

        if (!Schema::hasColumn('agents', 'mobile_no_3')) {
            Schema::table('agents', function (Blueprint $table) {
                $table->string('mobile_no_3')->nullable()->after('mobile_no_2');
            });
        }

        if (!Schema::hasColumn('agents', 'added_by')) {
            Schema::table('agents', function (Blueprint $table) {
                $table->unsignedInteger('added_by')->nullable()->default(0)->after('branch');
            });
        }

        Schema::table('agents', function (Blueprint $table) {
            // Keep migration additive and safe: unique validation is handled in app layer to avoid failing on legacy data.
            $table->index('country_id', 'agents_country_id_idx');
            $table->index('district_id', 'agents_district_id_idx');
            $table->index('added_by', 'agents_added_by_idx');
            $table->index('city', 'agents_city_idx');
            $table->index('referral_code', 'agents_referral_code_idx');
            $table->unique('agent_code', 'agents_agent_code_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (!Schema::hasTable('agents')) {
            return;
        }

        Schema::table('agents', function (Blueprint $table) {
            $table->dropIndex('agents_country_id_idx');
            $table->dropIndex('agents_district_id_idx');
            $table->dropIndex('agents_added_by_idx');
            $table->dropIndex('agents_city_idx');
            $table->dropIndex('agents_referral_code_idx');
            $table->dropUnique('agents_agent_code_unique');
        });

        if (Schema::hasColumn('agents', 'added_by')) {
            Schema::table('agents', function (Blueprint $table) {
                $table->dropColumn('added_by');
            });
        }

        if (Schema::hasColumn('agents', 'mobile_no_3')) {
            Schema::table('agents', function (Blueprint $table) {
                $table->dropColumn('mobile_no_3');
            });
        }

        if (Schema::hasColumn('agents', 'mobile_no_2')) {
            Schema::table('agents', function (Blueprint $table) {
                $table->dropColumn('mobile_no_2');
            });
        }

        if (Schema::hasColumn('agents', 'city')) {
            Schema::table('agents', function (Blueprint $table) {
                $table->dropColumn('city');
            });
        }

        if (Schema::hasColumn('agents', 'district_id')) {
            Schema::table('agents', function (Blueprint $table) {
                $table->dropColumn('district_id');
            });
        }

        if (Schema::hasColumn('agents', 'country_id')) {
            Schema::table('agents', function (Blueprint $table) {
                $table->dropColumn('country_id');
            });
        }

        if (Schema::hasColumn('agents', 'agent_code')) {
            Schema::table('agents', function (Blueprint $table) {
                $table->dropColumn('agent_code');
            });
        }
    }
};

