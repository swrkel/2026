<?php
namespace Modules\ProductsNew\Http\Controllers\Framework;

use Illuminate\Routing\Controller;
use Modules\ProductsNew\Services\Framework\BulkOperationService;

class BulkOperationController extends Controller
{
    public function __construct(protected BulkOperationService $service) {}
    public function index(){ $sessions=$this->service->recent(); return view('productsnew::framework.bulk_operations.index', compact('sessions')); }
    public function preview(){ return response()->json($this->service->preview(request()->all())); }
    public function commit(){ $this->service->commit(request()->all()); return back()->with('status', __('productsnew::lang.bulk_operation_completed')); }
}
