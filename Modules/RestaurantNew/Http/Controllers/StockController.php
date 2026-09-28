<?php

namespace Modules\RestaurantNew\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\RestaurantNew\Entities\Ingredient;
use Modules\RestaurantNew\Entities\InventoryBalance;
use Modules\RestaurantNew\Entities\StockMovement;
use Modules\RestaurantNew\Http\Requests\StockAdjustmentRequest;
use Modules\RestaurantNew\Services\InventoryService;
use Modules\RestaurantNew\Services\TenantScopeService;

class StockController extends Controller
{
    public function index(TenantScopeService $scope)
    {
        $balances = InventoryBalance::withoutGlobalScopes()
            ->where('business_id', $scope->businessId())
            ->with(['ingredient', 'location']);
        $scope->applyOptionalLocationScope($balances);

        $movements = StockMovement::withoutGlobalScopes()
            ->where('business_id', $scope->businessId())
            ->with(['ingredient', 'location']);
        $scope->applyOptionalLocationScope($movements);

        return view('restaurantnew::stock.index', [
            'balances' => $balances->orderBy('ingredient_id')->paginate(50),
            'ingredients' => Ingredient::where('is_active', true)->orderBy('name')->get(),
            'movements' => $movements->latest('id')->limit(20)->get(),
            'locations' => $scope->locationOptions(),
            'currentLocationId' => $scope->currentLocationId(),
        ]);
    }

    public function adjust(StockAdjustmentRequest $request, TenantScopeService $scope, InventoryService $service)
    {
        $data = $request->validated();
        $locationId = (int) ($data['location_id'] ?? 0) ?: null;
        $scope->assertLocationAccess($locationId);
        $ingredient = Ingredient::findOrFail((int) $data['ingredient_id']);
        $scope->assertBusinessRecord($ingredient, $scope->businessId());
        $service->adjust((int) $scope->businessId(), $locationId, $ingredient->id, (float) $data['quantity'], $data['reason']);

        return back()->with('success', 'Ingredient stock adjusted.');
    }
}
