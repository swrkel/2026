<?php

namespace Modules\PetroGeneral\Http\Controllers\AddPayment;

use Illuminate\Http\Request;

class CreditSalePaymentActionController extends BaseAddPaymentActionController
{
    public function saveCreditSalePayment(Request $request)
    {
        return $this->legacy->saveCreditSalePayment($request);
    }

    public function deleteCreditSalePayment($id)
    {
        return $this->legacy->deleteCreditSalePayment($id);
    }

    public function checkOrderNumber(Request $request)
    {
        return $this->legacy->check_order_number($request);
    }

    public function getCustomerDetails($customer_id)
    {
        return $this->legacy->getCustomerDetails($customer_id);
    }

    public function productPreview($id)
    {
        return $this->legacy->productPreview($id);
    }
}
