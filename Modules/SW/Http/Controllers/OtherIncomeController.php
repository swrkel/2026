<?php

namespace Modules\SW\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\SW\Services\OtherIncomeLookupService;

/**
 * What the Other Income form asks for — 8045.
 */
class OtherIncomeController extends Controller
{
    public function __construct(protected OtherIncomeLookupService $lookup)
    {
    }

    protected function businessId(): int
    {
        return (int) (session('business.id') ?: session('user.business_id') ?: 0);
    }

    public function services()
    {
        $businessId = $this->businessId();

        if ($businessId <= 0) {
            return response()->json([]);
        }

        return response()->json($this->lookup->services($businessId));
    }

    public function serviceDetails(Request $request)
    {
        $businessId = $this->businessId();
        $productId = (int) $request->input('product_id');

        if ($businessId <= 0 || $productId <= 0) {
            return response()->json(['found' => false]);
        }

        return response()->json($this->lookup->forService($businessId, $productId));
    }
}
