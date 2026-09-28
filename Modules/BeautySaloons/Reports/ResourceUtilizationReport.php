<?php

namespace Modules\BeautySaloons\Reports;

class ResourceUtilizationReport
{
    public function headings(): array
    {
        return ['Branch', 'Resource', 'Booked Hours', 'Available Hours', 'Utilization %'];
    }
}
