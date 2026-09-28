<?php

namespace Modules\PetroGeneral\Http\Controllers\SettlementPD;

use Modules\PetroGeneral\Http\Controllers\SettlementPDController;

class SettlementPDMeterSaleController extends SettlementPDController
{
    public function form($id)
    {
        return parent::getMeterSaleForm($id);
    }

    public function edit($id)
    {
        return parent::editMeterSale($id);
    }

    public function update($id)
    {
        return parent::updateMeterSale($id);
    }

    public function updateSettlementMeterSale($id)
    {
        return parent::updateSettlementMeterSale($id);
    }

    public function save()
    {
        return parent::saveMeterSale();
    }

    public function delete($id)
    {
        return parent::deleteMeterSale($id);
    }
}
