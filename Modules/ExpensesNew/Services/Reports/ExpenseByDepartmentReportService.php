<?php

namespace Modules\ExpensesNew\Services\Reports;

use Modules\ExpensesNew\Repositories\Reports\ExpenseByDepartmentRepository;

class ExpenseByDepartmentReportService
{
    protected $repository;
    public function __construct(ExpenseByDepartmentRepository $repository)
    {
        $this->repository = $repository;
    }
    public function build(array $filters = []): array
    {
        return ['rows' => $this->repository->rows($filters), 'totals' => []];
    }
}
