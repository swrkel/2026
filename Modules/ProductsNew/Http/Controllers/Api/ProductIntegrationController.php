<?php
namespace Modules\ProductsNew\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ProductsNew\Services\Integration\ProductsNewIntegrationBridge;

class ProductIntegrationController extends Controller
{
    public function lookup(Request $request, ProductsNewIntegrationBridge $bridge)
    {
        return response()->json($bridge->lookupForModule($request->get('consumer', 'external'), $request->all()));
    }

    public function stock(Request $request, $product, ProductsNewIntegrationBridge $bridge)
    {
        return response()->json($bridge->stockForModule($request->get('consumer', 'external'), (int) $product, $request->all()));
    }

    public function price(Request $request, $product, ProductsNewIntegrationBridge $bridge)
    {
        return response()->json($bridge->priceForModule($request->get('consumer', 'external'), (int) $product, $request->all()));
    }
}
