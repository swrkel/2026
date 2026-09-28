<?php

namespace Modules\StockAdjustmentNew\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\StockAdjustmentNew\Services\ProductBridgeService;
use Modules\StockAdjustmentNew\Services\SettingsReferenceService;
use Modules\StockAdjustmentNew\Services\StockAdjustmentSettingsService;
use Modules\StockAdjustmentNew\Services\TenantScopeService;

class BatchLookupController extends Controller
{
    public function __invoke(
        Request $request,
        ProductBridgeService $products,
        TenantScopeService $scope,
        SettingsReferenceService $references,
        StockAdjustmentSettingsService $settingsService
    ): JsonResponse {
        $validated = $request->validate([
            'product_id' => 'required|integer|min:1',
            'variation_id' => 'nullable|integer|min:1',
            'location_id' => 'nullable|integer|min:1',
            'store_id' => 'nullable|integer|min:1',
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
        $batches = $products->getAvailableBatches(
            (int) $validated['product_id'],
            isset($validated['variation_id']) ? (int) $validated['variation_id'] : null,
            $businessId,
            $locationId,
            $storeId,
            (string) ($settings['batch_selection_method'] ?? 'fefo')
        );

        return response()->json([
            'results' => $batches,
            'requires_batch' => $batches !== [] && (bool) ($settings['require_batch_when_available'] ?? true),
        ]);
    }
}
