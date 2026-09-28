<?php
namespace Modules\ProductsNew\Http\Controllers\Framework;

use Illuminate\Routing\Controller;
use Modules\ProductsNew\Services\Framework\VersionHistoryService;

class VersionHistoryController extends Controller
{
    public function __construct(protected VersionHistoryService $service) {}
    public function index(){ $versions=$this->service->list(request()->all()); return view('productsnew::framework.version_history.index', compact('versions')); }
    public function show(int $id){ return response()->json($this->service->find($id)); }
}
