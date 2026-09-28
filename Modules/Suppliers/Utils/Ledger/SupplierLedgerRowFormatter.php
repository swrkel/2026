<?php

namespace Modules\Suppliers\Utils\Ledger;

use Modules\Suppliers\Entities\SupplierTransaction;
use Modules\Suppliers\Entities\SupplierTransactionPayment;
use Illuminate\Support\Carbon;

class SupplierLedgerRowFormatter
{
    public function purchase(SupplierTransaction $transaction): array
    {
        $isReturn = $transaction->type === 'purchase_return';

        return [
            'sort_id' => 'T' . $transaction->id,
            'date' => Carbon::parse($transaction->transaction_date)->format('Y-m-d'),
            'datetime' => Carbon::parse($transaction->transaction_date)->format('Y-m-d H:i'),
            'type' => $isReturn ? 'Purchase Return' : 'Purchase',
            'reference' => $transaction->ref_no ?: $transaction->invoice_no,
            'location' => optional($transaction->location)->name,
            'description' => $isReturn ? 'Supplier purchase return' : 'Supplier purchase / opening balance',
            'debit' => $isReturn ? 0 : (float) $transaction->final_total,
            'credit' => $isReturn ? (float) $transaction->final_total : 0,
            'balance' => 0,
        ];
    }

    public function payment(SupplierTransactionPayment $payment): array
    {
        return [
            'sort_id' => 'P' . $payment->id,
            'date' => Carbon::parse($payment->paid_on)->format('Y-m-d'),
            'datetime' => Carbon::parse($payment->paid_on)->format('Y-m-d H:i'),
            'type' => 'Payment',
            'reference' => $payment->payment_ref_no,
            'location' => optional(optional($payment->transaction)->location)->name,
            'description' => $payment->note ?: 'Supplier payment',
            'debit' => 0,
            'credit' => (float) $payment->amount,
            'balance' => 0,
        ];
    }
}
