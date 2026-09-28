<?php
namespace Modules\BeautySaloons\Services;

use Illuminate\Support\Facades\DB;
use Modules\BeautySaloons\Entities\BeautyStockMovement;

class BeautyInventoryService
{
    public function currentStock(int $productId, ?int $locationId = null): float
    {
        $query = BeautyStockMovement::where('product_id', $productId);
        if ($locationId) { $query->where('business_location_id', $locationId); }
        return (float) $query->select(DB::raw("COALESCE(SUM(CASE WHEN movement_type IN ('opening','purchase','return_in','adjust_in') THEN quantity ELSE -quantity END),0) as stock"))->value('stock');
    }

    public function recordMovement(array $data): BeautyStockMovement
    {
        return BeautyStockMovement::create($data);
    }
}
