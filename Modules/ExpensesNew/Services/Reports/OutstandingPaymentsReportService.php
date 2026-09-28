<?php

namespace Modules\ExpensesNew\Services\Reports;

use Modules\ExpensesNew\Repositories\Reports\OutstandingPaymentsRepository;

class OutstandingPaymentsReportService
{
    protected $repository;
    public function __construct(OutstandingPaymentsRepository $repository)
    {
        $this->repository = $repository;
    }
    public function build(array $filters = []): array
    {
        return ['rows' => $this->repository->rows($filters), 'totals' => []];
    }
}
