<?php

namespace Modules\SettlementSW\Services;

/** SW_SEP_003 customer payment service. */
class SettlementSwCustomerPaymentService extends SettlementSwBaseService
{
    public function totalCollections($payments): float
    {
        return $this->sumAmount($payments, 'amount');
    }
}
