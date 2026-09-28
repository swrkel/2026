<?php
namespace Modules\ProductsNew\Http\Controllers\Intelligence;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ProductsNew\Services\Intelligence\ProductNoteService;
use Modules\ProductsNew\Services\ProductLookupService;

class ProductNoteController extends Controller
{
    public function __construct(protected ProductNoteService $service, protected ProductLookupService $lookup) {}

    public function index(Request $request)
    {
        $notes = $this->service->list($request->all());
        $lookups = $this->lookup->formLookups();
        return view('productsnew::intelligence.notes.index', compact('notes','lookups'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id'=>'required|integer', 'note_type'=>'required|string|max:50', 'title'=>'nullable|string|max:191',
            'note'=>'required|string', 'is_pinned'=>'nullable|boolean'
        ]);
        $this->service->create($data);
        return back()->with('status', __('productsnew::product.note_saved'));
    }
}
