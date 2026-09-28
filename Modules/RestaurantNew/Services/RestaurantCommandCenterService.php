<?php

namespace Modules\RestaurantNew\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\RestaurantNew\Entities\RestaurantNewDashboardAlert;
use Modules\RestaurantNew\Entities\RestaurantNewKpiDailySummary;

class RestaurantCommandCenterService
{
    public function restaurantSummary(int $businessId, ?int $locationId = null): array
    {
        return [
            'gross_sales' => $this->sumTable('restaurant_new_bills', 'total_amount', $businessId, $locationId),
            'net_sales' => $this->sumTable('restaurant_new_bills', 'net_amount', $businessId, $locationId),
            'running_orders' => $this->countTable('restaurant_new_orders', $businessId, $locationId, ['status' => ['open','running','sent_to_kitchen']]),
            'waiting_orders' => $this->countTable('restaurant_new_kitchen_tickets', $businessId, $locationId, ['status' => ['received','waiting']]),
            'ready_orders' => $this->countTable('restaurant_new_kitchen_tickets', $businessId, $locationId, ['status' => ['ready']]),
            'delivery_orders' => $this->countTable('restaurant_new_delivery_orders', $businessId, $locationId, ['status' => ['pending','assigned','out_for_delivery']]),
            'takeaway_orders' => $this->countTable('restaurant_new_orders', $businessId, $locationId, ['order_type' => ['takeaway']]),
            'low_stock_alerts' => $this->countTable('restaurant_new_ingredients', $businessId, $locationId, ['stock_status' => ['low','reorder']]),
            'alerts' => RestaurantNewDashboardAlert::where('business_id', $businessId)->when($locationId, fn($q) => $q->where('location_id', $locationId))->where('is_read', false)->latest()->limit(10)->get(),
        ];
    }

    public function kitchenSummary(int $businessId, ?int $locationId = null): array
    {
        return [
            'received' => $this->countTable('restaurant_new_kitchen_tickets', $businessId, $locationId, ['status' => ['received']]),
            'preparing' => $this->countTable('restaurant_new_kitchen_tickets', $businessId, $locationId, ['status' => ['preparing']]),
            'ready' => $this->countTable('restaurant_new_kitchen_tickets', $businessId, $locationId, ['status' => ['ready']]),
            'served' => $this->countTable('restaurant_new_kitchen_tickets', $businessId, $locationId, ['status' => ['served']]),
            'delayed' => $this->countTable('restaurant_new_kitchen_tickets', $businessId, $locationId, ['is_delayed' => [1]]),
        ];
    }

    public function cashierSummary(int $businessId, ?int $locationId = null): array
    {
        return [
            'open_bills' => $this->countTable('restaurant_new_bills', $businessId, $locationId, ['status' => ['open','pending']]),
            'paid_bills' => $this->countTable('restaurant_new_bills', $businessId, $locationId, ['status' => ['paid']]),
            'refunds' => $this->countTable('restaurant_new_refunds', $businessId, $locationId),
            'voids' => $this->countTable('restaurant_new_void_bills', $businessId, $locationId),
            'payments_total' => $this->sumTable('restaurant_new_payments', 'amount', $businessId, $locationId),
        ];
    }

    public function managerKpis(int $businessId, ?int $locationId = null): array
    {
        $today = Carbon::today()->toDateString();
        $summary = RestaurantNewKpiDailySummary::where('business_id', $businessId)
            ->when($locationId, fn($q) => $q->where('location_id', $locationId))
            ->where('summary_date', $today)
            ->first();

        return $summary ? $summary->toArray() : [
            'sales_total' => 0, 'payment_total' => 0, 'food_cost_total' => 0,
            'gross_profit' => 0, 'food_cost_percent' => 0, 'bills_count' => 0,
            'kot_count' => 0, 'void_count' => 0, 'refund_count' => 0,
            'feedback_count' => 0, 'average_rating' => 0,
        ];
    }

    protected function countTable(string $table, int $businessId, ?int $locationId = null, array $filters = []): int
    {
        if (!DB::getSchemaBuilder()->hasTable($table)) { return 0; }
        $q = DB::table($table)->where('business_id', $businessId);
        if ($locationId && DB::getSchemaBuilder()->hasColumn($table, 'location_id')) { $q->where('location_id', $locationId); }
        foreach ($filters as $field => $values) {
            if (DB::getSchemaBuilder()->hasColumn($table, $field)) { $q->whereIn($field, $values); }
        }
        return (int) $q->count();
    }

    protected function sumTable(string $table, string $column, int $businessId, ?int $locationId = null): float
    {
        if (!DB::getSchemaBuilder()->hasTable($table) || !DB::getSchemaBuilder()->hasColumn($table, $column)) { return 0; }
        $q = DB::table($table)->where('business_id', $businessId);
        if ($locationId && DB::getSchemaBuilder()->hasColumn($table, 'location_id')) { $q->where('location_id', $locationId); }
        return (float) $q->sum($column);
    }
}
