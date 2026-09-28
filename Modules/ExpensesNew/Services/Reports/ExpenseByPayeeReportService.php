<?php

namespace Modules\ExpensesNew\Services\Reports;

use Modules\ExpensesNew\Repositories\Reports\ExpenseByPayeeRepository;

class ExpenseByPayeeReportService
{
    protected $repository;
    public function __construct(ExpenseByPayeeRepository $repository)
    {
        $this->repository = $repository;
    }
    public function build(array $filters = []): array
    {
        return ['rows' => $this->repository->rows($filters), 'totals' => []];
    }
}
