<?php

namespace Modules\Distribution\Repositories;

use Modules\Distribution\Entities\DistributionTransactionPayment;

class DistributionPaymentRepository extends DistributionBaseRepository
{
    protected string $modelClass = DistributionTransactionPayment::class;

    public function forTransaction($transactionId, ?int $businessId = null)
    {
        return $this->forBusiness($businessId)->where('transaction_id', $transactionId);
    }
}
