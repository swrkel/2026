<?php

namespace Modules\EnterpriseFramework\Services\DrillDown;

class DrillDownEngine
{
    public function path(array $source): array
    {
        return [
            'dashboard',
            'report',
            $source['group'] ?? 'summary',
            $source['ledger'] ?? 'ledger',
            $source['voucher'] ?? 'voucher',
            $source['transaction'] ?? 'transaction',
            $source['module'] ?? 'source_module',
        ];
    }
}
