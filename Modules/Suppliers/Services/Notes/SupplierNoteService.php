<?php

namespace Modules\Suppliers\Services\Notes;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Suppliers\Entities\Supplier;
use Modules\Suppliers\Utils\SupplierContextUtil;
use Modules\Suppliers\Utils\SupplierMorphTypeUtil;

class SupplierNoteService
{
    public function latest(Supplier $supplier): array
    {
        if (! Schema::hasTable('notes')) {
            return [];
        }

        // Query the notes table directly. This avoids legacy morph-relation code
        // that generated the misspelled `notiable_type` / `notiable_id` columns
        // and caused the Supplier Profile page to fail.
        if (! Schema::hasColumn('notes', 'notable_type') || ! Schema::hasColumn('notes', 'notable_id')) {
            return [];
        }

        $query = DB::table('notes')
            ->whereIn('notable_type', SupplierMorphTypeUtil::supplierTypes())
            ->where('notable_id', (int) $supplier->id);

        if (Schema::hasColumn('notes', 'business_id')) {
            $query->where('business_id', SupplierContextUtil::businessId());
        }

        if (Schema::hasColumn('notes', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $query
            ->orderByDesc(Schema::hasColumn('notes', 'created_at') ? 'created_at' : 'id')
            ->limit(20)
            ->get()
            ->map(static function ($row): array {
                return [
                    'heading' => $row->heading ?? null,
                    'description' => $row->description ?? null,
                    'created_at' => ! empty($row->created_at)
                        ? date('Y-m-d H:i', strtotime((string) $row->created_at))
                        : null,
                ];
            })
            ->toArray();
    }
}
