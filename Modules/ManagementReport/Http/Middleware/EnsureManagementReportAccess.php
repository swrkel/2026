<?php
namespace Modules\ManagementReport\Http\Middleware;
use Modules\ManagementReport\Support\TenantConnection;

use Closure;

class EnsureManagementReportAccess
{
    public function handle($request, Closure $next)
    {
        $businessId = (int) session('user.business_id');
        if (!$businessId) {
            abort(403, 'Business context is required.');
        }

        if (!$this->isModuleEnabled($businessId)) {
            abort(403, 'Management Report module is disabled for this business.');
        }

        return $next($request);
    }

    protected function isModuleEnabled($businessId)
    {
        $package = $this->packageDetails($businessId);
        $keys = config('managementreport.module_status_keys', []);
        $found = false;

        foreach ($keys as $key) {
            if (!array_key_exists($key, $package)) {
                continue;
            }
            $found = true;
            if (in_array($package[$key], [1, '1', true, 'true', 'yes', 'on'], true)) {
                return true;
            }
        }

        if ($found) {
            return false;
        }

        // Newly installed modules are enabled by default until Manage Side Bar saves an explicit value.
        return $this->installedAndEnabled();
    }

    protected function packageDetails($businessId)
    {
        if (TenantConnection::schema()->hasTable('subscriptions')) {
            $query = TenantConnection::db()->table('subscriptions')->where('business_id', $businessId);
            if (TenantConnection::schema()->hasColumn('subscriptions', 'status')) {
                $query->whereIn('status', ['approved', 'active']);
            }
            $subscription = $query->orderByDesc('id')->first();
            if ($subscription && isset($subscription->package_details)) {
                return json_decode($subscription->package_details, true) ?: [];
            }
        }

        if (TenantConnection::schema()->hasTable('business')) {
            $business = TenantConnection::db()->table('business')->where('id', $businessId)->first();
            foreach (['pacakge_details', 'package_details'] as $column) {
                if ($business && isset($business->{$column})) {
                    return json_decode($business->{$column}, true) ?: [];
                }
            }
        }

        return [];
    }

    protected function installedAndEnabled()
    {
        try {
            $path = base_path('modules_statuses.json');
            if (!is_file($path)) {
                return true;
            }
            $statuses = json_decode(file_get_contents($path), true) ?: [];
            return !array_key_exists('ManagementReport', $statuses) || !empty($statuses['ManagementReport']);
        } catch (\Throwable $e) {
            return true;
        }
    }
}
