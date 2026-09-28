<?php

namespace Modules\Distribution\Services\Invoices;

use Illuminate\Support\Collection;
use Modules\Distribution\Entities\DistributionInvoice;
use Modules\Distribution\Entities\DistributionTransaction;
use Modules\Distribution\Entities\DistributionTransactionPayment;

class DistributionInvoicePaymentService
{
    public function findInvoice(int $businessId, int $invoiceId): DistributionInvoice
    {
        return DistributionInvoice::where('business_id', $businessId)->findOrFail($invoiceId);
    }

    public function paymentRows(DistributionInvoice $invoice): Collection
    {
        $rows = collect();
        $transaction = DistributionTransaction::where('business_id', $invoice->business_id)
            ->where('invoice_no', $invoice->invoice_no)
            ->first();

        if ($transaction) {
            $payments = DistributionTransactionPayment::where('transaction_id', $transaction->id)
                ->orderBy('paid_on')
                ->get();

            foreach ($payments as $payment) {
                $rows->push([
                    'method' => ucfirst((string) ($payment->method ?? 'payment')),
                    'amount' => (float) ($payment->amount ?? 0),
                    'paid_on' => $payment->paid_on,
                    'payment_ref_no' => $payment->payment_ref_no ?? null,
                    'note' => $payment->note ?? null,
                ]);
            }
        }

        if ($rows->isEmpty()) {
            $this->pushInvoiceAmount($rows, 'Cash', (float) ($invoice->payment_cash ?? 0), $invoice);
            $this->pushInvoiceAmount($rows, 'Card', (float) ($invoice->payment_card ?? 0), $invoice);
            $this->pushInvoiceAmount($rows, 'Cheque', (float) ($invoice->payment_cheque ?? 0), $invoice);
            $this->pushInvoiceAmount($rows, 'Credit', (float) ($invoice->payment_credit ?? 0), $invoice);
        }

        return $rows->filter(fn ($row) => (float) ($row['amount'] ?? 0) > 0)->values();
    }

    public function totalPaid(Collection $rows): float
    {
        return (float) $rows->sum('amount');
    }

    public function balanceDue(DistributionInvoice $invoice, float $paid): float
    {
        return max(0, (float) ($invoice->grand_total ?? 0) - $paid);
    }

    private function pushInvoiceAmount(Collection $rows, string $method, float $amount, DistributionInvoice $invoice): void
    {
        if ($amount <= 0) {
            return;
        }

        $rows->push([
            'method' => $method,
            'amount' => $amount,
            'paid_on' => $invoice->date ?? $invoice->created_at,
            'payment_ref_no' => $invoice->invoice_no,
            'note' => null,
        ]);
    }
}
