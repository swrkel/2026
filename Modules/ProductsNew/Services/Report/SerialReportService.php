<?php

namespace Modules\ProductsNew\Services\Report;

class SerialReportService
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
        return 'Serial Numbers';
    }

    public function description(): string
    {
        return 'Provides product details for serial-number analysis.';
    }
}
