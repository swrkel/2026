<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Manage Side Bar fix - step 2 (data backfill).
 *
 * Before the fix, ModulePermissionService::enforceHierarchy() built its module
 * catalogue from the ENABLED keys only. A module that had been unchecked lives
 * in `business.enabled_modules` purely as a `__sidebar_disabled__:<key>` marker,
 * so it fell out of that catalogue and `subscriptions.package_details.<key>` was
 * never written back as 0. The subscription package therefore still reported the
 * module as permitted and it kept rendering in the Main System Sidebar.
 *
 * The code fix stops this happening again, but businesses that were already
 * saved keep the stale `1` in package_details until something re-saves them.
 * This migration walks every business that has at least one disabled marker and
 * re-runs the corrected hierarchy pass, which writes the missing zeros.
 *
 * It is idempotent - running it twice produces the same result - and it only
 * touches parent module flags. Child page/tab permissions are left untouched.
 */
class BackfillDisabledSidebarModules extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('business')
            || ! Schema::hasTable('subscriptions')
            || ! Schema::hasColumn('business', 'enabled_modules')) {
            return;
        }

        $service = app(\Modules\Superadmin\Services\ModulePermissionService::class);

        DB::table('business')
            ->select('id')
            ->where('enabled_modules', 'like', '%__sidebar_disabled__:%')
            ->orderBy('id')
            ->chunk(100, function ($rows) use ($service): void {
                foreach ($rows as $row) {
                    try {
                        $business = \App\Business::find($row->id);
                        if ($business === null) {
                            continue;
                        }

                        // Rewrites only the stable parent module flags in
                        // package_details for every subscription of this
                        // business, using the corrected catalogue.
                        $service->syncSubscriptionsFromBusiness($business);

                        if (class_exists(\App\Utils\SidebarPermissionUtil::class)
                            && method_exists(\App\Utils\SidebarPermissionUtil::class, 'forgetBusinessCache')) {
                            \App\Utils\SidebarPermissionUtil::forgetBusinessCache((int) $business->id);
                        }
                    } catch (\Throwable $e) {
                        Log::warning('Manage Side Bar backfill skipped one business.', [
                            'business_id' => $row->id ?? null,
                            'message' => $e->getMessage(),
                        ]);
                    }
                }
            });
    }

    public function down(): void
    {
        // Nothing to reverse. The backfill only re-derives package_details
        // parent flags from the Manage Side Bar selections that already exist.
    }
}
