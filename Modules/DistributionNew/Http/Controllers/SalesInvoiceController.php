<?php

namespace Modules\DistributionNew\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\DistributionNew\Services\Invoices\DisnewInvoiceFromOrderService;
use Modules\DistributionNew\Utils\DisnewTenantUtil;

class SalesInvoiceController extends Controller
{
    public function __construct(protected DisnewTenantUtil $tenant, protected DisnewInvoiceFromOrderService $invoices) {}

    public function index()
    {
        $invoices = DB::table('disnew_sales_invoices')->where('business_id', $this->tenant->businessId())->latest('id')->paginate(25);
        return view('distributionnew::invoices.index', compact('invoices'));
    }

    public function createFromOrder(Request $request, int $salesOrderId)
    {
        $invoiceId = $this->invoices->createFromOrder($salesOrderId, $this->tenant->businessId(), auth()->id());
        return redirect()->route('distributionnew.invoices.show', $invoiceId)->with('status', 'Sales invoice created from sales order.');
    }

    public function show(int $id)
    {
        $invoice = DB::table('disnew_sales_invoices')->where('business_id', $this->tenant->businessId())->where('id', $id)->first();
        if (!$invoice) { abort(404); }
        $lines = DB::table('disnew_sales_invoice_lines')->where('sales_invoice_id', $id)->get();
        return view('distributionnew::invoices.show', compact('invoice', 'lines'));
    }
}
