<?php
namespace Modules\ProductsNew\Http\Controllers\Framework;

use Illuminate\Routing\Controller;
use Modules\ProductsNew\Services\Framework\ProductTemplateService;

class ProductTemplateController extends Controller
{
    public function __construct(protected ProductTemplateService $service) {}
    public function index(){ $templates=$this->service->list(); return view('productsnew::framework.templates.index', compact('templates')); }
    public function store(){ $this->service->store(request()->all()); return back()->with('status', __('productsnew::lang.template_saved')); }
    public function apply(int $template){ return response()->json($this->service->apply($template)); }
}
