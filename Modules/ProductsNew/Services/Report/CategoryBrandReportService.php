<?php

namespace Modules\ProductsNew\Services\Report;

class CategoryBrandReportService
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
        return 'Category & Brand';
    }

    public function description(): string
    {
        return 'Groups products by category and brand.';
    }
}
