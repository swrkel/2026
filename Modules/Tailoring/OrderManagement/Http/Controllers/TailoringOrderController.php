<?php
namespace Modules\Tailoring\OrderManagement\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\Tailoring\OrderManagement\Services\TailoringOrderService;

class TailoringOrderController extends Controller
{
    protected $service;

    public function __construct(TailoringOrderService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $summary = $this->service->summary($request);
        return view('tailoring::order_management.orders.index', compact('summary'));
    }

    public function create()
    {
        return view('tailoring::order_management.orders.create');
    }

    public function show($id)
    {
        $order = $this->service->find($id);
        return view('tailoring::order_management.orders.show', compact('order'));
    }
}
