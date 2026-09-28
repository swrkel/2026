<?php

namespace Modules\PetroGeneral\Http\Controllers\Settlement;

use Modules\PetroGeneral\Http\Controllers\SettlementController;

class SettlementStockAjaxController extends SettlementController
{
    public function getBalanceStock($id)
    {
        return parent::getBalanceStock($id);
    }

    public function getBalanceStockById($id)
    {
        return parent::getBalanceStockById($id);
    }
}
