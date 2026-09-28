<?php

namespace Modules\PriceChangeNew\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\PriceChangeNew\Services\ProductLookupService;

class ProductLookupController extends Controller
{
    public function __construct(private ProductLookupService $products)
    {
    }

    public function search(Request $request)
    {
        return response()->json([
            'results' => $this->products->search((string) $request->query('q', '')),
        ]);
    }

    public function show(Request $request, int $variationId)
    {
        return response()->json([
            'data' => $this->products->details(
                $variationId,
                (array) $request->query('location_ids', [])
            ),
        ]);
    }
}
