<?php

namespace Modules\AutoService\Services;

class AutoServiceSecurityService
{
    public function customerCanViewCurrentInvoice($businessId): bool
    {
        return (bool) config('autoservice.customer_portal.allow_customer_current_invoice', false);
    }

    public function customerCanDownloadInvoicePdf($businessId): bool
    {
        return (bool) config('autoservice.customer_portal.allow_customer_invoice_pdf', false);
    }

    public function safeCustomerInvoiceFields(): array
    {
        return ['invoice_no','invoice_date','status','total_amount','paid_amount','balance_amount','job_no','vehicle_no'];
    }
}
