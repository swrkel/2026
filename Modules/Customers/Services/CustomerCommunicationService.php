<?php

namespace Modules\Customers\Services;

use Illuminate\Support\Facades\Schema;
use Modules\Customers\Entities\CustomerCommunication;

/**
 * CustomerCommunicationService
 *
 * Customers-owned communication foundation. Methods are intentionally safe:
 * if the optional customer_communications table is not yet migrated, the
 * service returns empty results instead of breaking existing customer pages.
 */
class CustomerCommunicationService
{
    public function tableReady(): bool
    {
        return Schema::hasTable('customer_communications');
    }

    public function recentForCustomer(int $businessId, int $customerId, int $limit = 20)
    {
        if (! $this->tableReady()) {
            return collect();
        }

        return CustomerCommunication::where('business_id', $businessId)
            ->where('customer_id', $customerId)
            ->latest('id')
            ->limit($limit)
            ->get();
    }
}
