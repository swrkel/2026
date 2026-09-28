<?php

namespace Modules\EnterpriseFramework\Services\Performance;

class IndexRecommendationService
{
    public function recommendations(): array
    {
        return [
            'transactions' => ['business_id', 'location_id', 'transaction_date', 'type', 'status'],
            'transaction_payments' => ['business_id', 'payment_for', 'paid_on', 'method'],
            'account_transactions' => ['business_id', 'account_id', 'transaction_date', 'operation_date'],
            'accounts' => ['business_id', 'account_type_id', 'parent_account_id'],
            'business_locations' => ['business_id', 'is_active'],
        ];
    }
}
