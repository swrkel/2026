<?php

namespace Modules\StockTransferNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\StockTransferNew\Entities\StockTransferCarrierInvoice;
use Modules\StockTransferNew\Services\CarrierInvoiceService;

class CarrierInvoiceController extends Controller
{
    private CarrierInvoiceService $service;

    public function __construct(CarrierInvoiceService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $businessId = (int) session('business.id');
        $invoices = $this->service->list($businessId, $request->only(['status', 'carrier_name', 'transfer_id']));

        return view('stocktransfernew::carrier.index', compact('invoices'));
    }

    public function create()
    {
        return view('stocktransfernew::carrier.create');
    }

    public function store(Request $request)
    {
        $businessId = (int) session('business.id');
        $userId = (int) auth()->id();
        $this->service->store($businessId, $request->all(), $userId);

        return redirect()->route('stock-transfer-new.carrier-invoices.index')
            ->with('status', __('carrier.invoice_created'));
    }

    public function approve(StockTransferCarrierInvoice $carrierInvoice)
    {
        $this->service->approve($carrierInvoice, (int) auth()->id());

        return back()->with('status', __('carrier.invoice_approved'));
    }

    public function cancel(StockTransferCarrierInvoice $carrierInvoice)
    {
        $this->service->cancel($carrierInvoice, (int) auth()->id());

        return back()->with('status', __('carrier.invoice_cancelled'));
    }
}
