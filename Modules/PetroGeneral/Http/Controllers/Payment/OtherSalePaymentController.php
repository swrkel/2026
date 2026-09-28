<?php

namespace Modules\PetroGeneral\Http\Controllers\Payment;

use Illuminate\Http\Request;

class OtherSalePaymentController extends BasePaymentController
{
    public function getOtherSale()
    {
        return parent::getOtherSale();
    }

    public function otherSales($shift_id)
    {
        return parent::otherSales($shift_id);
    }

    public function otherSalesList(Request $request)
    {
        return parent::otherSalesList($request);
    }

    public function pumpOtherSalesList(Request $request)
    {
        return parent::pumpOtherSalesList($request);
    }

    public function othersalespage(Request $request)
    {
        return parent::othersalespage($request);
    }

    public function getProducts(Request $request)
    {
        return parent::getProducts($request);
    }

    public function saveOtherSale(Request $request)
    {
        return parent::saveOtherSale($request);
    }

    public function updateOtherSaleItem(Request $request, $id)
    {
        return parent::updateOtherSaleItem($request, $id);
    }

    public function deleteOtherSaleItem($id)
    {
        return parent::deleteOtherSaleItem($id);
    }

    public function saveOtherSaleItems(Request $request)
    {
        return parent::saveOtherSaleItems($request);
    }

    public function deleteOtherSale($id)
    {
        return parent::deleteOtherSale($id);
    }
}
