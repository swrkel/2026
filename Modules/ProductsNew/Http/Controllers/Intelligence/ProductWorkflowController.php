<?php
namespace Modules\ProductsNew\Http\Controllers\Intelligence;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ProductsNew\Entities\ProductsNewProduct;
use Modules\ProductsNew\Services\Intelligence\ProductWorkflowService;
use Modules\ProductsNew\Services\ProductLookupService;

class ProductWorkflowController extends Controller
{
    public function __construct(protected ProductWorkflowService $service, protected ProductLookupService $lookup) {}

    public function index(Request $request)
    {
        $statuses = $this->service->statuses($request->business_id);
        $transitions = $this->service->transitions($request->all());
        $lookups = $this->lookup->formLookups();
        return view('productsnew::intelligence.workflow.index', compact('statuses','transitions','lookups'));
    }

    public function transition(Request $request, ProductsNewProduct $product)
    {
        $data = $request->validate(['to_status'=>'required|string|max:50','note'=>'nullable|string|max:500']);
        $this->service->transition($product, $data['to_status'], $data['note'] ?? null);
        return back()->with('status', __('productsnew::product.status_updated'));
    }
}
