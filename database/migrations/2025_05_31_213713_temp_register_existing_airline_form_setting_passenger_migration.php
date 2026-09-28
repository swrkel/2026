<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
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
        $legacy_migration = '2025_05_31_213714_create_airline_form_setting_passenger_table';

        // Temporary bridge:
        // If the table already exists but the migration row is missing,
        // register it to prevent duplicate-create failure.
        if (Schema::hasTable('airline_form_setting_passenger')) {
            $already_registered = DB::table('migrations')
                ->where('migration', $legacy_migration)
                ->exists();

            if (!$already_registered) {
                $current_batch = (int) DB::table('migrations')->max('batch');
                DB::table('migrations')->insert([
                    'migration' => $legacy_migration,
                    'batch' => max(1, $current_batch),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('migrations')
            ->where('migration', '2025_05_31_213714_create_airline_form_setting_passenger_table')
            ->delete();
    }
};

