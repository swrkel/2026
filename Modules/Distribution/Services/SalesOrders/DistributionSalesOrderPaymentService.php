<?php

namespace Modules\Distribution\Services\SalesOrders;

use Illuminate\Support\Collection;
use Modules\Distribution\Entities\DistributionSalesOrder;

/**
 * Distribution-owned payment helper for Sales Orders.
 *
 * This keeps Sales Order payment display logic inside the Distribution module,
 * so controllers/views do not depend on the main TransactionPaymentController
 * or other module controllers for viewing payment details.
 */
class DistributionSalesOrderPaymentService
{
    public function findSalesOrder(int $businessId, int $salesOrderId): DistributionSalesOrder
    {
        return DistributionSalesOrder::where('business_id', $businessId)
            ->with(['customer', 'invoices'])
            ->findOrFail($salesOrderId);
    }

    public function paymentRows(DistributionSalesOrder $salesOrder): Collection
    {
        $rows = collect();

        foreach ($salesOrder->invoices as $invoice) {
            $invoiceNo = $invoice->invoice_no ?? null;
            $invoiceDate = optional($invoice->created_at)->format('Y-m-d');

            $this->pushRow($rows, 'Cash', (float) ($invoice->payment_cash ?? 0), $invoiceNo, $invoiceDate);
            $this->pushRow($rows, 'Card', (float) ($invoice->payment_card ?? 0), $invoiceNo, $invoiceDate);
            $this->pushRow($rows, 'Cheque', (float) ($invoice->payment_cheque ?? 0), $invoiceNo, $invoiceDate);
            $this->pushRow($rows, 'Credit', (float) ($invoice->payment_credit ?? 0), $invoiceNo, $invoiceDate);
        }

        if ($rows->isEmpty()) {
            $this->pushRow($rows, 'Cash', (float) ($salesOrder->payment_cash ?? 0));
            $this->pushRow($rows, 'Card', (float) ($salesOrder->payment_card ?? 0));
            $this->pushRow($rows, 'Cheque', (float) ($salesOrder->payment_cheque ?? 0));
            $this->pushRow($rows, 'Credit', (float) ($salesOrder->payment_credit ?? 0));
        }

        return $rows->filter(function (array $row) {
            return (float) $row['amount'] > 0;
        })->values();
    }

    public function totalPaid(Collection $rows): float
    {
        return (float) $rows->sum('amount');
    }

    public function balanceDue(DistributionSalesOrder $salesOrder, ?float $paidTotal = null): float
    {
        if ($paidTotal === null) {
            $paidTotal = $this->totalPaid($this->paymentRows($salesOrder));
        }

        return max(0, (float) ($salesOrder->grand_total ?? 0) - (float) $paidTotal);
    }

    private function pushRow(Collection $rows, string $method, float $amount, ?string $invoiceNo = null, ?string $invoiceDate = null): void
    {
        if ($amount <= 0) {
            return;
        }

        $rows->push([
            'method' => $method,
            'amount' => $amount,
            'invoice_no' => $invoiceNo,
            'invoice_date' => $invoiceDate,
        ]);
    }
}
