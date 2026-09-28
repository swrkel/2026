<?php

namespace Modules\DistributionNew\Services\SuperAdmin;

use Illuminate\Support\Facades\DB;

class BusinessLimitUsageService
{
    public function usage(int $businessId): array
    {
        return [
            'vehicles' => DB::table('disnew_vehicles')->where('business_id', $businessId)->count(),
            'sales_reps' => DB::table('disnew_sales_reps')->where('business_id', $businessId)->count(),
            'territories' => DB::table('disnew_territories')->where('business_id', $businessId)->count(),
            'routes' => DB::table('disnew_routes')->where('business_id', $businessId)->count(),
            'warehouses' => DB::table('disnew_warehouses')->where('business_id', $businessId)->count(),
            'active_orders' => DB::table('disnew_sales_orders')->where('business_id', $businessId)->whereNotIn('status', ['closed','cancelled'])->count(),
        ];
    }
}
