<?php

namespace Modules\Distribution\Repositories;

use Modules\Distribution\Entities\DistributionContact;

class DistributionCustomerRepository extends DistributionBaseRepository
{
    protected string $modelClass = DistributionContact::class;

    public function customers(?int $businessId = null)
    {
        return $this->forBusiness($businessId)->where('type', 'customer');
    }

    public function activeCustomers(?int $businessId = null)
    {
        return $this->customers($businessId)->where(function ($query) {
            $query->whereNull('is_inactive')->orWhere('is_inactive', 0);
        });
    }
}
