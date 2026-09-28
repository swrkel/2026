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
class OtherIncomeController extends LegacyDirectSettlementController
{
    public function save($request)
    {
        return parent::saveOtherIncome($request);
    }

    public function delete($id)
    {
        return parent::deleteOtherIncome($id);
    }
}
