<?php

namespace Modules\PetroPD\Services\SettlementRewrite\Posting;

class StockTransactionPostingService
{
    public function post(PdSettlementPostingPayload $payload, SettlementPostingResult $result): void
    {
        $result->posted('stock_transactions', [
            'meter_sales_rows' => $payload->meterSales->count(),
            'other_sales_rows' => $payload->otherSales->count(),
            'source' => 'saved settlement snapshot only',
        ]);
    }
}
