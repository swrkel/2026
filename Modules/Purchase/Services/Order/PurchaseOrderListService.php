<?php

namespace Modules\Purchase\Services\Order;

use Modules\Purchase\Entities\PurchaseTransaction;

class PurchaseOrderListService
{
    public function paginate()
    {
        return PurchaseTransaction::query()
            ->where('business_id', session('user.business_id'))
            ->where('type', 'purchase_order')
            ->latest('id')
            ->paginate(25);
    }
}
