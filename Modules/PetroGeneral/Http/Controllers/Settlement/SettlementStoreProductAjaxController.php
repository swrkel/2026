<?php

namespace Modules\PetroGeneral\Http\Controllers\Settlement;

use Illuminate\Http\Request;
use Modules\PetroGeneral\Http\Controllers\SettlementController;

class SettlementStoreProductAjaxController extends SettlementController
{
    public function getStoresById(Request $request)
    {
        return parent::getStoresById($request);
    }

    public function getProductsByStoreId(Request $request)
    {
        return parent::getProductsByStoreId($request);
    }
}
