<?php

namespace Modules\ExpensesNew\Services\Reports;

use Modules\ExpensesNew\Repositories\Reports\ProfitabilityAnalysisRepository;

class ProfitabilityAnalysisReportService
{
    protected $repository;
    public function __construct(ProfitabilityAnalysisRepository $repository)
    {
        $this->repository = $repository;
    }
    public function build(array $filters = []): array
    {
        return ['rows' => $this->repository->rows($filters), 'totals' => []];
    }
}
