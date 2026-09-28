<?php
namespace Modules\ProductsNew\Http\Controllers\Framework;

use Illuminate\Routing\Controller;
use Modules\ProductsNew\Services\Framework\InventoryExceptionService;

class ExceptionCentreController extends Controller
{
    public function __construct(protected InventoryExceptionService $service) {}
    public function index(){ $summary=$this->service->summary(); $items=$this->service->items(request()->all()); return view('productsnew::framework.exceptions.index', compact('summary','items')); }
}
