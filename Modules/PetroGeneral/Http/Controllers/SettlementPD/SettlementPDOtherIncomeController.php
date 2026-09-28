<?php

namespace Modules\PetroGeneral\Http\Controllers\SettlementPD;

use Modules\PetroGeneral\Http\Controllers\SettlementPDController;

class SettlementPDOtherIncomeController extends SettlementPDController
{
    public function save()
    {
        return parent::saveOtherIncome();
    }

    public function delete($id)
    {
        return parent::deleteOtherIncome($id);
    }
}
