<?php

namespace Modules\Suppliers\Services;

use Modules\Suppliers\Repositories\SupplierRepository;
use Modules\Suppliers\Utils\SupplierContextUtil;
use Modules\Suppliers\Utils\SupplierDatabaseUtil;

class SupplierNumberService
{
    protected SupplierRepository $suppliers;

    public function __construct(?SupplierRepository $suppliers = null)
    {
        $this->suppliers = $suppliers ?: new SupplierRepository();
    }

    public function nextSupplierNumber(): string
    {
        $businessId = (int) SupplierContextUtil::businessId();
        $prefix = 'SUP';

        return SupplierDatabaseUtil::transaction(function () use ($businessId, $prefix) {
            $lastId = $this->suppliers->nextRawIdForBusiness($businessId);
            $next = $lastId + 1;

            return $prefix . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
        });
    }
}
