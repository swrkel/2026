<?php

namespace Modules\PetroGeneral\Http\Controllers\Settlement\Direct;

use Modules\PetroGeneral\Http\Controllers\SettlementController as LegacyDirectSettlementController;

/**
 * PG015 safe split wrapper.
 *
 * This controller keeps the existing Direct Settlement logic untouched while
 * moving action routes into small, maintainable controller files. After UAT,
 * the parent logic can be moved here method-by-method.
 */
class PumpDetailController extends LegacyDirectSettlementController
{
    public function details($pump_id, $shift_id = null)
    {
        return parent::getPumpDetails($pump_id, $shift_id);
    }

    public function detailsPerShift($pump_id, $shift_id)
    {
        return parent::getPumpDetailsPerShift($pump_id, $shift_id);
    }

    public function pumps($id = null)
    {
        return parent::getPumps($id);
    }

    public function pumpsByLocation($request = null)
    {
        return parent::getPumpsByLocation($request);
    }
}
