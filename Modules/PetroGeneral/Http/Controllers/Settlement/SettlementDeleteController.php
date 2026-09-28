<?php

namespace Modules\PetroGeneral\Http\Controllers\Settlement;

use Modules\PetroGeneral\Http\Controllers\SettlementController;

class SettlementDeleteController extends SettlementController
{
    public function destroy($id)
    {
        return parent::destroy($id);
    }
}
