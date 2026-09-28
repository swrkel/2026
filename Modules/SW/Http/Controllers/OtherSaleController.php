<?php

namespace Modules\SW\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\SW\Services\OtherSaleLookupService;

/**
 * What the Other Sales form asks for — 8044.
 */
class OtherSaleController extends Controller
{
    public function __construct(protected OtherSaleLookupService $lookup)
    {
    }

    protected function businessId(): int
    {
        return (int) (session('business.id') ?: session('user.business_id') ?: 0);
    }

    public function stores(Request $request)
    {
        $businessId = $this->businessId();

        if ($businessId <= 0) {
            return response()->json([]);
        }

        return response()->json(
            $this->lookup->stores($businessId, (int) $request->input('location_id'))
        );
    }

    public function storeProducts(Request $request)
    {
        $businessId = $this->businessId();
        $storeId = (int) $request->input('store_id');

        if ($businessId <= 0 || $storeId <= 0) {
            return response()->json([]);
        }

        return response()->json($this->lookup->products(
            $businessId, $storeId, (int) $request->input('location_id')
        ));
    }

    public function productDetails(Request $request)
    {
        $businessId = $this->businessId();
        $variationId = (int) $request->input('variation_id');

        if ($businessId <= 0 || $variationId <= 0) {
            return response()->json(['found' => false]);
        }

        return response()->json($this->lookup->forProduct(
            $businessId, $variationId, (int) $request->input('location_id')
        ));
    }
}
