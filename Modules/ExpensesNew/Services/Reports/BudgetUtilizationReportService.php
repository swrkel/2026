<?php

namespace Modules\ExpensesNew\Services\Reports;

use Modules\ExpensesNew\Repositories\Reports\BudgetUtilizationRepository;

class BudgetUtilizationReportService
{
    protected $repository;
    public function __construct(BudgetUtilizationRepository $repository)
    {
        $this->repository = $repository;
    }
    public function build(array $filters = []): array
    {
        return ['rows' => $this->repository->rows($filters), 'totals' => []];
    }
}
