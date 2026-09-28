<?php

namespace Modules\BeautySaloons\Reports;

class ServiceSalesReport
{
    public function columns(): array
    {
        return ['Date', 'Service', 'Category', 'Staff', 'Customer', 'Amount', 'Commission'];
    }
}
