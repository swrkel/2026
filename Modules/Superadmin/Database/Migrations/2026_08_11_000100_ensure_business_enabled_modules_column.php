<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Manage Side Bar fix - step 1.
 *
 * The Manage Side Bar screen stores its result in `business.enabled_modules`
 * as a JSON list. Enabled modules are stored as plain keys; a module that is
 * unchecked is stored as an explicit marker:
 *
 *     ["petro", "petro_module", "__sidebar_disabled__:tables"]
 *
 * This migration only guarantees the column exists. It never changes the
 * values, so it is safe to run on an installation that already has it.
 */
class EnsureBusinessEnabledModulesColumn extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('business')) {
            return;
        }

        if (! Schema::hasColumn('business', 'enabled_modules')) {
            Schema::table('business', function (Blueprint $table): void {
                $table->text('enabled_modules')->nullable()->after('name');
            });
        }
    }

    public function down(): void
    {
        // Intentionally left empty. Dropping the column would delete every
        // Manage Side Bar selection for every business.
    }
}
