<?php

namespace Modules\RestaurantNew\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Modules\RestaurantNew\Entities\DeliveryDispatch;
use Modules\RestaurantNew\Entities\GoodsReceipt;
use Modules\RestaurantNew\Entities\InventoryBalance;
use Modules\RestaurantNew\Entities\KitchenTicket;
use Modules\RestaurantNew\Entities\Order;
use Modules\RestaurantNew\Entities\Reservation;
use Modules\RestaurantNew\Entities\Wastage;

class ManagementDashboardService
{
    public function __construct(private TenantScopeService $scope)
    {
    }

    public function data(?int $locationId = null): array
    {
        $businessId = $this->scope->businessId();
        abort_unless($businessId, 403);
        $this->scope->assertLocationAccess($locationId);

        $dayStart = now()->copy()->startOfDay();
        $dayEnd = now()->copy()->endOfDay();

        $sales = $this->locationScoped(
            Order::withoutGlobalScopes()
                ->where('business_id', $businessId)
                ->where('payment_status', 'paid')
                ->whereBetween('paid_at', [$dayStart, $dayEnd]),
            $locationId
        );

        $orders = $this->locationScoped(
            Order::withoutGlobalScopes()
                ->where('business_id', $businessId)
                ->whereBetween('created_at', [$dayStart, $dayEnd]),
            $locationId
        );

        $lateTickets = $this->locationScoped(
            KitchenTicket::withoutGlobalScopes()
                ->where('business_id', $businessId)
                ->whereIn('status', ['new', 'accepted', 'preparing'])
                ->where('created_at', '<', now()->subMinutes(20)),
            $locationId
        );

        $reservations = $this->locationScoped(
            Reservation::withoutGlobalScopes()
                ->where('business_id', $businessId)
                ->whereIn('status', ['booked', 'confirmed'])
                ->whereBetween('reserved_at', [now()->subHour(), now()->addHours(4)]),
            $locationId
        );

        $deliveries = $this->locationScoped(
            DeliveryDispatch::withoutGlobalScopes()
                ->where('business_id', $businessId)
                ->whereIn('status', ['waiting', 'assigned', 'dispatched']),
            $locationId
        );

        $lowStock = InventoryBalance::withoutGlobalScopes()
            ->join('restnew_ingredients', 'restnew_ingredients.id', '=', 'restnew_inventory_balances.ingredient_id')
            ->where('restnew_inventory_balances.business_id', $businessId)
            ->where('restnew_ingredients.business_id', $businessId)
            ->whereColumn('restnew_inventory_balances.quantity', '<=', 'restnew_ingredients.reorder_level');
        if ($locationId) {
            $lowStock->where('restnew_inventory_balances.location_id', $locationId);
        }
        $this->scope->applyLocationScope($lowStock, 'restnew_inventory_balances.location_id');

        $discounts = DB::table('restnew_discount_usages')
            ->join('restnew_orders', 'restnew_orders.id', '=', 'restnew_discount_usages.order_id')
            ->where('restnew_discount_usages.business_id', $businessId)
            ->where('restnew_orders.business_id', $businessId)
            ->whereBetween('restnew_discount_usages.created_at', [$dayStart, $dayEnd]);
        if ($locationId) {
            $discounts->where('restnew_orders.location_id', $locationId);
        }
        $this->scope->applyLocationScope($discounts, 'restnew_orders.location_id');

        $wastage = $this->locationScoped(
            Wastage::withoutGlobalScopes()
                ->where('business_id', $businessId)
                ->whereDate('wastage_date', now()->toDateString()),
            $locationId
        );

        $draftReceipts = $this->locationScoped(
            GoodsReceipt::withoutGlobalScopes()
                ->where('business_id', $businessId)
                ->where('status', 'draft'),
            $locationId
        );

        return [
            'sales' => (float) (clone $sales)->sum('total_amount'),
            'paid_orders' => (clone $sales)->count(),
            'orders' => (clone $orders)->count(),
            'average_bill' => (float) ((clone $sales)->avg('total_amount') ?? 0),
            'late_tickets' => (clone $lateTickets)->count(),
            'upcoming_reservations' => (clone $reservations)->count(),
            'active_deliveries' => (clone $deliveries)->count(),
            'low_stock' => $lowStock->count(),
            'discounts' => (float) $discounts->sum('restnew_discount_usages.discount_amount'),
            'wastage' => (float) $wastage->withSum('lines', 'value')->get()->sum('lines_sum_value'),
            'unposted_receipts' => $draftReceipts->count(),
        ];
    }

    private function locationScoped(Builder $query, ?int $locationId): Builder
    {
        if ($locationId) {
            $query->where('location_id', $locationId);
        }
        $this->scope->applyLocationScope($query);

        return $query;
    }
}
