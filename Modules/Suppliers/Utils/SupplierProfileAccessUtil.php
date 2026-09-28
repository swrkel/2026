<?php

namespace Modules\Suppliers\Utils;

use Modules\Suppliers\Entities\Supplier;

class SupplierProfileAccessUtil
{
    public static function ensureTenantSupplier(Supplier $supplier): void
    {
        abort_unless(
            (int) $supplier->business_id === (int) SupplierContextUtil::businessId()
            && in_array((string) $supplier->type, ['supplier', 'both'], true),
            404
        );
    }
}
