<?php

namespace Modules\Distribution\Repositories;

use Modules\Distribution\Entities\DistributionInvoice;

class DistributionInvoiceRepository extends DistributionBaseRepository
{
    protected string $modelClass = DistributionInvoice::class;

    public function byInvoiceNo(string $invoiceNo, ?int $businessId = null)
    {
        return $this->forBusiness($businessId)->where('invoice_no', $invoiceNo)->first();
    }
}
