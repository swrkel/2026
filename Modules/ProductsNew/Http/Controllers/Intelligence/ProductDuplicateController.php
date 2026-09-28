<?php
namespace Modules\ProductsNew\Http\Controllers\Intelligence;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ProductsNew\Services\Intelligence\ProductDuplicateService;

class ProductDuplicateController extends Controller
{
    public function __construct(protected ProductDuplicateService $service) {}

    public function index(Request $request)
    {
        $duplicates = $this->service->list($request->all());
        return view('productsnew::intelligence.duplicates.index', compact('duplicates'));
    }

    public function scan(Request $request)
    {
        $count = $this->service->scan($request->business_id);
        return back()->with('status', __('productsnew::product.duplicates_scanned', ['count'=>$count]));
    }

    public function resolve(Request $request, int $duplicate)
    {
        $data = $request->validate(['status'=>'required|in:confirmed,ignored,merged,open']);
        $this->service->resolve($duplicate, $data['status']);
        return back()->with('status', __('productsnew::product.duplicate_updated'));
    }
}
