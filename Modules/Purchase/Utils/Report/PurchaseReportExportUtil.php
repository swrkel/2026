<?php

namespace Modules\Purchase\Utils\Report;

class PurchaseReportExportUtil
{
    public function headings(): array
    {
        return ['Date', 'Reference No', 'Supplier', 'Status', 'Payment Status', 'Total'];
    }
}
