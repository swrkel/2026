<?php

namespace Modules\ProductsNew\Services\Report;

class AbcXyzAnalysisReportService
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
        return 'ABC XYZ Analysis';
    }

    public function description(): string
    {
        return 'Classification-ready product analytics.';
    }
}
