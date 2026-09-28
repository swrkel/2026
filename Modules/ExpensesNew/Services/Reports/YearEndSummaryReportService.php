<?php

namespace Modules\ExpensesNew\Services\Reports;

use Modules\ExpensesNew\Repositories\Reports\YearEndSummaryRepository;

class YearEndSummaryReportService
{
    protected $repository;
    public function __construct(YearEndSummaryRepository $repository)
    {
        $this->repository = $repository;
    }
    public function build(array $filters = []): array
    {
        return ['rows' => $this->repository->rows($filters), 'totals' => []];
    }
}
