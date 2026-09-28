<?php

namespace Modules\Distribution\Repositories;

use Modules\Distribution\Entities\DistributionProduct;

class DistributionProductRepository extends DistributionBaseRepository
{
    protected string $modelClass = DistributionProduct::class;

    public function activeProducts(?int $businessId = null)
    {
        return $this->forBusiness($businessId)->where(function ($query) {
            $query->whereNull('not_for_selling')->orWhere('not_for_selling', 0);
        });
    }
}
