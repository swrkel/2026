<?php

namespace Modules\PetroGeneral\Http\Controllers\Payment;

use Illuminate\Http\Request;

class CardPaymentController extends BasePaymentController
{
    public function saveCardPayment(Request $request)
    {
        return parent::saveCardPayment($request);
    }
}
