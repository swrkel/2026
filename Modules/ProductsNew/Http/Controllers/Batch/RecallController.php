<?php
namespace Modules\ProductsNew\Http\Controllers\Batch;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ProductsNew\Services\Batch\RecallService;
use Modules\ProductsNew\Services\ProductLookupService;

class RecallController extends Controller
{
    public function __construct(protected RecallService $service, protected ProductLookupService $lookup) {}
    public function index(Request $request)
    {
        $recalls = $this->service->query($request->all())->paginate(50);
        $lookups = $this->lookup->formLookups();
        return view('productsnew::batch.recalls', compact('recalls','lookups'));
    }
    public function store(Request $request)
    {
        $data = $request->validate(['product_id'=>'required|integer','batch_id'=>'nullable|integer','recall_no'=>'required|string|max:191','reason'=>'required|string|max:1000','status'=>'nullable|string|max:50']);
        $this->service->create($data);
        return back()->with('status', __('productsnew::product.recall_saved'));
    }
    public function close(int $recall)
    {
        $this->service->close($recall);
        return back()->with('status', __('productsnew::product.recall_closed'));
    }
}
