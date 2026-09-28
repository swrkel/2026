<?php

namespace Modules\ProductsNew\Services\Report;

class ProductAgingReportService
{
    public function __construct(protected ProductReportDatasetService $dataset)
    {
    }

    public function rows(array $request)
    {
        return $this->dataset->rows($request);
    }

    public function title(): string
    {
        return 'Product Aging';
    }

    public function description(): string
    {
        return 'Groups products for age and last-movement review.';
    }
}
