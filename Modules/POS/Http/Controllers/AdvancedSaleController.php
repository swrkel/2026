<?php

namespace Modules\POS\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\POS\Services\AdvancedSaleService;

class AdvancedSaleController extends Controller
{
    public function index()
    {
        // The page itself has no database dependency. Resolve the operational
        // service only for AJAX actions so a service/container issue can never
        // turn the page into a white screen.
        return view('pos::sales.advanced', [
            'title' => __('pos::messages.advanced_sales'),
        ]);
    }

    public function searchProducts(Request $request)
    {
        return response()->json(
            app(AdvancedSaleService::class)->searchProducts($request->all())
        );
    }

    public function hold(Request $request)
    {
        $result = app(AdvancedSaleService::class)->holdSale($request->all());

        return response()->json([
            'success' => true,
            'message' => __('pos::messages.sale_held'),
            'data' => $result,
        ]);
    }

    public function merge(Request $request)
    {
        $result = app(AdvancedSaleService::class)->mergeSales(
            $request->input('sale_ids', [])
        );

        return response()->json([
            'success' => true,
            'message' => __('pos::messages.sales_merged'),
            'data' => $result,
        ]);
    }
}
