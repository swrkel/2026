<?php
namespace Modules\ProductsNew\Http\Controllers\Intelligence;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ProductsNew\Services\Intelligence\ProductRelationshipService;
use Modules\ProductsNew\Services\ProductLookupService;

class ProductRelationshipController extends Controller
{
    public function __construct(protected ProductRelationshipService $service, protected ProductLookupService $lookup) {}

    public function index(Request $request)
    {
        $relationships = $this->service->list($request->all());
        $lookups = $this->lookup->formLookups();
        return view('productsnew::intelligence.relationships.index', compact('relationships','lookups'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id'=>'required|integer', 'related_product_id'=>'required|integer|different:product_id',
            'relationship_type'=>'required|string|max:50', 'note'=>'nullable|string|max:500', 'is_active'=>'nullable|boolean'
        ]);
        $this->service->create($data);
        return back()->with('status', __('productsnew::product.relationship_saved'));
    }
}
