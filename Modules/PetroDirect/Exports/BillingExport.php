<?php
namespace Modules\PetroDirect\Exports;
class BillingExport extends BaseExport
{
    public function __construct($query)
    {
        parent::__construct($query, ['Invoice No', 'Reference No', 'Date', 'Customer', 'Location', 'Final Total', 'Payment Status'], ['invoice_no', 'ref_no', 'transaction_date', 'customer_name', 'location_name', 'final_total', 'payment_status']);
    }
}
