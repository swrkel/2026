<?php

namespace Modules\RestaurantNew\Reports;

use Modules\RestaurantNew\Entities\RestaurantNewIngredientStock;
use Modules\RestaurantNew\Entities\RestaurantNewStockMovement;
use Modules\RestaurantNew\Entities\RestaurantNewWastage;

class InventoryReportService
{
    public function currentStock(int $businessId, ?int $locationId = null)
    {
        return RestaurantNewIngredientStock::with('ingredient')
            ->where('business_id', $businessId)
            ->when($locationId, fn ($query) => $query->where('location_id', $locationId))
            ->orderBy('ingredient_id')
            ->get();
    }

    public function movements(int $businessId, ?int $locationId = null, ?string $from = null, ?string $to = null)
    {
        return RestaurantNewStockMovement::where('business_id', $businessId)
            ->when($locationId, fn ($query) => $query->where('location_id', $locationId))
            ->when($from, fn ($query) => $query->whereDate('created_at', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('created_at', '<=', $to))
            ->latest()
            ->get();
    }

    public function wastage(int $businessId, ?int $locationId = null, ?string $from = null, ?string $to = null)
    {
        return RestaurantNewWastage::where('business_id', $businessId)
            ->when($locationId, fn ($query) => $query->where('location_id', $locationId))
            ->when($from, fn ($query) => $query->whereDate('wastage_date', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('wastage_date', '<=', $to))
            ->latest()
            ->get();
    }
}
