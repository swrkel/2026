<?php

namespace Modules\PetroGeneral\Http\Controllers\AddPayment;

use Illuminate\Http\Request;

class CashPaymentActionController extends BaseAddPaymentActionController
{
    public function saveCashPayment(Request $request)
    {
        return $this->legacy->saveCashPayment($request);
    }

    public function deleteCashPayment($id)
    {
        return $this->legacy->deleteCashPayment($id);
    }

    public function saveCashDeposit(Request $request)
    {
        return $this->legacy->saveCashDeposit($request);
    }

    public function deleteCashDeposit($id)
    {
        return $this->legacy->deleteCashDeposit($id);
    }
}
