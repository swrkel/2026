<?php

namespace Modules\StockTransferNew\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\StockTransferNew\Services\StockTransferLookupService;
use Modules\StockTransferNew\Utilities\StockTransferTenant;

class LookupController extends Controller
{
    public function __construct(protected StockTransferLookupService $lookup)
    {
    }

    public function locations()
    {
        return response()->json([
            'results' => $this->lookup
                ->locations((int) StockTransferTenant::businessId())
                ->map(static fn ($row) => [
                    'id' => (int) $row->id,
                    'text' => (string) $row->name,
                ])
                ->values(),
        ]);
    }

    public function stores(Request $request)
    {
        return response()->json([
            'results' => $this->lookup
                ->stores(
                    (int) StockTransferTenant::businessId(),
                    ($locationId = (int) $request->query('location_id', 0)) > 0 ? $locationId : null
                )
                ->map(static fn ($row) => [
                    'id' => (int) $row->id,
                    'text' => (string) $row->name,
                    'location_id' => (int) ($row->location_id ?? $row->business_location_id ?? 0),
                ])
                ->values(),
        ]);
    }

    public function products(Request $request)
    {
        $term = trim((string) $request->query('q', ''));

        $products = $this->lookup
            ->products(
                (int) StockTransferTenant::businessId(),
                $term,
                30
            )
            ->map(static function ($product) {
                $sku = trim((string) ($product->sku ?? ''));

                return [
                    'id' => (int) $product->id,
                    'text' => $sku !== ''
                        ? $product->name . ' (' . $sku . ')'
                        : $product->name,
                ];
            })
            ->values();

        return response()->json([
            'results' => $products,
            'pagination' => ['more' => false],
        ]);
    }

    public function variations(int $productId)
    {
        return response()->json(
            $this->lookup->variations(
                (int) StockTransferTenant::businessId(),
                $productId
            )
        );
    }

    public function availableStock(Request $request)
    {
        return response()->json([
            'qty_available' => $this->lookup->availableStock(
                (int) StockTransferTenant::businessId(),
                ($locationId = (int) $request->query('location_id', 0)) > 0 ? $locationId : null,
                ($storeId = (int) $request->query('store_id', 0)) > 0 ? $storeId : null,
                ($productId = (int) $request->query('product_id', 0)) > 0 ? $productId : null,
                ($variationId = (int) $request->query('variation_id', 0)) > 0 ? $variationId : null
            ),
        ]);
    }
}
