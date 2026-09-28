<?php

namespace Modules\Distribution\Repositories;

use Modules\Distribution\Entities\DistributionSalesOrder;

class DistributionSalesOrderRepository extends DistributionBaseRepository
{
    protected string $modelClass = DistributionSalesOrder::class;

    public function byInvoiceNo(string $invoiceNo, ?int $businessId = null)
    {
        return $this->forBusiness($businessId)->where('invoice_no', $invoiceNo)->first();
    }
}
