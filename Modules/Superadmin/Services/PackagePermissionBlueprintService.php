<?php

namespace Modules\Superadmin\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\Superadmin\Entities\Package;

class PackagePermissionBlueprintService
{
    public const PACKAGE_PERMISSION_KEY = 'manage_permission_blueprint';
    public const SUBSCRIPTION_ACTIVE_KEY = '_package_permission_blueprint';
    public const SUBSCRIPTION_PACKAGE_KEY = '_package_permission_blueprint_package_id';

    /**
     * Dynamic standalone module/page permission sections.
     *
     * Existing package fields continue to cover legacy/core modules. This list
     * adds every newly discovered standalone module without requiring edits to
     * the package form whenever a module is installed.
     */
    public function sections(): array
    {
        return Cache::remember('superadmin.package_permission_blueprint.sections.v1', 300, function (): array {
            return app(ModulePermissionService::class)->discoverManageSections();
        });
    }

    public function decodePackagePermissions($value): array
    {
        if ($value instanceof Package) {
            $value = $value->package_permissions;
        }

        if (is_array($value)) {
            return $value;
        }

        if (! is_scalar($value) || trim((string) $value) === '') {
            return [];
        }

        $decoded = json_decode((string) $value, true);

        return is_array($decoded) ? $decoded : [];
    }

    public function blueprintForPackage(?Package $package): array
    {
        if (! $package) {
            return [
                'enabled' => 0,
                'permissions' => [],
            ];
        }

        $packagePermissions = $this->decodePackagePermissions($package);
        $blueprint = $packagePermissions[self::PACKAGE_PERMISSION_KEY] ?? [];

        if (! is_array($blueprint)) {
            $blueprint = [];
        }

        return [
            'enabled' => ! empty($blueprint['enabled']) ? 1 : 0,
            'permissions' => $this->sanitizePermissions($blueprint['permissions'] ?? []),
        ];
    }

    public function isEnabledForPackage(?Package $package): bool
    {
        return ! empty($this->blueprintForPackage($package)['enabled']);
    }

    /**
     * Merge the compact JSON payload into the existing package_permissions JSON.
     * One hidden JSON input avoids PHP max_input_vars even with thousands of pages.
     */
    public function mergeRequest(array $packagePermissions, Request $request): array
    {
        $enabled = $request->boolean('package_permission_blueprint_enabled');
        $decoded = json_decode((string) $request->input('package_permission_blueprint_json', '{}'), true);
        $permissions = $this->sanitizePermissions(is_array($decoded) ? $decoded : []);

        $packagePermissions[self::PACKAGE_PERMISSION_KEY] = [
            'enabled' => $enabled ? 1 : 0,
            'version' => 1,
            'permissions' => $permissions,
        ];

        return $packagePermissions;
    }

    /**
     * Apply package-controlled standalone permissions to subscription details.
     * Existing legacy package settings remain untouched.
     */
    public function applyToSubscriptionDetails(array $details, ?Package $package): array
    {
        $blueprint = $this->blueprintForPackage($package);

        if (empty($blueprint['enabled'])) {
            unset($details[self::SUBSCRIPTION_ACTIVE_KEY], $details[self::SUBSCRIPTION_PACKAGE_KEY]);

            return $details;
        }

        foreach ($blueprint['permissions'] as $key => $value) {
            $details[$key] = (int) $value;
        }

        $details[self::SUBSCRIPTION_ACTIVE_KEY] = 1;
        $details[self::SUBSCRIPTION_PACKAGE_KEY] = (int) $package->id;

        return $details;
    }

    /**
     * Truthy permission map used to simplify Super Admin > Manage for a
     * package-controlled subscription. Non-permission limits/dates are ignored.
     */
    public function allowedPermissionMap(array $details): array
    {
        $allowed = [];

        foreach ($details as $key => $value) {
            $key = $this->normaliseKey($key);
            if ($key === '' || str_starts_with($key, '_')) {
                continue;
            }

            if (is_array($value)) {
                // Some permission families are stored as associative child maps.
                foreach ($value as $childKey => $childValue) {
                    if (is_int($childKey)) {
                        continue;
                    }
                    $childKey = $this->normaliseKey($childKey);
                    if ($childKey !== '' && $this->isTruthy($childValue)) {
                        $allowed[$childKey] = 1;
                    }
                }
                continue;
            }

            if ($this->isTruthy($value)) {
                $allowed[$key] = 1;
            }
        }

        return $allowed;
    }

    private function sanitizePermissions($permissions): array
    {
        $clean = [];

        foreach ((array) $permissions as $key => $value) {
            $key = $this->normaliseKey($key);
            if ($key === '') {
                continue;
            }

            $clean[$key] = $this->isTruthy($value) ? 1 : 0;
        }

        ksort($clean, SORT_NATURAL | SORT_FLAG_CASE);

        return $clean;
    }

    private function normaliseKey($value): string
    {
        $value = strtolower(trim((string) $value));
        $value = preg_replace('/[^a-z0-9_]+/', '_', $value);
        $value = trim((string) preg_replace('/_+/', '_', (string) $value), '_');

        return preg_match('/^[a-z0-9_]+$/', $value) ? $value : '';
    }

    private function isTruthy($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (float) $value > 0;
        }

        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on', 'enabled'], true);
    }
}
