<?php

namespace Modules\ProductsNew\Services\Report;

class FastSlowDeadStockReportService
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
        return 'Fast / Slow / Dead Stock';
    }

    public function description(): string
    {
        return 'Movement-ready product performance classification.';
    }
}
