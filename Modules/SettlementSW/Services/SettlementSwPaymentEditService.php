<?php

namespace Modules\SettlementSW\Services;

use Modules\SettlementSW\Entities\SettlementCashPayment;

/**
 * SW_SEP_008
 * Local module service for Settlement SW payment edit operations.
 */
class SettlementSwPaymentEditService
{
    public function editCashPayment($businessId, $paymentId, array $data)
    {
        $payment = SettlementCashPayment::where('id', $paymentId)
            ->where('business_id', $businessId)
            ->firstOrFail();

        $payment->fill($data);
        $payment->save();

        return $payment;
    }
}
