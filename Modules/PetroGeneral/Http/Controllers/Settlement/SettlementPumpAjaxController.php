<?php

namespace Modules\PetroGeneral\Http\Controllers\Settlement;

use Illuminate\Http\Request;
use Modules\PetroGeneral\Http\Controllers\SettlementController;

class SettlementPumpAjaxController extends SettlementController
{
    public function getPumpDetails($pump_id, $shift_id = null)
    {
        return parent::getPumpDetails($pump_id, $shift_id);
    }

    public function getPumpDetailsPerShift($pump_id, $shift_id)
    {
        return parent::getPumpDetailsPerShift($pump_id, $shift_id);
    }

    public function getPumps($id)
    {
        return parent::getPumps($id);
    }

    public function getPumpsByLocation(Request $request)
    {
        return parent::getPumpsByLocation($request);
    }
}
