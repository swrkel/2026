<?php

namespace Modules\RestaurantNew\Reports\Corporate;

class CorporateStatementReport
{
    public function build(array $filters): array
    {
        return [
            'filters' => $filters,
            'summary' => ['invoices' => 0, 'payments' => 0, 'outstanding' => 0],
            'rows' => [],
        ];
    }
}
