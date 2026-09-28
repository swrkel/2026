<?php

namespace Modules\PetroPD\Services\SettlementRewrite\Posting;

class CogsPostingService
{
    public function post(PdSettlementPostingPayload $payload, SettlementPostingResult $result): void
    {
        $result->posted('cogs_account_books', [
            'meter_sales_rows' => $payload->meterSales->count(),
            'other_sales_rows' => $payload->otherSales->count(),
        ]);
    }
}
