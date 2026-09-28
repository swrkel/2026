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
class StockProductController extends LegacyDirectSettlementController
{
    public function balance($id)
    {
        return parent::getBalanceStock($id);
    }

    public function stores($id)
    {
        return parent::getStoresById($id);
    }

    public function products($id)
    {
        return parent::getProductsByStoreId($id);
    }

    public function balanceById($id)
    {
        return parent::getBalanceStockById($id);
    }
}
