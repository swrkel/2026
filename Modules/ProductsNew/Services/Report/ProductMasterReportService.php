<?php

namespace Modules\ProductsNew\Services\Report;

use Modules\ProductsNew\Services\ProductQueryService;

class ProductMasterReportService
{
    public function __construct(protected ProductQueryService $products)
    {
    }

    public function data(array $filters = [])
    {
        if (! empty($filters['keyword']) && empty($filters['search'])) {
            $filters['search'] = $filters['keyword'];
        }

        return $this->products->paginated($filters, 50);
    }
}
