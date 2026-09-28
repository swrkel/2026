<?php

namespace Modules\PetroGeneral\Http\Controllers\AddPayment;

use Illuminate\Http\Request;

class ChequePaymentActionController extends BaseAddPaymentActionController
{
    public function saveChequePayment(Request $request)
    {
        return $this->legacy->saveChequePayment($request);
    }

    public function deleteChequePayment($id)
    {
        return $this->legacy->deleteChequePayment($id);
    }
}
