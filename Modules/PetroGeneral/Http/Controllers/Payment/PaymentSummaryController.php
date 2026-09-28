<?php

namespace Modules\PetroGeneral\Http\Controllers\Payment;

class PaymentSummaryController extends BasePaymentController
{
    public function summarypaymnetdashboard()
    {
        return parent::summarypaymnetdashboard();
    }

    public function getPaymentSummaryModal()
    {
        return parent::getPaymentSummaryModal();
    }

    public function getPaymentModal()
    {
        return parent::getPaymentModal();
    }

    public function balanceToOperator($pump_operator_id)
    {
        return parent::balanceToOperator($pump_operator_id);
    }

    public function metersWithPayments()
    {
        return parent::metersWithPayments();
    }
}
