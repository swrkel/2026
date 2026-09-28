<?php

namespace Modules\PetroGeneral\Http\Controllers\Payment;

use Illuminate\Http\Request;

class CashPaymentController extends BasePaymentController
{
    public function saveCashDenom(Request $request)
    {
        return parent::saveCashDenom($request);
    }
}
