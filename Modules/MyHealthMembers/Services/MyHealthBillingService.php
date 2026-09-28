<?php

namespace Modules\MyHealthMembers\Services;

use Modules\MyHealthMembers\Entities\MyHealthBillingInvoice;

class MyHealthBillingService
{
    public function refreshInvoiceTotals(MyHealthBillingInvoice $invoice): MyHealthBillingInvoice
    {
        $gross = (float) $invoice->items()->sum('line_total');
        $paid = (float) $invoice->payments()->sum('amount');
        $discount = (float) $invoice->discount_amount;
        $insurance = (float) $invoice->insurance_amount;
        $balance = max($gross - $discount - $insurance - $paid, 0);
        $status = $balance <= 0 ? 'paid' : ($paid > 0 ? 'partially_paid' : 'unpaid');

        $invoice->update([
            'gross_amount' => $gross,
            'paid_amount' => $paid,
            'balance_amount' => $balance,
            'status' => $status,
        ]);

        return $invoice->fresh(['items', 'payments']);
    }
}
