<?php

namespace Modules\PetroGeneral\Http\Controllers\Settlement;

use Illuminate\Http\Request;
use Modules\PetroGeneral\Http\Controllers\SettlementController;

class SettlementPaymentAjaxController extends SettlementController
{
    public function getPaymentTabTotals(Request $request)
    {
        return parent::getPaymentTabTotals($request);
    }
}
