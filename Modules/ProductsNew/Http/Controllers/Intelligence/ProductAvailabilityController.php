<?php
namespace Modules\ProductsNew\Http\Controllers\Intelligence;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ProductsNew\Services\Intelligence\ProductAvailabilityService;

class ProductAvailabilityController extends Controller
{
    public function __construct(protected ProductAvailabilityService $service) {}

    public function index(Request $request)
    {
        $matrix = $this->service->matrix($request->all());
        return view('productsnew::intelligence.availability.index', compact('matrix'));
    }

    public function refresh(Request $request)
    {
        $count = $this->service->refresh($request->business_id);
        return back()->with('status', __('productsnew::product.availability_refreshed', ['count'=>$count]));
    }
}
