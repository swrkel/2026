<?php

namespace Modules\ProductsNew\Services\Report;

class InventoryTurnoverReportService
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
        return 'Inventory Turnover';
    }

    public function description(): string
    {
        return 'Turnover-ready stock and product analytics.';
    }
}
