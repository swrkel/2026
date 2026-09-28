<?php

namespace Modules\PetroPD\Services\SettlementRewrite;

use Illuminate\Support\Facades\Log;
use Modules\PetroPD\Services\SettlementRewrite\Posting\PdSettlementLedgerPoster;
use Modules\PetroPD\Services\SettlementRewrite\Posting\PdSettlementPaymentAccountPoster;
use Modules\PetroPD\Services\SettlementRewrite\Posting\PdSettlementStockPoster;

/**
 * Posting bridge for the rewrite.
 *
 * The current ERP already has mature posting code in PetroPDSettlementController.
 * This bridge provides a single seam where payment account books, stock account books,
 * sales income, COGS, customer ledgers, pump operator ledgers and stock transactions
 * must be called from one saved settlement snapshot.
 */
class PdSettlementPostingBridge
{
    public function __construct(
        protected PdSettlementPostingContextBuilder $contextBuilder,
        protected PdSettlementConsistencyGuard $consistencyGuard,
        protected PdSettlementPaymentAccountPoster $paymentAccountPoster,
        protected PdSettlementStockPoster $stockPoster,
        protected PdSettlementLedgerPoster $ledgerPoster
    ) {
    }

    public function post(array $context): void
    {
        $postingContext = $this->contextBuilder->build($context);
        $this->consistencyGuard->assertConsistent($postingContext);

        // PD-SETTLEMENT-REWRITE-010: all posting categories now pass through
        // dedicated rewrite poster seams using the same normalized context.
        $this->paymentAccountPoster->post($postingContext);
        $this->stockPoster->post($postingContext);
        $this->ledgerPoster->post($postingContext);

        Log::info('PD settlement rewrite posting bridge normalized context', [
            'settlement_id' => $postingContext['settlement_id'] ?? null,
            'settlement_no' => $postingContext['settlement_no'] ?? null,
            'business_id' => $postingContext['business_id'] ?? null,
            'shift_id' => $postingContext['shift_id'] ?? null,
            'payment_details_total' => $postingContext['payment_details_total'] ?? 0,
            'meter_sales_total' => $postingContext['meter_sales_total'] ?? 0,
            'other_sales_total' => $postingContext['other_sales_total'] ?? 0,
        ]);
    }
}
