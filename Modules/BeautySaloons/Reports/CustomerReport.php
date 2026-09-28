<?php

namespace Modules\BeautySaloons\Reports;

use Modules\BeautySaloons\Entities\BeautyCustomerProfile;

class CustomerReport
{
    public function summary(array $filters = [])
    {
        return BeautyCustomerProfile::query()
            ->when($filters['business_location_id'] ?? null, fn ($q, $id) => $q->where('business_location_id', $id))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->selectRaw('customer_type, status, count(*) as customer_count')
            ->groupBy('customer_type', 'status')
            ->get();
    }
}
