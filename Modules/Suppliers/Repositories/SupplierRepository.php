<?php

namespace Modules\Suppliers\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Modules\Suppliers\Entities\Supplier;
use Modules\Suppliers\Entities\SupplierContactGroup;
use Modules\Suppliers\Utils\SupplierDatabaseUtil;

class SupplierRepository
{
    public function baseQuery(int $businessId): Builder
    {
        return Supplier::query()
            ->where('business_id', $businessId)
            ->whereIn('type', ['supplier', 'both']);
    }

    public function activeQuery(int $businessId): Builder
    {
        return $this->baseQuery($businessId)->where('active', 1);
    }

    public function findForBusiness(int $businessId, int $supplierId): Supplier
    {
        return $this->baseQuery($businessId)->findOrFail($supplierId);
    }

    public function dropdown(int $businessId, bool $prependNone = true)
    {
        $items = $this->activeQuery($businessId)
            ->select('id', SupplierDatabaseUtil::raw("IF(contact_id IS NULL OR contact_id='', name, CONCAT(name, ' - ', COALESCE(supplier_business_name, ''), '(', contact_id, ')')) AS supplier"))
            ->pluck('supplier', 'id');

        return $prependNone ? $items->prepend(__('lang_v1.none'), '') : $items;
    }


    public function selectedDropdown(int $businessId, ?int $supplierId, bool $prependNone = true)
    {
        $items = collect();

        if (!empty($supplierId)) {
            $supplier = $this->baseQuery($businessId)
                ->select('id', 'name', 'supplier_business_name', 'contact_id')
                ->find($supplierId);

            if ($supplier) {
                $label = trim((string) $supplier->name);
                $businessName = trim((string) $supplier->supplier_business_name);
                $code = trim((string) $supplier->contact_id);

                if ($businessName !== '') {
                    $label .= ' - ' . $businessName;
                }

                if ($code !== '') {
                    $label .= ' (' . $code . ')';
                }

                $items->put((int) $supplier->id, $label);
            }
        }

        return $prependNone ? $items->prepend(__('lang_v1.none'), '') : $items;
    }

    public function groupsDropdown(int $businessId)
    {
        return SupplierContactGroup::forSupplierDropdown($businessId, true);
    }

    public function nextRawIdForBusiness(int $businessId): int
    {
        return (int) Supplier::query()
            ->where('business_id', $businessId)
            ->where('type', 'supplier')
            ->lockForUpdate()
            ->max('id');
    }
}
