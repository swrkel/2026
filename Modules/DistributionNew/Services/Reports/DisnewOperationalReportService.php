<?php

namespace Modules\DistributionNew\Services\Reports;

use Illuminate\Support\Facades\DB;

class DisnewOperationalReportService
{
    public function dailySummary(int $businessId, array $filters = []): array
    {
        $date = $filters['date'] ?? now()->toDateString();
        return [
            'orders' => DB::table('disnew_sales_orders')->where('business_id',$businessId)->whereDate('order_date',$date)->count(),
            'invoices' => DB::table('disnew_sales_invoices')->where('business_id',$businessId)->whereDate('invoice_date',$date)->count(),
            'loaded_value' => DB::table('disnew_loadings')->where('business_id',$businessId)->whereDate('loading_date',$date)->sum('total_amount'),
            'collections' => DB::table('disnew_collections')->where('business_id',$businessId)->whereDate('collection_date',$date)->sum('amount'),
            'settlements' => DB::table('disnew_settlements')->where('business_id',$businessId)->whereDate('settlement_date',$date)->count(),
        ];
    }

    public function vehicleStockBalance(int $businessId, array $filters = [])
    {
        return DB::table('disnew_vehicle_stocks')
            ->where('business_id',$businessId)
            ->when($filters['vehicle_id'] ?? null, fn($q,$v)=>$q->where('vehicle_id',$v))
            ->select('vehicle_id','product_id', DB::raw('SUM(qty) as qty'))
            ->groupBy('vehicle_id','product_id')
            ->orderBy('vehicle_id')
            ->get();
    }
}
