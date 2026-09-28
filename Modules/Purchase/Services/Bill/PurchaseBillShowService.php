<?php

namespace Modules\Purchase\Services\Bill;

use Modules\Purchase\Entities\PurchaseBill;

class PurchaseBillShowService
{
    public function find($id): PurchaseBill
    {
        return PurchaseBill::where('business_id', session('user.business_id'))
            ->where('type', 'purchase')
            ->findOrFail($id);
    }
}
