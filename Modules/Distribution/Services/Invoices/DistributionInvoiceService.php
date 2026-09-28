<?php

namespace Modules\Distribution\Services\Invoices;

use Modules\Distribution\Entities\DistributionInvoice;
use Modules\Distribution\Entities\DistributionInvoiceLine;

/**
 * Distribution-owned invoice service seam.
 *
 * Existing invoice functionality is preserved. This service gives the module a
 * single internal place for invoice lookup and totals so future maintenance no
 * longer needs to call main ERP controllers/helpers directly.
 */
class DistributionInvoiceService
{
    public function queryForBusiness(int $businessId)
    {
        return DistributionInvoice::where('business_id', $businessId);
    }

    public function findForBusiness(int $businessId, int $invoiceId): ?DistributionInvoice
    {
        return $this->queryForBusiness($businessId)->find($invoiceId);
    }

    public function findForBusinessOrFail(int $businessId, int $invoiceId): DistributionInvoice
    {
        return $this->queryForBusiness($businessId)->findOrFail($invoiceId);
    }

    public function linesForInvoice(int $invoiceId)
    {
        return DistributionInvoiceLine::where('invoice_id', $invoiceId)->get();
    }

    public function paymentSummary(DistributionInvoice $invoice): array
    {
        return [
            'cash' => (float) ($invoice->payment_cash ?? 0),
            'card' => (float) ($invoice->payment_card ?? 0),
            'cheque' => (float) ($invoice->payment_cheque ?? 0),
            'credit' => (float) ($invoice->payment_credit ?? 0),
            'total_paid' => (float) (($invoice->payment_cash ?? 0) + ($invoice->payment_card ?? 0) + ($invoice->payment_cheque ?? 0) + ($invoice->payment_credit ?? 0)),
        ];
    }
}
