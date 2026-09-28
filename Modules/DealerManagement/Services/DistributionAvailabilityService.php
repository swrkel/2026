<?php
namespace Modules\DealerManagement\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DistributionAvailabilityService
{
    /** Per PHP request cache: sidebar + controller must not repeat subscription checks. */
    private static array $businessAvailabilityCache = [];

    /**
     * Dealer Management is available only for businesses where the host ERP
     * has Distribution enabled.
     *
     * Priority:
     *  1. Host ERP subscription/module flag: distribution_module.
     *  2. Distribution New business-specific UI status row.
     *  3. Distribution New global UI status row (legacy compatibility).
     *  4. Presence of Distribution New tables (legacy compatibility only).
     */
    public function enabledForBusiness(?int $businessId): bool
    {
        $businessId = (int) $businessId;
        if ($businessId <= 0) return false;
        if (array_key_exists($businessId, self::$businessAvailabilityCache)) {
            return self::$businessAvailabilityCache[$businessId];
        }

        $enabled = null;

        // Fastest/canonical host ERP check first.
        try {
            if (class_exists(\App\Utils\ModuleUtil::class)
                && method_exists(\App\Utils\ModuleUtil::class, 'hasThePermissionInSubscription')) {
                $enabled = (bool) \App\Utils\ModuleUtil::hasThePermissionInSubscription($businessId, 'distribution_module');
                if ($enabled) return self::$businessAvailabilityCache[$businessId] = true;
            }
        } catch (\Throwable $e) {
            $enabled = null;
        }

        // Explicit package flag. Only query when the first check did not positively enable it.
        try {
            if (class_exists(\Modules\Superadmin\Entities\Subscription::class)) {
                $subscription = \Modules\Superadmin\Entities\Subscription::current_subscription($businessId);
                if (!empty($subscription)) {
                    $details = (array) ($subscription->package_details ?? []);
                    foreach (['distribution_module','distribution_new','distributionnew_module'] as $key) {
                        if (array_key_exists($key, $details)) {
                            return self::$businessAvailabilityCache[$businessId] = !empty($details[$key]);
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // use compatibility fallbacks below
        }

        try {
            if (Schema::hasTable('disnew_module_ui_status')) {
                $base = DB::table('disnew_module_ui_status')
                    ->where('module_key', config('dealermanagement.distribution_module_key', 'distribution_new'));
                $specific = (clone $base)->where('business_id', $businessId)->first(['is_visible','is_installed']);
                if ($specific) {
                    return self::$businessAvailabilityCache[$businessId] = ((int)$specific->is_visible === 1 && (int)$specific->is_installed === 1);
                }
                $global = (clone $base)->whereNull('business_id')->first(['is_visible','is_installed']);
                if ($global && (int)$global->is_visible === 1 && (int)$global->is_installed === 1) {
                    return self::$businessAvailabilityCache[$businessId] = true;
                }
            }
        } catch (\Throwable $e) {}

        try {
            $fallback = Schema::hasTable('disnew_sales_orders') || Schema::hasTable('disnew_deliveries') || Schema::hasTable('disnew_sales_invoices');
            return self::$businessAvailabilityCache[$businessId] = $fallback;
        } catch (\Throwable $e) {
            return self::$businessAvailabilityCache[$businessId] = false;
        }
    }

    /**
     * Businesses that may use Dealer Login in the current tenant/central DB.
     */
    public function enabledBusinesses(): array
    {
        $ids = [];

        // Public dealer login has no ERP session, so inspect businesses in the
        // current tenant database and retain only Distribution-enabled ones.
        try {
            $table = Schema::hasTable('business') ? 'business' : (Schema::hasTable('businesses') ? 'businesses' : null);
            if ($table) {
                $candidateIds = DB::table($table)->pluck('id');
                foreach ($candidateIds as $id) {
                    $id = (int) $id;
                    if ($id > 0 && $this->enabledForBusiness($id)) {
                        $ids[] = $id;
                    }
                }
            }
        } catch (\Throwable $e) {
            $ids = [];
        }

        // ERP-side fallback to the current business context.
        if (empty($ids)) {
            $sessionBusiness = session('business.id')
                ?? session('business_id')
                ?? session('user.business_id');

            if ($sessionBusiness && $this->enabledForBusiness((int) $sessionBusiness)) {
                $ids[] = (int) $sessionBusiness;
            }
        }

        return array_values(array_unique(array_filter(array_map('intval', $ids))));
    }
}
