<?php

namespace Modules\Purchase\Services\Bill;

use Modules\Purchase\Entities\PurchaseBill;

class PurchaseBillDeleteService
{
    public function delete($id): void
    {
        PurchaseBill::where('business_id', session('user.business_id'))
            ->where('type', 'purchase')
            ->findOrFail($id)
            ->delete();
    }
}
