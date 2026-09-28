<?php

namespace Modules\ProductsNew\Services\Report;

class ProductExpiryReportService
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
        return 'Expiry';
    }

    public function description(): string
    {
        return 'Provides a schema-safe product list for expiry analysis.';
    }
}
