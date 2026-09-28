<?php
namespace Modules\EggManagement\Services;

use Illuminate\Support\Facades\DB;

class DashboardService
{
    protected $context;

    public function __construct(EggContext $context)
    {
        $this->context = $context;
    }

    protected function db()
    {
        return DB::connection(config('egg.connection'));
    }

    protected function scope($query, $location, $store)
    {
        if ($location !== 'all' && $location !== null && $location !== '') {
            $query->where('location_id', $location);
        }
        if ($store !== 'all' && $store !== null && $store !== '') {
            $query->where('store_id', $store);
        }

        return $query;
    }

    public function metrics($from, $to, $location = 'all', $store = 'all')
    {
        $businessId = $this->context->businessId();
        $fromDate = $from->toDateString();
        $toDate = $to->toDateString();

        $production = $this->scope(
            $this->db()->table('egg_collections')
                ->where('business_id', $businessId)
                ->whereNull('deleted_at')
                ->whereBetween('collection_date', [$fromDate, $toDate]),
            $location,
            $store
        );

        $stock = $this->scope(
            $this->db()->table('egg_stock_lots')
                ->where('business_id', $businessId)
                ->whereNull('deleted_at'),
            $location,
            $store
        );

        $sales = $this->scope(
            $this->db()->table('egg_sales')
                ->where('business_id', $businessId)
                ->whereNull('deleted_at')
                ->whereBetween('sale_date', [$fromDate, $toDate]),
            $location,
            $store
        );

        $activeStockLots = (clone $stock)->where('available_pieces', '>', 0)->count();
        $pendingIntegrations = $this->db()->table('egg_integration_outbox')
            ->where('business_id', $businessId)
            ->whereNull('deleted_at')
            ->where('status', 'pending')
            ->count();

        return [
            'production_pieces' => (int) (clone $production)->sum('total_pieces'),
            'good_pieces' => (int) (clone $production)->sum('good_pieces'),
            'available_stock' => (int) (clone $stock)->sum('available_pieces'),
            'sales_total' => (float) (clone $sales)->sum('total'),
            'sales_count' => (int) (clone $sales)->count(),
            'active_stock_lots' => (int) $activeStockLots,
            'pending_integrations' => (int) $pendingIntegrations,
        ];
    }

    public function recentActivity($from, $to, $location = 'all', $store = 'all', $limit = 8)
    {
        $businessId = $this->context->businessId();
        $fromDate = $from->toDateString();
        $toDate = $to->toDateString();
        $perType = max(4, (int) $limit);

        $collections = $this->scope(
            $this->db()->table('egg_collections')
                ->select(['collection_date as activity_date', 'collection_no as reference', 'total_pieces as value', 'status', 'created_at'])
                ->where('business_id', $businessId)
                ->whereNull('deleted_at')
                ->whereBetween('collection_date', [$fromDate, $toDate]),
            $location,
            $store
        )->orderByDesc('collection_date')->orderByDesc('id')->limit($perType)->get()
            ->map(function ($row) {
                return [
                    'sort_date' => (string) ($row->created_at ?: $row->activity_date),
                    'date' => (string) $row->activity_date,
                    'type' => 'Collection',
                    'reference' => (string) $row->reference,
                    'display_value' => number_format((int) $row->value) . ' pcs',
                    'status' => (string) $row->status,
                    'theme' => 'blue',
                    'icon' => 'fa fa-circle-o',
                ];
            });

        $sales = $this->scope(
            $this->db()->table('egg_sales')
                ->select(['sale_date as activity_date', 'sale_no as reference', 'total as value', 'status', 'created_at'])
                ->where('business_id', $businessId)
                ->whereNull('deleted_at')
                ->whereBetween('sale_date', [$fromDate, $toDate]),
            $location,
            $store
        )->orderByDesc('sale_date')->orderByDesc('id')->limit($perType)->get()
            ->map(function ($row) {
                return [
                    'sort_date' => (string) ($row->created_at ?: $row->activity_date),
                    'date' => (string) $row->activity_date,
                    'type' => 'Sale',
                    'reference' => (string) $row->reference,
                    'display_value' => number_format((float) $row->value, 4),
                    'status' => (string) $row->status,
                    'theme' => 'purple',
                    'icon' => 'fa fa-shopping-cart',
                ];
            });

        $purchases = $this->scope(
            $this->db()->table('egg_purchases')
                ->select(['purchase_date as activity_date', 'purchase_no as reference', 'total as value', 'status', 'created_at'])
                ->where('business_id', $businessId)
                ->whereNull('deleted_at')
                ->whereBetween('purchase_date', [$fromDate, $toDate]),
            $location,
            $store
        )->orderByDesc('purchase_date')->orderByDesc('id')->limit($perType)->get()
            ->map(function ($row) {
                return [
                    'sort_date' => (string) ($row->created_at ?: $row->activity_date),
                    'date' => (string) $row->activity_date,
                    'type' => 'Purchase',
                    'reference' => (string) $row->reference,
                    'display_value' => number_format((float) $row->value, 4),
                    'status' => (string) $row->status,
                    'theme' => 'orange',
                    'icon' => 'fa fa-truck',
                ];
            });

        return $collections
            ->concat($sales)
            ->concat($purchases)
            ->sortByDesc('sort_date')
            ->take((int) $limit)
            ->values();
    }
}
