<?php

namespace Modules\PetroPD\Services\SettlementRewrite\Posting;

use Illuminate\Support\Facades\DB;

class PdSettlementPostingService
{
    public function __construct(
        protected PaymentAccountBookPostingService $paymentAccounts,
        protected StockAccountPostingService $stockAccounts,
        protected SalesIncomePostingService $salesIncome,
        protected CogsPostingService $cogs,
        protected CustomerLedgerPostingService $customerLedgers,
        protected PumpOperatorLedgerPostingService $operatorLedgers,
        protected StockTransactionPostingService $stockTransactions,
    ) {}

    public function post(PdSettlementPostingPayload $payload): SettlementPostingResult
    {
        $result = new SettlementPostingResult();

        DB::transaction(function () use ($payload, $result) {
            $this->paymentAccounts->post($payload, $result);
            $this->stockAccounts->post($payload, $result);
            $this->salesIncome->post($payload, $result);
            $this->cogs->post($payload, $result);
            $this->customerLedgers->post($payload, $result);
            $this->operatorLedgers->post($payload, $result);
            $this->stockTransactions->post($payload, $result);
        });

        return $result;
    }
}
