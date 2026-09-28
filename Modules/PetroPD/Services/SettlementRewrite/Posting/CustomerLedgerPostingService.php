<?php

namespace Modules\PetroPD\Services\SettlementRewrite\Posting;

use Modules\PetroPD\Services\SettlementRewrite\Ledger\WalkInCustomerLedgerPairService;

class CustomerLedgerPostingService
{
    public function __construct(protected WalkInCustomerLedgerPairService $walkInLedgerPair) {}

    public function post(PdSettlementPostingPayload $payload, SettlementPostingResult $result): void
    {
        $creditPayments = $payload->payments->filter(function ($payment) {
            $type = strtolower(str_replace(['-', ' '], '_', (string) $payment->payment_type));
            return in_array($type, ['credit', 'credit_sale', 'credit_sales'], true);
        })->values();

        $walkInPairs = 0;
        foreach ($payload->payments as $payment) {
            if ($this->walkInLedgerPair->postForPaymentRow($payment, [
                'business_id' => $payload->businessId,
                'settlement_no' => $payload->settlementNo,
                'operation_date' => $payload->transactionDate,
                'created_by' => $payload->createdBy,
            ])) {
                $walkInPairs++;
            }
        }

        $result->posted('customer_ledgers', [
            'credit_payment_rows' => $creditPayments->count(),
            'walk_in_ledger_pairs_posted_or_verified' => $walkInPairs,
            'walk_in_rule' => 'Walk-In Customer creates debit and immediate credit rows for the same payment amount. This writes to the shared contact ledger so both Contacts and Customers modules show the same ledger.',
        ]);
    }
}
