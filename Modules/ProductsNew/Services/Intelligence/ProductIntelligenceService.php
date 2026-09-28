<?php
namespace Modules\ProductsNew\Services\Intelligence;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\ProductsNew\Entities\ProductsNewProduct;

class ProductIntelligenceService
{
    public function dashboard(array $filters = []): array
    {
        $businessId = $filters['business_id'] ?? session('business.id');
        $base = ProductsNewProduct::query()->when($businessId, fn($q) => $q->where('business_id', $businessId));

        $total = (clone $base)->count();
        $inactive = Schema::hasColumn('products', 'not_for_selling')
            ? (clone $base)->where('not_for_selling', 1)->count()
            : 0;
        $withoutSku = Schema::hasColumn('products', 'sku')
            ? (clone $base)->where(function($q){ $q->whereNull('sku')->orWhere('sku',''); })->count()
            : $total;

        // Products New intentionally uses the existing tenant `products` master table.
        // Media/meta tables are module-owned and may be absent during a partial rollout,
        // so these KPI queries must remain safe until the full module SQL is installed.
        if (Schema::hasTable('products_new_media')) {
            $withoutImage = DB::table('products as p')
                ->when($businessId, fn($q) => $q->where('p.business_id', $businessId))
                ->leftJoin('products_new_media as m', function ($join) use ($businessId) {
                    $join->on('m.product_id', '=', 'p.id');
                    if ($businessId && Schema::hasColumn('products_new_media', 'business_id')) {
                        $join->where('m.business_id', '=', $businessId);
                    }
                    if (Schema::hasColumn('products_new_media', 'deleted_at')) {
                        $join->whereNull('m.deleted_at');
                    }
                })
                ->whereNull('m.id')
                ->distinct()
                ->count('p.id');
        } else {
            $withoutImage = $total;
        }

        if (Schema::hasTable('products_new_product_meta')) {
            $withLowHealth = DB::table('products as p')
                ->when($businessId, fn($q) => $q->where('p.business_id', $businessId))
                ->leftJoin('products_new_product_meta as meta', 'meta.product_id', '=', 'p.id')
                ->where(function($q){
                    $q->whereNull('meta.health_score')->orWhere('meta.health_score', '<', 70);
                })->count();
        } else {
            $withLowHealth = $total;
        }

        $duplicateOpen = Schema::hasTable('products_new_duplicate_reviews')
            ? DB::table('products_new_duplicate_reviews')
                ->where('status','open')
                ->when($businessId, fn($q) => $q->where('business_id', $businessId))
                ->count()
            : 0;

        $statusQuery = DB::table('products')
            ->when($businessId, fn($q) => $q->where('business_id', $businessId));

        if (Schema::hasColumn('products', 'products_new_status')) {
            $fallback = Schema::hasColumn('products', 'not_for_selling')
                ? "IF(not_for_selling = 1, 'inactive', 'active')"
                : "'active'";
            $statusExpression = "COALESCE(products_new_status, {$fallback})";
        } elseif (Schema::hasColumn('products', 'not_for_selling')) {
            $statusExpression = "IF(not_for_selling = 1, 'inactive', 'active')";
        } else {
            $statusExpression = "'active'";
        }

        $statusCounts = $statusQuery
            ->select(DB::raw($statusExpression . ' as status_name'), DB::raw('count(*) as total'))
            ->groupBy(DB::raw($statusExpression))
            ->pluck('total','status_name')->toArray();

        return compact('total','inactive','withoutSku','withoutImage','withLowHealth','duplicateOpen','statusCounts');
    }

    public function healthItems(int $limit = 50, ?int $businessId = null)
    {
        $businessId = $businessId ?: session('business.id');
        $query = DB::table('products as p')
            ->when($businessId, fn($q)=>$q->where('p.business_id',$businessId));

        if (Schema::hasTable('products_new_product_meta')) {
            $query->leftJoin('products_new_product_meta as meta', 'meta.product_id', '=', 'p.id');
            $health = 'COALESCE(meta.health_score, 0)';
        } else {
            $health = '0';
        }

        $select = ['p.id'];
        foreach (['name','sku','type','not_for_selling'] as $column) {
            if (Schema::hasColumn('products', $column)) {
                $select[] = 'p.' . $column;
            }
        }
        $select[] = DB::raw($health . ' as health_score');

        return $query
            ->select($select)
            ->orderByRaw($health . ' ASC')
            ->limit($limit)->get();
    }
}
