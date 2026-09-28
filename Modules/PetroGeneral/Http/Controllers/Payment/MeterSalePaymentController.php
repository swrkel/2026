<?php

namespace Modules\PetroGeneral\Http\Controllers\Payment;

use Illuminate\Http\Request;

class MeterSalePaymentController extends BasePaymentController
{
    public function saveMeterSale(Request $request)
    {
        return parent::saveMeterSale($request);
    }

    public function meterSalesList(Request $request)
    {
        return parent::meterSalesList($request);
    }
}
