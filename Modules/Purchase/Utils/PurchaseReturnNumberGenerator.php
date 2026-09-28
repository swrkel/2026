<?php

namespace Modules\Purchase\Utils;

use Modules\Purchase\Entities\PurchaseReturn;

class PurchaseReturnNumberGenerator
{
    public function next(int $businessId): string
    {
        $prefix = 'PR-';
        $latest = PurchaseReturn::where('business_id', $businessId)->where('type', 'purchase_return')->where('ref_no', 'like', $prefix . '%')->orderByDesc('id')->value('ref_no');
        $number = 1;
        if (! empty($latest) && preg_match('/(\d+)$/', $latest, $matches)) {
            $number = ((int) $matches[1]) + 1;
        }
        return $prefix . str_pad((string) $number, 4, '0', STR_PAD_LEFT);
    }
}
