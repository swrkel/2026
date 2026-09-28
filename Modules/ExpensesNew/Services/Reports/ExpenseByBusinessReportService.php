<?php

namespace Modules\ExpensesNew\Services\Reports;

use Modules\ExpensesNew\Repositories\Reports\ExpenseByBusinessRepository;

class ExpenseByBusinessReportService
{
    protected $repository;
    public function __construct(ExpenseByBusinessRepository $repository)
    {
        $this->repository = $repository;
    }
    public function build(array $filters = []): array
    {
        return ['rows' => $this->repository->rows($filters), 'totals' => []];
    }
}
