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
class MeterSaleController extends LegacyDirectSettlementController
{
    public function list($request = null)
    {
        return parent::meter_sales($request);
    }

    public function edit($id)
    {
        return parent::editMeterSale($id);
    }

    public function update($request, $id)
    {
        return parent::updateMeterSale($request, $id);
    }

    public function save($request)
    {
        return parent::saveMeterSale($request);
    }

    public function delete($id)
    {
        return parent::deleteMeterSale($id);
    }

    public function form($id)
    {
        return parent::getMeterSaleForm($id);
    }

    public function updateSettlement($request, $id)
    {
        return parent::updateSettlementMeterSale($request, $id);
    }
}
