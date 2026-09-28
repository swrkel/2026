<?php

namespace Modules\Suppliers\Utils;

use App\Utils\SidebarPermissionUtil;
use Modules\Superadmin\Entities\Subscription;

/**
 * Resolves the standalone Suppliers module access independently from Contacts.
 *
 * The Super Admin / Manage parent permission grants the module to the business,
 * while Manage Side Bar controls whether the granted module is visible/usable.
 * Contact Module permissions never enable or disable this standalone module.
 */
class SupplierPermissionUtil
{
    public static function permissions(): array
    {
        return [
            'suppliers.view',
            'suppliers.create',
            'suppliers.edit',
            'suppliers.delete',
            'suppliers.profile.view',
            'suppliers.contacts.manage',
            'suppliers.documents.manage',
            'suppliers.accounts.manage',
            'suppliers.purchase_history.view',
            'suppliers.reports.view',
        ];
    }

    public static function name(string $permission): string
    {
        return trim($permission);
    }

    public static function can(string $permission): bool
    {
        $aliases = [
            'supplier.view' => ['suppliers.view', 'umn.module.suppliers.view'],
            'suppliers.view' => ['supplier.view', 'umn.module.suppliers.view'],
            'supplier.create' => ['suppliers.create', 'umn.module.suppliers.edit'],
            'suppliers.create' => ['supplier.create', 'umn.module.suppliers.edit'],
            'supplier.update' => ['supplier.edit', 'suppliers.edit', 'umn.module.suppliers.edit'],
            'supplier.edit' => ['supplier.update', 'suppliers.edit', 'umn.module.suppliers.edit'],
            'suppliers.edit' => ['supplier.update', 'supplier.edit', 'umn.module.suppliers.edit'],
            'supplier.delete' => ['suppliers.delete', 'umn.module.suppliers.delete'],
            'suppliers.delete' => ['supplier.delete', 'umn.module.suppliers.delete'],
        ];

        $permission = self::name($permission);
        $permissions = [$permission];
        if (isset($aliases[$permission])) {
            $permissions = array_merge($permissions, $aliases[$permission]);
        }

        foreach (array_unique($permissions) as $candidate) {
            if (SupplierContextUtil::can($candidate)) {
                return true;
            }
        }

        return false;
    }

    public static function authorize(string $permission): void
    {
        abort_unless(self::can($permission), 403, 'Unauthorized action.');
    }

    /** @var array<int, array<string, mixed>> */
    private static array $requestPackageDetails = [];

    public static function businessId(): int
    {
        return SupplierContextUtil::businessId();
    }

    public static function packageDetails($provided = null, ?int $businessId = null): array
    {
        if (is_string($provided)) {
            $decoded = json_decode($provided, true);
            $provided = is_array($decoded) ? $decoded : [];
        }
        if (is_object($provided)) {
            $provided = (array) $provided;
        }
        if (is_array($provided) && $provided !== []) {
            return $provided;
        }

        $businessId = $businessId ?: self::businessId();
        if ($businessId > 0 && array_key_exists($businessId, self::$requestPackageDetails)) {
            return self::$requestPackageDetails[$businessId];
        }

        foreach (['business.package_details', 'package_details'] as $sessionKey) {
            $details = function_exists('session') ? session($sessionKey) : null;
            if (is_string($details)) {
                $decoded = json_decode($details, true);
                $details = is_array($decoded) ? $decoded : [];
            }
            if (is_object($details)) {
                $details = (array) $details;
            }
            if (is_array($details) && $details !== []) {
                if ($businessId > 0) {
                    self::$requestPackageDetails[$businessId] = $details;
                }
                return $details;
            }
        }

        $details = [];
        if ($businessId > 0) {
            try {
                $subscription = Subscription::current_subscription($businessId);
                $details = $subscription ? $subscription->package_details : [];
                if (is_string($details)) {
                    $decoded = json_decode($details, true);
                    $details = is_array($decoded) ? $decoded : [];
                }
                if (is_object($details)) {
                    $details = (array) $details;
                }
                $details = is_array($details) ? $details : [];
            } catch (\Throwable $e) {
                $details = [];
            }
            self::$requestPackageDetails[$businessId] = $details;
        }

        return $details;
    }

    public static function isGranted($packageDetails = null, ?int $businessId = null): bool
    {
        $details = self::packageDetails($packageDetails, $businessId);

        return self::truthy($details['suppliers_module'] ?? null)
            || self::truthy($details['supplier_module'] ?? null);
    }

    public static function isSidebarEnabled(?int $businessId = null): bool
    {
        return SidebarPermissionUtil::isEnabled('suppliers_module', $businessId ?: self::businessId());
    }

    public static function isEnabled($packageDetails = null, ?int $businessId = null): bool
    {
        $businessId = $businessId ?: self::businessId();

        return self::isGranted($packageDetails, $businessId)
            && self::isSidebarEnabled($businessId);
    }

    public static function userCanAccess(): bool
    {
        $user = function_exists('auth') ? auth()->user() : null;
        if (!$user || (int) data_get($user, 'is_customer', 0) !== 0) {
            return false;
        }

        if (method_exists($user, 'can') && $user->can('superadmin')) {
            return true;
        }

        $permissions = ['supplier.view', 'supplier.create', 'supplier.update', 'supplier.delete'];
        foreach ($permissions as $permission) {
            if (self::can($permission)) {
                return true;
            }
        }

        return false;
    }

    public static function clearRequestCache(): void
    {
        self::$requestPackageDetails = [];
    }

    private static function truthy($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on', 'enabled'], true);
    }
}
