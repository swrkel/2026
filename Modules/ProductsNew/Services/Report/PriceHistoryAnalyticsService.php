<?php

namespace Modules\ProductsNew\Services\Report;

class PriceHistoryAnalyticsService
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
        return 'Price History Analytics';
    }

    public function description(): string
    {
        return 'Summarises product information for price-history analysis.';
    }
}
