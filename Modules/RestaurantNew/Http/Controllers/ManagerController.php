<?php

namespace Modules\RestaurantNew\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\RestaurantNew\Entities\DeliveryDispatch;
use Modules\RestaurantNew\Entities\InventoryBalance;
use Modules\RestaurantNew\Entities\KitchenTicket;
use Modules\RestaurantNew\Entities\ManagerApproval;
use Modules\RestaurantNew\Entities\Reservation;
use Modules\RestaurantNew\Services\ManagementDashboardService;
use Modules\RestaurantNew\Services\TenantScopeService;

class ManagerController extends Controller
{
    public function index(
        Request $request,
        TenantScopeService $scope,
        ManagementDashboardService $dashboard
    ) {
        $locationId = (int) $request->get('location_id') ?: $scope->currentLocationId();
        if ($locationId) {
            $scope->assertLocationAccess($locationId);
        }

        $businessId = $scope->businessId();

        $lateQuery = KitchenTicket::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->whereIn('status', ['new', 'accepted', 'preparing'])
            ->where('created_at', '<', now()->subMinutes(20));
        $this->scopeLocation($lateQuery, $scope, $locationId);
        $late = $lateQuery->latest()->limit(20)->get();

        $reservationQuery = Reservation::withoutGlobalScopes()
            ->with('table')
            ->where('business_id', $businessId)
            ->whereIn('status', ['booked', 'confirmed'])
            ->whereBetween('reserved_at', [now()->subHour(), now()->addHours(4)]);
        $this->scopeLocation($reservationQuery, $scope, $locationId);
        $reservations = $reservationQuery->orderBy('reserved_at')->limit(20)->get();

        $deliveryQuery = DeliveryDispatch::withoutGlobalScopes()
            ->with('order')
            ->where('business_id', $businessId)
            ->whereIn('status', ['waiting', 'assigned', 'dispatched']);
        $this->scopeLocation($deliveryQuery, $scope, $locationId);
        $deliveries = $deliveryQuery->latest()->limit(20)->get();

        $lowQuery = InventoryBalance::withoutGlobalScopes()
            ->join('restnew_ingredients', 'restnew_ingredients.id', '=', 'restnew_inventory_balances.ingredient_id')
            ->where('restnew_inventory_balances.business_id', $businessId)
            ->whereColumn('restnew_inventory_balances.quantity', '<=', 'restnew_ingredients.reorder_level')
            ->select(
                'restnew_inventory_balances.*',
                'restnew_ingredients.name',
                'restnew_ingredients.unit',
                'restnew_ingredients.reorder_level'
            );
        if ($locationId) {
            $lowQuery->where('restnew_inventory_balances.location_id', $locationId);
        } else {
            $scope->applyLocationScope($lowQuery, 'restnew_inventory_balances.location_id');
        }
        $low = $lowQuery->orderBy('restnew_inventory_balances.quantity')->limit(20)->get();

        $approvalQuery = ManagerApproval::withoutGlobalScopes()
            ->where('business_id', $businessId);
        $this->scopeLocation($approvalQuery, $scope, $locationId);
        $approvals = $approvalQuery->latest()->limit(30)->get();

        return view('restaurantnew::manager.index', [
            'metrics' => $dashboard->data($locationId),
            'lateTickets' => $late,
            'reservations' => $reservations,
            'deliveries' => $deliveries,
            'lowStock' => $low,
            'approvals' => $approvals,
            'locations' => $scope->locationOptions(),
            'locationId' => $locationId,
        ]);
    }

    private function scopeLocation($query, TenantScopeService $scope, ?int $locationId): void
    {
        if ($locationId) {
            $query->where('location_id', $locationId);
            return;
        }

        $scope->applyLocationScope($query);
    }
}
