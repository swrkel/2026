<?php

namespace Modules\ProductsNew\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ProductsNew\Services\ProductQueryService;

class ProductLookupController extends Controller
{
    public function __construct(protected ProductQueryService $products)
    {
    }

    public function lookup(Request $request)
    {
        $filters = $request->all();
        $filters['status'] = $filters['status'] ?? 'active';

        return response()->json($this->products->paginated($filters, 20));
    }

    public function barcode(string $barcode)
    {
        return response()->json(
            $this->products->baseQuery(['search' => $barcode, 'status' => 'active'])->first()
        );
    }
}
