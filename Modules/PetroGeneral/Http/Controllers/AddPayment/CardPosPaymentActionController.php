<?php

namespace Modules\PetroGeneral\Http\Controllers\AddPayment;

use Illuminate\Http\Request;

class CardPosPaymentActionController extends BaseAddPaymentActionController
{
    public function saveCardPayment(Request $request)
    {
        return $this->legacy->saveCardPayment($request);
    }

    public function deleteCardPayment($id)
    {
        return $this->legacy->deleteCardPayment($id);
    }

    public function savePosPayment(Request $request)
    {
        return $this->legacy->savePosPayment($request);
    }

    public function deletePosPayment($id)
    {
        return $this->legacy->deletePosPayment($id);
    }
}
