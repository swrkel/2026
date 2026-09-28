<?php
namespace Modules\BeautySaloons\Reports;
use Modules\BeautySaloons\Entities\BeautyFinancePosting;

class FinancePostingReport
{
    public function query(array $filters = [])
    {
        return BeautyFinancePosting::query()
            ->when($filters['start_date'] ?? null, fn($q, $v) => $q->whereDate('posting_date', '>=', $v))
            ->when($filters['end_date'] ?? null, fn($q, $v) => $q->whereDate('posting_date', '<=', $v));
    }
}
