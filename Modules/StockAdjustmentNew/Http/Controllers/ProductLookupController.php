<?php

namespace Modules\StockAdjustmentNew\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\StockAdjustmentNew\Services\ProductBridgeService;
use Modules\StockAdjustmentNew\Services\SettingsReferenceService;
use Modules\StockAdjustmentNew\Services\StockAdjustmentSettingsService;
use Modules\StockAdjustmentNew\Services\TenantScopeService;

class ProductLookupController extends Controller
{
    public function __invoke(
        Request $request,
        ProductBridgeService $products,
        TenantScopeService $scope,
        SettingsReferenceService $references,
        StockAdjustmentSettingsService $settingsService
    ): JsonResponse {
        $validated = $request->validate([
            'q' => 'nullable|string|max:100',
            'location_id' => 'nullable|integer|min:1',
            'store_id' => 'nullable|integer|min:1',
            'category_id' => 'nullable|integer|min:1',
            'sub_category_id' => 'nullable|integer|min:1',
            'stock_adjustment_type' => 'nullable|in:increase,decrease',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        $businessId = $scope->businessId($request);
        abort_unless($businessId, 422, 'A business must be selected.');

        $locationId = isset($validated['location_id']) ? (int) $validated['location_id'] : null;
        $storeId = isset($validated['store_id']) ? (int) $validated['store_id'] : null;
        $permittedLocationIds = $scope->permittedLocationIds($request);

        if ($locationId !== null && ! $references->locationBelongsToBusiness($locationId, (int) $businessId, $permittedLocationIds)) {
            abort(422, 'The selected Location is invalid or is not assigned to this user.');
        }
        if ($storeId !== null && ! $references->storeBelongsToBusiness($storeId, (int) $businessId, $locationId)) {
            abort(422, 'The selected Store is invalid or does not belong to the selected Location.');
        }

        $settings = $settingsService->values((int) $businessId);
        $results = $products->searchProducts(
            (string) ($validated['q'] ?? ''),
            $businessId,
            $locationId,
            $storeId,
            (int) ($validated['limit'] ?? 25),
            isset($validated['category_id']) ? (int) $validated['category_id'] : null,
            isset($validated['sub_category_id']) ? (int) $validated['sub_category_id'] : null
        );

        if (
            (string) ($validated['stock_adjustment_type'] ?? 'decrease') === 'decrease'
            && (bool) ($settings['hide_zero_stock_products'] ?? false)
        ) {
            $results = array_values(array_filter(
                $results,
                static fn (array $product): bool => (float) ($product['system_qty'] ?? 0) > 0
            ));
        }

        return response()->json(['results' => $results]);
    }
}
