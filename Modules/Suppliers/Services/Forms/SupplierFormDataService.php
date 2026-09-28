<?php

namespace Modules\Suppliers\Services\Forms;

use Modules\Suppliers\Entities\SupplierBusinessLocation;

class SupplierFormDataService
{
    public function defaults(?object $supplier = null): array
    {
        return [
            'payTermTypes' => [
                'days' => __('suppliers::lang.days'),
                'months' => __('suppliers::lang.months'),
            ],
            'supplier' => $supplier,
            'locations' => $this->locations(),
        ];
    }

    private function locations(): array
    {
        $businessId = (int) \Modules\Suppliers\Utils\SupplierContextUtil::businessId();

        return SupplierBusinessLocation::where('business_id', $businessId)
            ->pluck('name', 'id')
            ->toArray();
    }
}
