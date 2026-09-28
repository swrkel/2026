<?php

namespace Modules\ProductsNew\Http\Controllers\CommandCenter;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ProductsNew\Services\CommandCenter\ProductCommandCenterService;

class ProductCommandCenterController extends Controller
{
    public function __construct(protected ProductCommandCenterService $commandCenter) {}

    public function index(Request $request)
    {
        $products = $this->commandCenter->search($request->all(), 30);
        $workspace = null;
        if ($request->filled('product_id')) {
            $workspace = $this->commandCenter->workspace((int)$request->product_id, $request->all());
        }
        return view('productsnew::command_center.index', compact('products','workspace'));
    }

    public function show(Request $request, int $product)
    {
        $workspace = $this->commandCenter->workspace($product, $request->all());
        return view('productsnew::command_center.show', compact('workspace'));
    }

    public function search(Request $request)
    {
        return response()->json($this->commandCenter->search($request->all(), 25));
    }

    public function snapshot(Request $request, int $product)
    {
        $snapshot = $this->commandCenter->saveSnapshot($product, $request->all());
        return response()->json(['success'=>true,'snapshot_id'=>$snapshot->id]);
    }
}
