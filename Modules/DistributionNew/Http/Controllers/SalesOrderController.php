<?php

namespace Modules\DistributionNew\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\DistributionNew\Services\Orders\DisnewSalesOrderWorkflowService;
use Modules\DistributionNew\Utils\DisnewTenantUtil;

class SalesOrderController extends Controller
{
    public function __construct(protected DisnewTenantUtil $tenant, protected DisnewSalesOrderWorkflowService $orders) {}

    public function index(Request $request)
    {
        $businessId = $this->tenant->businessId();
        $orders = DB::table('disnew_sales_orders')->where('business_id', $businessId)->latest('id')->paginate(25);
        return view('distributionnew::sales_orders.index', compact('orders'));
    }

    public function create()
    {
        return view('distributionnew::sales_orders.create', ['order' => null, 'lines' => collect()]);
    }

    public function store(Request $request)
    {
        $businessId = $this->tenant->businessId();
        $locationId = $this->tenant->locationId();
        $this->orders->create($request->all(), $businessId, $locationId, auth()->id(), $request->input('source', 'user'));
        return redirect()->route('distributionnew.sales-orders.index')->with('status', 'Sales order created successfully.');
    }

    public function edit(int $id)
    {
        $businessId = $this->tenant->businessId();
        $order = DB::table('disnew_sales_orders')->where('business_id', $businessId)->where('id', $id)->first();
        if (!$order) { abort(404); }
        $lines = DB::table('disnew_sales_order_lines')->where('sales_order_id', $id)->get();
        return view('distributionnew::sales_orders.edit', compact('order', 'lines'));
    }

    public function update(Request $request, int $id)
    {
        $this->orders->update($id, $request->all(), $this->tenant->businessId(), auth()->id());
        return redirect()->route('distributionnew.sales-orders.index')->with('status', 'Sales order updated successfully.');
    }

    public function show(int $id)
    {
        $businessId = $this->tenant->businessId();
        $order = DB::table('disnew_sales_orders')->where('business_id', $businessId)->where('id', $id)->first();
        if (!$order) { abort(404); }
        $lines = DB::table('disnew_sales_order_lines')->where('sales_order_id', $id)->get();
        $history = DB::table('disnew_sales_order_status_histories')->where('sales_order_id', $id)->latest('id')->get();
        return view('distributionnew::sales_orders.show', compact('order', 'lines', 'history'));
    }
}
