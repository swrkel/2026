<?php

namespace Modules\Deposits\Reports;

use Modules\Deposits\Services\DepositSummaryService;

class DepositSummaryReport
{
    public function data(): array
    {
        return app(DepositSummaryService::class)->dashboard();
    }
}
