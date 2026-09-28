<?php

namespace Modules\DistributionNew\Reports;

use Illuminate\Support\Facades\DB;
use Modules\DistributionNew\Utils\DisnewTenantUtil;

class DisnewSalesOrderReport
{
    public function summary(array $filters = [])
    {
        return DB::table('disnew_sales_orders')
            ->where('business_id', DisnewTenantUtil::businessId())
            ->when($filters['from'] ?? null, fn ($q, $from) => $q->whereDate('order_date', '>=', $from))
            ->when($filters['to'] ?? null, fn ($q, $to) => $q->whereDate('order_date', '<=', $to))
            ->selectRaw('status, COUNT(*) as record_count, SUM(grand_total) as total_amount')
            ->groupBy('status')
            ->get();
    }
}
