<?php
namespace Modules\AutoService\Http\Controllers;

use Illuminate\Http\Request;
use Modules\AutoService\Entities\AutoServiceInvoice;
use Modules\AutoService\Entities\AutoServiceJob;
use Modules\AutoService\Services\AutoServiceBillingDeliveryService;

class BillingDeliveryController extends AutoServiceBaseController
{
    public function index(Request $request)
    {
        $service = app(AutoServiceBillingDeliveryService::class);
        $jobs = $service->deliveryBoard($this->businessId(), $this->locationId(), $request->all());
        $summary = $service->summary($this->businessId(), $this->locationId());
        return view('autoservice::billing_delivery.index', compact('jobs', 'summary'));
    }

    public function generateInvoice($job)
    {
        $job = AutoServiceJob::with('lines')->findOrFail($job);
        $invoice = app(AutoServiceBillingDeliveryService::class)->generateInvoiceForCompletedJob($job, $this->businessId(), $this->locationId());
        return redirect()->route('autoservice.billing_delivery.index')->with('status', 'Invoice '.$invoice->invoice_no.' generated successfully.');
    }

    public function markPaid(Request $request, $invoice)
    {
        $invoice = AutoServiceInvoice::findOrFail($invoice);
        app(AutoServiceBillingDeliveryService::class)->recordPayment($invoice, $request->all(), $this->businessId(), $this->locationId());
        return redirect()->route('autoservice.billing_delivery.index')->with('status', 'Payment recorded successfully.');
    }

    public function release(Request $request, $job)
    {
        $job = AutoServiceJob::findOrFail($job);
        app(AutoServiceBillingDeliveryService::class)->releaseVehicle($job, $request->all(), $this->businessId(), $this->locationId());
        return redirect()->route('autoservice.billing_delivery.index')->with('status', 'Vehicle released and handover recorded.');
    }
}
