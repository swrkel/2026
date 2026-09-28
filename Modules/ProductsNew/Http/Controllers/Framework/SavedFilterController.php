<?php
namespace Modules\ProductsNew\Http\Controllers\Framework;

use Illuminate\Routing\Controller;
use Modules\ProductsNew\Services\Framework\SavedFilterService;

class SavedFilterController extends Controller
{
    public function __construct(protected SavedFilterService $service) {}
    public function index(){ $filters=$this->service->list(); return view('productsnew::framework.saved_filters.index', compact('filters')); }
    public function store(){ $this->service->store(request()->all()); return back()->with('status', __('productsnew::lang.saved_filter_saved')); }
}
