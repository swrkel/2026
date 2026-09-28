<?php

namespace Modules\Suppliers\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Suppliers\Entities\Supplier;
use Modules\Suppliers\Utils\SupplierContextUtil;

/**
 * Module-local base controller.
 *
 * Supplier controllers extend this class instead of the host application's
 * main controller. Supplier tenant, user, and permission checks are centralized
 * here to keep every feature file small and module-contained.
 */
class SuppliersBaseController extends Controller
{
    protected function supplierBusinessId(): int
    {
        return SupplierContextUtil::businessId();
    }

    protected function supplierUserId(): ?int
    {
        return SupplierContextUtil::userId();
    }

    protected function requireSupplierPermission(string $permission): void
    {
        SupplierContextUtil::requirePermission($permission);
    }

    protected function ensureSupplierAccess(Supplier $supplier): void
    {
        SupplierContextUtil::requireSupplierAccess($supplier);
    }
}
