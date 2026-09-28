<?php
namespace Modules\BeautySaloons\Reports;

use Modules\BeautySaloons\Entities\BeautyBill;

class BeautyPosDailySalesReport
{
    public function totals(string $date): array
    {
        return [
            'record_count' => BeautyBill::whereDate('bill_date', $date)->count(),
            'net_total' => BeautyBill::whereDate('bill_date', $date)->sum('net_total'),
            'paid_amount' => BeautyBill::whereDate('bill_date', $date)->sum('paid_amount'),
        ];
    }
}
