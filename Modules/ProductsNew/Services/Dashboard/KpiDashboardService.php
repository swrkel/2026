<?php

namespace Modules\ProductsNew\Services\Dashboard;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\ProductsNew\Utilities\ProductsNewTenantGuard;

class KpiDashboardService
{
    public function __construct(protected ProductsNewTenantGuard $guard) {}

    public function overview(array $filters = []): array
    {
        $businessId = $this->guard->businessId();
        $locationId = $filters['location_id'] ?? null;

        return [
            'master' => $this->productMasterKpis($businessId),
            'stock' => $this->stockKpis($businessId, $locationId),
            'quality' => $this->qualityKpis($businessId),
            'expiry' => $this->expiryKpis($businessId, $locationId),
            'serial' => $this->serialKpis($businessId),
            'price' => $this->priceKpis($businessId),
            'recent_activity' => $this->recentActivity($businessId),
            'attention' => $this->attentionList($businessId, $locationId),
            'generated_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ];
    }

    protected function productMasterKpis(?int $businessId): array
    {
        $base = DB::table('products');
        if ($businessId) { $base->where('business_id', $businessId); }

        $total = (clone $base)->count();
        $active = (clone $base)->where(function ($q) {
            $q->whereNull('not_for_selling')->orWhere('not_for_selling', 0);
        })->count();

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => max(0, $total - $active),
            'stock_enabled' => (clone $base)->where('enable_stock', 1)->count(),
            'service_items' => (clone $base)->where(function ($q) {
                $q->whereNull('enable_stock')->orWhere('enable_stock', 0);
            })->count(),
            'created_today' => (clone $base)->whereDate('created_at', Carbon::today())->count(),
            'updated_today' => (clone $base)->whereDate('updated_at', Carbon::today())->count(),
        ];
    }

    protected function stockKpis(?int $businessId, $locationId = null): array
    {
        $q = DB::table('variation_location_details as vld')
            ->join('variations as v', 'v.id', '=', 'vld.variation_id')
            ->join('products as p', 'p.id', '=', 'v.product_id');
        if ($businessId) { $q->where('p.business_id', $businessId); }
        if ($locationId) { $q->where('vld.location_id', $locationId); }

        $sum = (clone $q)->selectRaw('COALESCE(SUM(vld.qty_available),0) as qty')->value('qty');
        $negative = (clone $q)->whereRaw('COALESCE(vld.qty_available,0) < 0')->count();
        $low = (clone $q)
            ->whereRaw('COALESCE(p.alert_quantity,0) > 0')
            ->whereRaw('COALESCE(vld.qty_available,0) <= COALESCE(p.alert_quantity,0)')
            ->count();
        $zero = (clone $q)->whereRaw('COALESCE(vld.qty_available,0) = 0')->count();

        return [
            'available_qty' => (float) $sum,
            'low_stock_lines' => $low,
            'negative_stock_lines' => $negative,
            'zero_stock_lines' => $zero,
        ];
    }

    protected function qualityKpis(?int $businessId): array
    {
        $base = DB::table('products as p')->leftJoin('products_new_product_meta as m', 'm.product_id', '=', 'p.id');
        if ($businessId) { $base->where('p.business_id', $businessId); }

        return [
            'without_image' => (clone $base)->where(function ($q) {
                $q->whereNull('p.image')->orWhere('p.image', '');
            })->count(),
            'without_sku' => (clone $base)->where(function ($q) {
                $q->whereNull('p.sku')->orWhere('p.sku', '');
            })->count(),
            'without_category' => (clone $base)->whereNull('p.category_id')->count(),
            'without_brand' => (clone $base)->whereNull('p.brand_id')->count(),
            'low_health' => (clone $base)->whereRaw('COALESCE(m.health_score,0) < 60')->count(),
        ];
    }

    protected function expiryKpis(?int $businessId, $locationId = null): array
    {
        $expired = 0;
        $expiringInThirtyDays = 0;

        if ($this->tableExists('products_new_batches')) {
            // The Products New batch schema uses `expiry_at`.  Keep a legacy
            // fallback for tenant databases that may still contain `expiry_date`.
            $expiryColumn = $this->firstExistingColumn(
                'products_new_batches',
                ['expiry_at', 'expiry_date']
            );

            if ($expiryColumn !== null) {
                $qualifiedExpiryColumn = 'b.' . $expiryColumn;
                $batches = DB::table('products_new_batches as b');

                if ($businessId) { $batches->where('b.business_id', $businessId); }
                if ($locationId) { $batches->where('b.location_id', $locationId); }

                $today = Carbon::today()->toDateString();
                $thirtyDaysFromToday = Carbon::today()->addDays(30)->toDateString();

                $expired = (clone $batches)
                    ->whereNotNull($qualifiedExpiryColumn)
                    ->whereDate($qualifiedExpiryColumn, '<', $today)
                    ->count();

                $expiringInThirtyDays = (clone $batches)
                    ->whereNotNull($qualifiedExpiryColumn)
                    ->whereBetween($qualifiedExpiryColumn, [$today, $thirtyDaysFromToday])
                    ->count();
            }
        }

        $recallOpen = 0;
        if ($this->tableExists('products_new_recalls')) {
            $recalls = DB::table('products_new_recalls');
            if ($businessId) { $recalls->where('business_id', $businessId); }
            $recallOpen = (clone $recalls)->where('status', 'open')->count();
        }

        return [
            'expired' => $expired,
            'expiring_30_days' => $expiringInThirtyDays,
            'recall_open' => $recallOpen,
        ];
    }

    protected function serialKpis(?int $businessId): array
    {
        if (!$this->tableExists('products_new_serial_numbers')) {
            return ['total_serials' => 0, 'available' => 0, 'sold' => 0, 'warranty_active' => 0];
        }
        $serials = DB::table('products_new_serial_numbers');
        if ($businessId) { $serials->where('business_id', $businessId); }

        $warranty = DB::table('products_new_warranties');
        if ($businessId) { $warranty->where('business_id', $businessId); }

        return [
            'total_serials' => (clone $serials)->count(),
            'available' => (clone $serials)->where('status', 'available')->count(),
            'sold' => (clone $serials)->where('status', 'sold')->count(),
            'warranty_active' => $this->tableExists('products_new_warranties') ? (clone $warranty)->where('status', 'active')->count() : 0,
        ];
    }

    protected function priceKpis(?int $businessId): array
    {
        if (!$this->tableExists('products_new_price_history')) {
            return ['changes_today' => 0, 'future_prices' => 0];
        }
        $q = DB::table('products_new_price_history');
        if ($businessId) { $q->where('business_id', $businessId); }
        return [
            'changes_today' => (clone $q)->whereDate('created_at', Carbon::today())->count(),
            'future_prices' => (clone $q)->whereDate('effective_from', '>', Carbon::today())->count(),
        ];
    }

    protected function recentActivity(?int $businessId): array
    {
        if (!$this->tableExists('products_new_timeline')) { return []; }
        $q = DB::table('products_new_timeline as t')
            ->leftJoin('products as p', 'p.id', '=', 't.product_id')
            ->select('t.event', 't.created_at', 'p.name as product_name', 'p.sku')
            ->orderByDesc('t.id')
            ->limit(10);
        if ($businessId) { $q->where('t.business_id', $businessId); }
        return $q->get()->toArray();
    }

    protected function attentionList(?int $businessId, $locationId = null): array
    {
        $low = DB::table('variation_location_details as vld')
            ->join('variations as v', 'v.id', '=', 'vld.variation_id')
            ->join('products as p', 'p.id', '=', 'v.product_id')
            ->select('p.id', 'p.name', 'p.sku', 'vld.qty_available', 'p.alert_quantity')
            ->whereRaw('COALESCE(p.alert_quantity,0) > 0')
            ->whereRaw('COALESCE(vld.qty_available,0) <= COALESCE(p.alert_quantity,0)')
            ->orderBy('vld.qty_available')
            ->limit(8);
        if ($businessId) { $low->where('p.business_id', $businessId); }
        if ($locationId) { $low->where('vld.location_id', $locationId); }

        return [
            'low_stock' => $low->get()->toArray(),
        ];
    }

    public function storeSnapshot(array $payload): int
    {
        $businessId = $this->guard->businessId();
        $userId = $this->guard->userId();
        $overview = $this->overview($payload);
        return DB::table('products_new_dashboard_snapshots')->insertGetId([
            'business_id' => $businessId,
            'location_id' => $payload['location_id'] ?? null,
            'snapshot_date' => Carbon::today()->toDateString(),
            'snapshot_payload' => json_encode($overview),
            'created_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function tableExists(string $table): bool
    {
        try { return DB::getSchemaBuilder()->hasTable($table); } catch (\Throwable $e) { return false; }
    }

    protected function firstExistingColumn(string $table, array $columns): ?string
    {
        try {
            $schema = DB::getSchemaBuilder();

            foreach ($columns as $column) {
                if ($schema->hasColumn($table, $column)) {
                    return $column;
                }
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }
}
