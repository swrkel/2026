<?php

namespace Modules\RestaurantNew\Reports;

use Illuminate\Support\Facades\DB;
use Modules\RestaurantNew\Entities\RestaurantNewDeliveryOrder;

class DeliveryReportService
{
    public function summary(array $filters = [])
    {
        $query = RestaurantNewDeliveryOrder::query()
            ->select([
                'delivery_status',
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('SUM(delivery_charge) as total_delivery_charge'),
                DB::raw('SUM(cod_amount) as total_cod'),
                DB::raw('SUM(card_amount) as total_card'),
            ])
            ->where('business_id', $filters['business_id'] ?? session('business.id'))
            ->groupBy('delivery_status');

        if (! empty($filters['business_location_id'])) {
            $query->where('business_location_id', $filters['business_location_id']);
        }
        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->get();
    }

    public function riderPerformance(array $filters = [])
    {
        return RestaurantNewDeliveryOrder::query()
            ->select([
                'delivery_rider_id',
                DB::raw('COUNT(*) as total_deliveries'),
                DB::raw('SUM(CASE WHEN delivery_status = "delivered" THEN 1 ELSE 0 END) as delivered_count'),
                DB::raw('SUM(CASE WHEN delivery_status = "cancelled" THEN 1 ELSE 0 END) as cancelled_count'),
                DB::raw('SUM(cod_amount + card_amount + delivery_charge) as collected_total'),
            ])
            ->where('business_id', $filters['business_id'] ?? session('business.id'))
            ->groupBy('delivery_rider_id')
            ->get();
    }
}
