<?php

namespace Modules\Purchase\Services\Order;

use Modules\Purchase\Entities\PurchaseTransaction;

class PurchaseOrderShowService
{
    public function find($id): PurchaseTransaction
    {
        return PurchaseTransaction::where('business_id', session('user.business_id'))
            ->where('type', 'purchase_order')
            ->findOrFail($id);
    }
}
