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
class ValidationController extends LegacyDirectSettlementController
{
    public function checkPrevious($request = null)
    {
        return parent::checkPreviousPumpSettlement($request);
    }

    public function checkSlipNo($request = null)
    {
        return parent::checkSlipNo($request);
    }
}
