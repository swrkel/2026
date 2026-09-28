<?php

namespace Modules\PetroGeneral\Http\Controllers\SettlementPD;

use Modules\PetroGeneral\Http\Controllers\SettlementPDController;

class SettlementPDAjaxController extends SettlementPDController
{
    public function checkPreviousPumpSettlementPD()
    {
        return parent::checkPreviousPumpSettlementPD();
    }

    public function pumpDetails($pump_id, $shift_id = null)
    {
        if ($shift_id !== null) {
            return parent::getPumpDetailsPerShift($pump_id, $shift_id);
        }

        return parent::getPumpDetails($pump_id);
    }

    public function pumps()
    {
        return parent::getPumps();
    }

    public function balanceStock()
    {
        return parent::getBalanceStock();
    }

    public function storesById($id)
    {
        return parent::getStoresById($id);
    }

    public function productsByStoreId($id)
    {
        return parent::getProductsByStoreId($id);
    }

    public function balanceStockById($id)
    {
        return parent::getBalanceStockById($id);
    }

    public function paymentTabTotals()
    {
        return parent::getPaymentTabTotals();
    }

    public function checkSlipNo()
    {
        return parent::checkSlipNo();
    }
}
