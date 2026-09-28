<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Customer registration fields: date of birth (year optional), living district
 * and town.
 *
 *
 * WHY THE DATE OF BIRTH IS THREE INTEGERS AND NOT A DATE
 *
 * Most customers will not give their birth YEAR, but are willing to give the day
 * and month - which is all a birthday greeting needs.
 *
 * A `date` column cannot express that. It always needs a year, so the year gets
 * filled with 1900 or the current year, and every later age calculation, sort
 * and report silently works from a value nobody entered. Three integers say
 * exactly what is known and nothing more: a null `dob_year` means "not given",
 * which is a fact, not a placeholder.
 *
 * The year is KEPT where it is already known - some existing records hold a full
 * date of birth, and discarding data the business already has would be wrong.
 * So new registrations fill day and month only, and older records keep all
 * three.
 *
 * 29 February is allowed and stored faithfully as day 29, month 2. Rejecting it
 * would mean telling roughly one customer in 1,500 that their birthday is not a
 * valid date. Greetings in non-leap years should fall back to 28 February so the
 * greeting still lands in the birth month - that belongs in the greeting code,
 * not here.
 *
 *
 * WHY `contacts`
 *
 * POSSalesWorkspaceService says it plainly: "Customer master comes from the
 * standalone Customers module. POS no longer uses or duplicates pos_customers
 * for customer selection." The master record is `contacts`, so the columns
 * belong there and both modules read the same data.
 *
 * Every column is added only if missing, so this is safe to run more than once
 * and safe on a tenant that already has some of them.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('contacts')) {
            return;
        }

        Schema::table('contacts', function (Blueprint $table) {
            // 1-31. Null when not given.
            if (! Schema::hasColumn('contacts', 'dob_day')) {
                $table->unsignedTinyInteger('dob_day')->nullable()->after('email');
            }

            // 1-12. Null when not given.
            if (! Schema::hasColumn('contacts', 'dob_month')) {
                $table->unsignedTinyInteger('dob_month')->nullable()->after('dob_day');
            }

            /*
             * Deliberately nullable, and deliberately separate.
             *
             * Null means the customer did not give a year - NOT that it is
             * unknown-but-required. Any age calculation must treat null as "no
             * age available" rather than defaulting it.
             */
            if (! Schema::hasColumn('contacts', 'dob_year')) {
                $table->unsignedSmallInteger('dob_year')->nullable()->after('dob_month');
            }

            /*
             * The table already has `city`, but no district. In Sri Lanka the
             * district is the larger administrative area and the town sits
             * inside it, so they are not interchangeable and city cannot serve
             * as both.
             */
            if (! Schema::hasColumn('contacts', 'living_district')) {
                $table->string('living_district', 100)->nullable()->after('city');
            }

            if (! Schema::hasColumn('contacts', 'town')) {
                $table->string('town', 150)->nullable()->after('living_district');
            }
        });

        /*
         * Indexed because the point of collecting day and month is to find
         * everyone with a birthday today - a query that runs on a schedule
         * against the whole customer base.
         */
        /*
         * Checked with SHOW INDEX rather than the Doctrine schema manager -
         * that requires doctrine/dbal, which is not guaranteed to be installed,
         * and a migration must not fail over a missing dev dependency.
         */
        $indexExists = collect(\Illuminate\Support\Facades\DB::select(
            "SHOW INDEX FROM `contacts` WHERE Key_name = 'contacts_dob_month_day_index'"
        ))->isNotEmpty();

        if (! $indexExists) {
            Schema::table('contacts', function (Blueprint $table) {
                $table->index(['dob_month', 'dob_day'], 'contacts_dob_month_day_index');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('contacts')) {
            return;
        }

        Schema::table('contacts', function (Blueprint $table) {
            foreach (['dob_day', 'dob_month', 'dob_year', 'living_district', 'town'] as $column) {
                if (Schema::hasColumn('contacts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
