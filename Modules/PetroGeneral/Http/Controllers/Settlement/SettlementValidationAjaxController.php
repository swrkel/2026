<?php

namespace Modules\PetroGeneral\Http\Controllers\Settlement;

use Illuminate\Http\Request;
use Modules\PetroGeneral\Http\Controllers\SettlementController;

class SettlementValidationAjaxController extends SettlementController
{
    public function checkPreviousPumpSettlement(Request $request)
    {
        return parent::checkPreviousPumpSettlement($request);
    }

    public function checkSlipNo(Request $request)
    {
        return parent::checkSlipNo($request);
    }
}
