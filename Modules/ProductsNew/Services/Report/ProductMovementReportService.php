<?php

namespace Modules\ProductsNew\Services\Report;

class ProductMovementReportService
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
        return 'Product Movement';
    }

    public function description(): string
    {
        return 'Provides product details for movement analysis.';
    }
}
