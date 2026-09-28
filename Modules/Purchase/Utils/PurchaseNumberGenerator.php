<?php

namespace Modules\Purchase\Utils;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PurchaseNumberGenerator
{
    public function generate(string $prefix, int $nextNo): string
    {
        return $prefix . str_pad((string) max(1, $nextNo), 6, '0', STR_PAD_LEFT);
    }

    public function next(string $type, int $businessId): string
    {
        $prefix = $type === 'purchase_order' ? 'PON-' : 'PEN-';
        if (! Schema::hasTable('transactions')) {
            return $this->generate($prefix, 1);
        }

        $next = max(1, (int) DB::table('transactions')
            ->where('business_id', $businessId)
            ->where('type', $type === 'purchase_order' ? 'purchase_order' : 'purchase')
            ->max('id') + 1);

        do {
            $candidate = $this->generate($prefix, $next++);
            $query = DB::table('transactions')
                ->where('business_id', $businessId)
                ->where('invoice_no', $candidate);
            if (Schema::hasColumn('transactions', 'deleted_at')) {
                $query->whereNull('deleted_at');
            }
        } while ($query->exists());

        return $candidate;
    }
}
