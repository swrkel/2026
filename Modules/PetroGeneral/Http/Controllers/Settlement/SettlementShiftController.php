<?php

namespace Modules\PetroGeneral\Http\Controllers\Settlement;

use Illuminate\Http\Request;
use Modules\PetroGeneral\Http\Controllers\SettlementController;

class SettlementShiftController extends SettlementController
{
    public function storeManualShiftNumber(Request $request)
    {
        return parent::storeManualShiftNumber($request);
    }
}
