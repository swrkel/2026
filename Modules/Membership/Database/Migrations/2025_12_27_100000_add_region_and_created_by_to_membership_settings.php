<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRegionAndCreatedByToMembershipSettings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('membership_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('membership_settings', 'region')) {
                $table->string('region')->default('Default')->after('business_id');
            }
            if (!Schema::hasColumn('membership_settings', 'next_sequence')) {
                $table->unsignedBigInteger('next_sequence')->nullable()->after('starting_number');
            }
            if (!Schema::hasColumn('membership_settings', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('next_sequence');
            }
        });

        // Initialize next_sequence with starting_number for existing records
        DB::table('membership_settings')->whereNull('next_sequence')->update([
            'next_sequence' => DB::raw('starting_number')
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('membership_settings', function (Blueprint $table) {
            $table->dropColumn(['region', 'next_sequence', 'created_by']);
        });
    }
}
