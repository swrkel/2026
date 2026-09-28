<?php

namespace Modules\PetroGeneral\Http\Controllers\Payment;

use Illuminate\Http\Request;

class CreditPaymentController extends BasePaymentController
{
    public function saveCredit(Request $request)
    {
        return parent::saveCredit($request);
    }

    public function printCreditSale($id)
    {
        return parent::printCreditSale($id);
    }

    public function reprintCreditSale($pump_operator_payment_id)
    {
        return parent::reprintCreditSale($pump_operator_payment_id);
    }

    public function reprintCreditSaleByScspId($scsp_id)
    {
        return parent::reprintCreditSaleByScspId($scsp_id);
    }
}
