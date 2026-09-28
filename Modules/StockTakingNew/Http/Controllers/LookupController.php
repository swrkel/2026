<?php

namespace Modules\StockTakingNew\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\StockTakingNew\Services\InventoryBridgeService;
use Modules\StockTakingNew\Services\MasterDataBridgeService;
use Modules\StockTakingNew\Services\TenantScopeService;

class LookupController extends Controller
{
    public function locations(Request $request, TenantScopeService $scope, MasterDataBridgeService $masters)
    {
        $businessId = $scope->businessId($request);
        return response()->json($businessId ? $masters->locations($businessId) : []);
    }

    public function stores(Request $request, TenantScopeService $scope, MasterDataBridgeService $masters)
    {
        $businessId = $scope->businessId($request);
        $locationId = $request->integer('location_id') ?: null;
        if ($locationId) {
            $scope->assertLocationAccess($locationId);
        }
        return response()->json($businessId ? $masters->stores($businessId, $locationId) : []);
    }

    public function products(Request $request, TenantScopeService $scope, InventoryBridgeService $inventory)
    {
        $businessId = $scope->businessId($request);
        $data = $request->validate([
            'location_id' => 'required|integer|min:1',
            'store_id' => 'nullable|integer|min:1',
            'q' => 'nullable|string|max:120',
        ]);
        $scope->assertLocationAccess((int) $data['location_id']);

        return response()->json($businessId ? $inventory->search(
            $businessId,
            (int) $data['location_id'],
            ! empty($data['store_id']) ? (int) $data['store_id'] : null,
            (string) ($data['q'] ?? ''),
            [],
            50
        ) : []);
    }
}
