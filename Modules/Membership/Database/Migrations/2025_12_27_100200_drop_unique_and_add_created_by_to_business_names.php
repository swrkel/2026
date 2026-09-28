<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DropUniqueAndAddCreatedByToBusinessNames extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Drop unique constraint on business_id if it exists
        try {
            DB::statement('ALTER TABLE membership_business_names DROP INDEX membership_business_names_business_id_unique');
        } catch (\Exception $e) {
            // Constraint might not exist, that's okay
        }

        // Add created_by column if it doesn't exist
        Schema::table('membership_business_names', function (Blueprint $table) {
            if (!Schema::hasColumn('membership_business_names', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('business_name');
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
        Schema::table('membership_business_names', function (Blueprint $table) {
            if (Schema::hasColumn('membership_business_names', 'created_by')) {
                $table->dropColumn('created_by');
            }
        });
    }
}
