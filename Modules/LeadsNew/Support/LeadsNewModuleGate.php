<?php

namespace Modules\LeadsNew\Support;

class LeadsNewModuleGate
{
    public static function enabledForBusiness($business = null): bool
    {
        if (auth()->check() && method_exists(auth()->user(), 'can') && auth()->user()->can('superadmin')) {
            return true;
        }

        $business = $business ?: (session('business') ?? null);
        $details = [];

        if (is_object($business) && isset($business->package_details)) {
            $details = is_string($business->package_details)
                ? json_decode($business->package_details, true)
                : (array) $business->package_details;
        }

        if (empty($details) && function_exists('session')) {
            $details = session('package_details', []);
        }

        return !empty($details['leads_new_module']) || !empty($details['enable_leads_new']);
    }

    public static function can(string $permission): bool
    {
        return auth()->check() && (auth()->user()->can($permission) || auth()->user()->can('superadmin'));
    }
}
