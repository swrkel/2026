<?php

namespace Modules\Suppliers\Utils;

/**
 * Central place for Suppliers module middleware names.
 *
 * This avoids scattering host application middleware strings throughout
 * individual supplier controllers/routes. If auth/permission middleware names
 * change in the ERP, only this module-local file needs adjustment.
 */
class SupplierMiddlewareUtil
{
    public static function auth(): string
    {
        return 'auth';
    }

    public static function permission(string $permission): string
    {
        return 'permission:' . SupplierPermissionUtil::name($permission);
    }

    public static function supplierAccess(): array
    {
        return [self::auth()];
    }
}
