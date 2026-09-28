<?php

namespace Modules\RestaurantNew\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\RestaurantNew\Entities\Ingredient;
use Modules\RestaurantNew\Entities\StockTransfer;
use Modules\RestaurantNew\Http\Requests\StoreStockTransferRequest;
use Modules\RestaurantNew\Services\StockTransferService;
use Modules\RestaurantNew\Services\TenantScopeService;

class StockTransferController extends Controller
{
    public function index(TenantScopeService $scope)
    {
        $query = StockTransfer::withoutGlobalScopes()
            ->with('lines.ingredient')
            ->where('business_id', $scope->businessId());

        $locationId = $scope->currentLocationId();
        if ($locationId) {
            $query->where(function ($locationQuery) use ($locationId) {
                $locationQuery->where('from_location_id', $locationId)
                    ->orWhere('to_location_id', $locationId);
            });
        } else {
            $allowed = $scope->permittedLocations();
            if ($allowed !== 'all') {
                $query->where(function ($locationQuery) use ($allowed) {
                    $permitted = $allowed ?: [-1];
                    $locationQuery->whereIn('from_location_id', $permitted)
                        ->orWhereIn('to_location_id', $permitted);
                });
            }
        }

        return view('restaurantnew::inventory.transfers', [
            'transfers' => $query->latest('id')->paginate(100),
            'ingredients' => Ingredient::withoutGlobalScopes()
                ->where('business_id', $scope->businessId())
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'locations' => $scope->locationOptions(),
        ]);
    }

    public function store(StoreStockTransferRequest $request, StockTransferService $service)
    {
        $transfer = $service->create($request->validated());

        return back()->with('success', 'Transfer '.$transfer->transfer_no.' created.');
    }

    public function dispatchTransfer(StockTransfer $transfer, StockTransferService $service)
    {
        $service->dispatch($transfer);

        return back()->with('success', 'Transfer dispatched.');
    }

    public function receive(StockTransfer $transfer, StockTransferService $service)
    {
        $service->receive($transfer);

        return back()->with('success', 'Transfer received and stock updated.');
    }
}
