<?php

namespace Modules\ExpensesNew\Services\Reports;

use Modules\ExpensesNew\Repositories\Reports\ExpenseRegisterRepository;

class ExpenseRegisterReportService
{
    protected $repository;
    public function __construct(ExpenseRegisterRepository $repository)
    {
        $this->repository = $repository;
    }
    public function build(array $filters = []): array
    {
        return ['rows' => $this->repository->rows($filters), 'totals' => []];
    }
}
