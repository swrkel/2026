<?php
namespace Modules\ProductsNew\Services\Intelligence;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\ProductsNew\Entities\ProductsNewAvailabilitySnapshot;

class ProductAvailabilityService
{
    public function matrix(array $filters = [])
    {
        return DB::table('products_new_availability_snapshots as s')
            ->leftJoin('products as p','p.id','=','s.product_id')
            ->leftJoin('business_locations as l','l.id','=','s.location_id')
            ->select('s.*','p.name as product_name','p.sku','l.name as location_name')
            ->when($filters['business_id'] ?? session('business.id'), fn($q,$businessId)=>$q->where('s.business_id',$businessId))
            ->when($filters['location_id'] ?? null, fn($q,$locationId)=>$q->where('s.location_id',$locationId))
            ->when($filters['product_id'] ?? null, fn($q,$productId)=>$q->where('s.product_id',$productId))
            ->orderBy('p.name')->orderBy('l.name')->paginate($filters['per_page'] ?? 50);
    }

    public function refresh(?int $businessId = null): int
    {
        $businessId = $businessId ?: session('business.id');
        if (!Schema::hasTable('products_new_variation_location_details')) {
            return 0;
        }
        $reservedSql = Schema::hasColumn('products_new_variation_location_details', 'reserved_qty') ? 'COALESCE(vld.reserved_qty,0)' : '0';
        $reorderSql = Schema::hasColumn('products_new_variation_location_details', 'reorder_level') ? 'COALESCE(vld.reorder_level,0)' : '0';
        $rows = DB::table('products_new_variation_location_details as vld')
            ->leftJoin('products as p','p.id','=','vld.product_id')
            ->select('vld.product_id','vld.variation_id','vld.location_id','p.business_id',DB::raw('COALESCE(vld.qty_available,0) as current_stock'),DB::raw($reservedSql.' as reserved_qty'),DB::raw($reorderSql.' as reorder_level'))
            ->where('p.business_id',$businessId)->get();
        $count = 0;
        foreach ($rows as $row) {
            ProductsNewAvailabilitySnapshot::updateOrCreate([
                'business_id'=>$row->business_id,
                'product_id'=>$row->product_id,
                'variation_id'=>$row->variation_id,
                'location_id'=>$row->location_id,
            ], [
                'current_stock'=>$row->current_stock,
                'reserved_qty'=>$row->reserved_qty,
                'available_qty'=>max(0, $row->current_stock - $row->reserved_qty),
                'reorder_level'=>$row->reorder_level,
                'snapshot_at'=>now(),
            ]);
            $count++;
        }
        return $count;
    }
}
