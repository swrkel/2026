<?php

namespace Modules\ProductsNew\Services\Report;

class NegativeOverstockReportService
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
        return 'Negative & Overstock';
    }

    public function description(): string
    {
        return 'Flags products for negative and overstock review.';
    }
}
