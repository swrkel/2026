<?php

namespace Modules\ProductsNew\Services\Report;

class ProductProfitabilityReportService
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
        return 'Product Profitability';
    }

    public function description(): string
    {
        return 'Provides product details for profitability analysis.';
    }
}
