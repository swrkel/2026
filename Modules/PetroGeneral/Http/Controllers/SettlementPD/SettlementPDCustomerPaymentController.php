<?php

namespace Modules\PetroGeneral\Http\Controllers\SettlementPD;

use Modules\PetroGeneral\Http\Controllers\SettlementPDController;

class SettlementPDCustomerPaymentController extends SettlementPDController
{
    public function save()
    {
        return parent::saveCustomerPayment();
    }

    public function delete($id)
    {
        return parent::deleteCustomerPayment($id);
    }
}
