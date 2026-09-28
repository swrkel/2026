<?php

namespace Modules\PetroGeneral\Http\Controllers\SettlementPD;

use Modules\PetroGeneral\Http\Controllers\SettlementPDController;

class SettlementPDAdjustmentController extends SettlementPDController
{
    public function adjustDiscounts()
    {
        return parent::adjustDiscounts();
    }

    public function adjustMeterSalesDates()
    {
        return parent::adjustMeterSalesDates();
    }

    public function updateCreditSales()
    {
        return parent::updateCreditSales();
    }
}
