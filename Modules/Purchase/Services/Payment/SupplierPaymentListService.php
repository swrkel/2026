<?php

namespace Modules\Purchase\Services\Payment;

use Modules\Purchase\Entities\SupplierPayment;

class SupplierPaymentListService
{
    public function paginate()
    {
        return SupplierPayment::where('business_id', session('user.business_id'))
            ->latest('id')
            ->paginate(25);
    }
}
