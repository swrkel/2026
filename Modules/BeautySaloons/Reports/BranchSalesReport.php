<?php

namespace Modules\BeautySaloons\Reports;

class BranchSalesReport
{
    public function headings(): array
    {
        return ['Branch', 'Appointments', 'Service Sales', 'Product Sales', 'Total'];
    }
}
