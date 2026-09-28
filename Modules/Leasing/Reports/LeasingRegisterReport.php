<?php

namespace Modules\Leasing\Reports;

use Modules\Leasing\Models\LeaseContract;

class LeasingRegisterReport
{
    public function query(array $filters = [])
    {
        $query = LeaseContract::query();
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        return $query->orderBy('lease_contractd_on', 'desc');
    }
}
