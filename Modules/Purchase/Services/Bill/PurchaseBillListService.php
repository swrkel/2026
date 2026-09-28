<?php

namespace Modules\Purchase\Services\Bill;

use Modules\Purchase\Entities\PurchaseBill;

class PurchaseBillListService
{
    public function paginate()
    {
        return PurchaseBill::where('business_id', session('user.business_id'))
            ->where('type', 'purchase')
            ->latest('id')
            ->paginate(25);
    }
}
