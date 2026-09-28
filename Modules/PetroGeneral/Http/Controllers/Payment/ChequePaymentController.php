<?php

namespace Modules\PetroGeneral\Http\Controllers\Payment;

use Illuminate\Http\Request;

class ChequePaymentController extends BasePaymentController
{
    public function saveChequePayment(Request $request)
    {
        return parent::saveChequePayment($request);
    }
}
