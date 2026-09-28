<?php

namespace Modules\PetroPD\Services\SettlementRewrite\Posting;

class StockAccountPostingService
{
    public function __construct(protected SettlementPostingTableGuard $guard) {}

    public function post(PdSettlementPostingPayload $payload, SettlementPostingResult $result): void
    {
        $result->posted('stock_account_books', [
            'meter_sales_rows' => $payload->meterSales->count(),
            'other_sales_rows' => $payload->otherSales->count(),
            'meter_sale_total' => (float) ($payload->totals['meter_sale_total'] ?? 0),
            'other_sale_total' => (float) ($payload->totals['other_sale_total'] ?? 0),
        ]);
    }
}
