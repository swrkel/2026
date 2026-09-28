<?php

namespace Modules\RestaurantNew\Services;

use Modules\RestaurantNew\Entities\KitchenTicket;
use Modules\RestaurantNew\Entities\Order;
use Modules\RestaurantNew\Entities\OrderItem;
use Modules\RestaurantNew\Entities\Payment;

class ReportService
{
    public function __construct(private TenantScopeService $scope)
    {
    }

    public function range(array $filters): array
    {
        $from = $filters['from'] ?? now()->startOfMonth()->toDateString();
        $to = $filters['to'] ?? now()->toDateString();
        $location = (int) ($filters['location_id'] ?? 0) ?: null;

        return [$from, $to, $location];
    }

    public function sales(array $filters)
    {
        [$from, $to, $location] = $this->range($filters);
        $query = Order::withoutGlobalScopes()
            ->where('business_id', $this->scope->businessId())
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->when($location, fn ($q) => $q->where('location_id', $location));
        $this->scope->applyLocationScope($query);

        return $query->latest('id');
    }

    public function itemSales(array $filters)
    {
        [$from, $to, $location] = $this->range($filters);

        return OrderItem::withoutGlobalScopes()
            ->selectRaw('item_code, item_name, SUM(quantity) qty, SUM(line_total) amount')
            ->where('business_id', $this->scope->businessId())
            ->whereHas('order', function ($q) use ($from, $to, $location) {
                $q->withoutGlobalScopes()
                    ->where('business_id', $this->scope->businessId())
                    ->where('payment_status', 'paid')
                    ->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59'])
                    ->when($location, fn ($x) => $x->where('location_id', $location));
                $this->scope->applyLocationScope($q);
            })
            ->groupBy('item_code', 'item_name')
            ->orderByDesc('amount');
    }

    public function kitchen(array $filters)
    {
        [$from, $to, $location] = $this->range($filters);
        $query = KitchenTicket::withoutGlobalScopes()
            ->where('business_id', $this->scope->businessId())
            ->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->when($location, fn ($q) => $q->where('location_id', $location));
        $this->scope->applyLocationScope($query);

        return $query->latest('id');
    }

    public function cashier(array $filters)
    {
        [$from, $to, $location] = $this->range($filters);
        $query = Payment::withoutGlobalScopes()
            ->selectRaw('received_by, payment_method, COUNT(*) payment_count, SUM(amount) amount')
            ->where('business_id', $this->scope->businessId())
            ->whereBetween('paid_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->when($location, fn ($q) => $q->where('location_id', $location));
        $this->scope->applyLocationScope($query);

        return $query->groupBy('received_by', 'payment_method')->orderBy('received_by');
    }

    public function takeaway(array $filters)
    {
        [$from, $to, $location] = $this->range($filters);
        $query = Order::withoutGlobalScopes()
            ->with('collectionToken')
            ->where('business_id', $this->scope->businessId())
            ->whereIn('order_type', ['takeaway', 'delivery'])
            ->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->when($location, fn ($q) => $q->where('location_id', $location));
        $this->scope->applyLocationScope($query);

        return $query->latest('id');
    }
}
