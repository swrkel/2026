<?php

namespace Modules\PetroGeneral\Http\Controllers\AddPayment;

use Illuminate\Http\Request;

class LoanPaymentActionController extends BaseAddPaymentActionController
{
    public function saveCustomerLoan(Request $request)
    {
        return $this->legacy->saveCustomerLoan($request);
    }

    public function deleteCustomerLoan($id)
    {
        return $this->legacy->deleteCustomerLoan($id);
    }

    public function saveLoanPayment(Request $request)
    {
        return $this->legacy->saveLoanPayment($request);
    }

    public function deleteLoanPayment($id)
    {
        return $this->legacy->deleteLoanPayment($id);
    }

    public function saveDrawingPayment(Request $request)
    {
        return $this->legacy->saveDrawingPayment($request);
    }

    public function deleteDrawingPayment($id)
    {
        return $this->legacy->deleteDrawingPayment($id);
    }
}
