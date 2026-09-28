<?php

namespace Modules\PetroGeneral\Http\Controllers\AddPayment;

class PaymentPreviewActionController extends BaseAddPaymentActionController
{
    public function preview($id)
    {
        return $this->legacy->preview($id);
    }

    public function getProductPrice()
    {
        return $this->legacy->getProductPrice(request());
    }
}
