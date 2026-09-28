<?php

namespace Modules\PetroGeneral\Http\Controllers\AddPayment;

use Illuminate\Http\Request;

class ExpenseShortageExcessActionController extends BaseAddPaymentActionController
{
    public function saveExpensePayment(Request $request)
    {
        return $this->legacy->saveExpensePayment($request);
    }

    public function deleteExpensePayment($id)
    {
        return $this->legacy->deleteExpensePayment($id);
    }

    public function saveShortagePayment(Request $request)
    {
        return $this->legacy->saveShortagePayment($request);
    }

    public function deleteShortagePayment($id)
    {
        return $this->legacy->deleteShortagePayment($id);
    }

    public function saveExcessPayment(Request $request)
    {
        return $this->legacy->saveExcessPayment($request);
    }

    public function deleteExcessPayment($id)
    {
        return $this->legacy->deleteExcessPayment($id);
    }

    public function getExpenseNumber(Request $request)
    {
        return $this->legacy->getExpenseNumber($request);
    }
}
