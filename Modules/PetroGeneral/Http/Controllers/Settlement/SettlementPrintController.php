<?php

namespace Modules\PetroGeneral\Http\Controllers\Settlement;

use Modules\PetroGeneral\Http\Controllers\SettlementController;

class SettlementPrintController extends SettlementController
{
    public function print($id)
    {
        return parent::print($id);
    }

    public function mechanicalMeter($id)
    {
        return parent::mechanicalMeter($id);
    }
}
