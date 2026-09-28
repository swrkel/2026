<?php
namespace Modules\ProductsNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ProductsNew\Services\Barcode\BarcodeTemplateService;
use Modules\ProductsNew\Services\ProductLookupService;

class BarcodeController extends Controller
{
    protected BarcodeTemplateService $templates;
    protected ProductLookupService $lookups;

    public function __construct(BarcodeTemplateService $templates, ProductLookupService $lookups)
    {
        $this->templates = $templates;
        $this->lookups = $lookups;
    }

    public function index(Request $request)
    {
        return view('productsnew::barcode.index', [
            'templates' => $this->templates->templates($request),
            'products' => $this->lookups->products($request),
        ]);
    }

    public function queue(Request $request)
    {
        $request->validate(['product_id'=>'required|integer','quantity'=>'required|integer|min:1']);
        $this->templates->queue($request);
        return back()->with('status', __('productsnew::product.barcode_queue_saved'));
    }

    public function templates(Request $request)
    {
        return view('productsnew::barcode.templates.index', ['templates'=>$this->templates->templates($request)]);
    }

    public function saveTemplate(Request $request)
    {
        $request->validate(['name'=>'required|max:191']);
        $this->templates->saveTemplate($request);
        return back()->with('status', __('productsnew::product.barcode_template_saved'));
    }
}
