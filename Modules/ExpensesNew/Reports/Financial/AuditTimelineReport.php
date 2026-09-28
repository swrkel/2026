<?php

namespace Modules\ExpensesNew\Reports\Financial;

class AuditTimelineReport
{
    public string $code = 'audittimeline';
    public string $title = 'AuditTimeline Report';

    public function columns(): array
    {
        return ['date', 'business', 'location', 'reference_no', 'category', 'payee', 'amount', 'status'];
    }

    public function query(array $filters = [])
    {
        return collect();
    }
}
