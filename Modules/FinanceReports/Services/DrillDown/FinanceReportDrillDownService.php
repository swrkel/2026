<?php

namespace Modules\FinanceReports\Services\DrillDown;

class FinanceReportDrillDownService
{
    public function trail(array $node): array
    {
        return [
            'statement' => $node['statement'] ?? null,
            'group' => $node['group'] ?? null,
            'account_id' => $node['account_id'] ?? null,
            'voucher_id' => $node['voucher_id'] ?? null,
            'transaction_id' => $node['transaction_id'] ?? null,
            'mode' => 'read_only',
        ];
    }
}
