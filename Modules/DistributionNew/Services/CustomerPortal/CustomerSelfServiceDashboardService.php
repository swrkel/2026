<?php

namespace Modules\DistributionNew\Services\CustomerPortal;

use Modules\DistributionNew\Entities\CustomerPortal\CustomerComplaint;
use Modules\DistributionNew\Entities\CustomerPortal\CustomerReturnRequest;

class CustomerSelfServiceDashboardService
{
    public function summary(int $businessId, int $customerId): array
    {
        return [
            'open_orders' => 0,
            'pending_deliveries' => 0,
            'unpaid_invoices' => 0,
            'open_returns' => CustomerReturnRequest::where('business_id', $businessId)->where('customer_id', $customerId)->whereNotIn('status', ['closed','rejected'])->count(),
            'open_complaints' => CustomerComplaint::where('business_id', $businessId)->where('customer_id', $customerId)->whereNotIn('status', ['resolved','closed','cancelled'])->count(),
        ];
    }
}
