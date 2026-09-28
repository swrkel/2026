<?php

namespace Modules\ProductsNew\Services\Report;

class ReorderRecommendationReportService
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
        return 'Reorder Recommendation';
    }

    public function description(): string
    {
        return 'Compares products with their stock alert requirements.';
    }
}
