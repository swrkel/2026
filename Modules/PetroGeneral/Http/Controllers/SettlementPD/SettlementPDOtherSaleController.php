<?php

namespace Modules\PetroGeneral\Http\Controllers\SettlementPD;

use Modules\PetroGeneral\Http\Controllers\SettlementPDController;

class SettlementPDOtherSaleController extends SettlementPDController
{
    public function save()
    {
        return parent::saveOtherSale();
    }

    public function delete($id)
    {
        return parent::deleteOtherSale($id);
    }
}
