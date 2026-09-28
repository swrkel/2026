<?php
namespace Modules\DistributionNew\Services;

use Illuminate\Support\Facades\DB;

class DisnewDashboardService
{
    public function summary(int $businessId, ?int $locationId = null): array
    {
        $where = ['business_id' => $businessId];
        if ($locationId) { $where['location_id'] = $locationId; }
        return [
            'today_orders' => DB::table('disnew_sales_orders')->where($where)->whereDate('created_at', today())->count(),
            'pending_orders' => DB::table('disnew_sales_orders')->where($where)->whereIn('status', ['draft','pending_approval'])->count(),
            'loading_today' => DB::table('disnew_loadings')->where($where)->whereDate('created_at', today())->count(),
            'vehicles_on_road' => DB::table('disnew_trips')->where($where)->whereIn('status', ['loaded','dispatched'])->count(),
            'deliveries_pending' => DB::table('disnew_deliveries')->where($where)->whereIn('status', ['pending','in_progress'])->count(),
            'completed_deliveries' => DB::table('disnew_deliveries')->where($where)->where('status', 'completed')->whereDate('updated_at', today())->count(),
            'daily_collections' => DB::table('disnew_collections')->where($where)->whereDate('collection_date', today())->sum('amount'),
            'returns' => DB::table('disnew_returns')->where($where)->whereDate('created_at', today())->count(),
        ];
    }
}
