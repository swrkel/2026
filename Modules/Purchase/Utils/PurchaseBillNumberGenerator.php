<?php

namespace Modules\Purchase\Utils;

use Modules\Purchase\Entities\PurchaseBill;

class PurchaseBillNumberGenerator
{
    public function next(int $businessId): string
    {
        $prefix = 'PB-';
        $latest = PurchaseBill::where('business_id', $businessId)
            ->where('ref_no', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('ref_no');

        $number = 1;
        if (! empty($latest) && preg_match('/(\d+)$/', $latest, $matches)) {
            $number = ((int) $matches[1]) + 1;
        }

        return $prefix . str_pad((string) $number, 4, '0', STR_PAD_LEFT);
    }
}
