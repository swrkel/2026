<?php

namespace Modules\Purchase\Services\Payment;

use Modules\Purchase\Entities\SupplierPayment;

class SupplierPaymentDeleteService
{
    public function delete($id): void
    {
        SupplierPayment::where('business_id', session('user.business_id'))->findOrFail($id)->delete();
    }
}
