<?php

namespace Modules\Suppliers\Services\Documents;

use Illuminate\Support\Facades\Schema;
use Modules\Suppliers\Entities\Supplier;
use Modules\Suppliers\Entities\SupplierMedia;
use Modules\Suppliers\Utils\SupplierMorphTypeUtil;

class SupplierDocumentService
{
    public function list(Supplier $supplier): array
    {
        if (! Schema::hasTable((new SupplierMedia())->getTable())) {
            return [];
        }

        return SupplierMedia::query()
            ->where('business_id', \Modules\Suppliers\Utils\SupplierContextUtil::businessId())
            ->whereIn('model_type', SupplierMorphTypeUtil::supplierTypes())
            ->where('model_id', $supplier->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(function ($row) {
                return [
                    'file_name' => $row->file_name ?? $row->display_name ?? null,
                    'display_name' => $row->display_name ?? $row->file_name ?? null,
                    'created_at' => $row->created_at ? date('Y-m-d H:i', strtotime($row->created_at)) : null,
                ];
            })
            ->toArray();
    }
}
