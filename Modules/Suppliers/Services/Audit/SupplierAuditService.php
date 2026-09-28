<?php

namespace Modules\Suppliers\Services\Audit;

use Illuminate\Support\Facades\Schema;
use Modules\Suppliers\Entities\Supplier;
use Modules\Suppliers\Entities\SupplierActivityLog;
use Modules\Suppliers\Utils\SupplierMorphTypeUtil;

class SupplierAuditService
{
    public function timeline(Supplier $supplier): array
    {
        if (! Schema::hasTable((new SupplierActivityLog())->getTable())) {
            return [];
        }

        return SupplierActivityLog::query()
            ->where(function ($query) use ($supplier) {
                $query->whereIn('subject_type', SupplierMorphTypeUtil::supplierTypes())
                    ->where('subject_id', $supplier->id);
            })
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(function ($row) {
                return [
                    'description' => $row->description,
                    'created_at' => $row->created_at ? date('Y-m-d H:i', strtotime($row->created_at)) : null,
                ];
            })
            ->toArray();
    }
}
