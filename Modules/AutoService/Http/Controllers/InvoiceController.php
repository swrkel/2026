<?php
namespace Modules\AutoService\Http\Controllers;

use Illuminate\Http\Request;
use Modules\AutoService\Entities\AutoServiceInvoice;
use Modules\AutoService\Entities\AutoServiceJob;
use Modules\AutoService\Entities\AutoServiceVehicle;
use Modules\AutoService\Services\AutoServiceInvoiceService;
use Modules\AutoService\Services\ProductPartsAdapter;
use Modules\AutoService\Services\SharedCustomerAdapter;
use Modules\AutoService\Services\AutoServicePackageStockPostingService;

class InvoiceController extends AutoServiceBaseController
{
    public function index()
    {
        $q = AutoServiceInvoice::query();
        if ($this->businessId()) $q->where('business_id', $this->businessId());
        return view('autoservice::invoices.index', ['invoices' => $q->orderByDesc('id')->paginate(25)]);
    }

    public function create(Request $request)
    {
        $invoice = new AutoServiceInvoice();
        if ($request->filled('job_id')) {
            $job = AutoServiceJob::with('lines')->findOrFail($request->job_id);
            $invoice->fill($job->only(['business_id','location_id','contact_id','vehicle_id']));
            $invoice->job_id = $job->id;
            $invoice->invoice_date = date('Y-m-d');
            $invoice->discount_amount = $job->discount_amount;
            $invoice->tax_amount = $job->tax_amount;
            $invoice->setRelation('lines', $job->lines);
        }
        return view('autoservice::invoices.form', $this->formData($invoice));
    }

    public function store(Request $request)
    {
        app(AutoServiceInvoiceService::class)->saveInvoice($this->invoiceData($request), $request->input('lines', []));
        return redirect()->route('autoservice.invoices.index')->with('status','Invoice saved successfully.');
    }

    public function edit($id)
    {
        return view('autoservice::invoices.form', $this->formData(AutoServiceInvoice::with('lines')->findOrFail($id)));
    }

    public function update(Request $request, $id)
    {
        $data = $this->invoiceData($request); $data['id'] = $id;
        app(AutoServiceInvoiceService::class)->saveInvoice($data, $request->input('lines', []));
        return redirect()->route('autoservice.invoices.index')->with('status','Invoice updated successfully.');
    }

    public function show($id)
    {
        return view('autoservice::invoices.show', ['invoice' => AutoServiceInvoice::with('lines','job','payments')->findOrFail($id)]);
    }

    public function print($id)
    {
        return view('autoservice::invoices.print', ['invoice' => AutoServiceInvoice::with('lines','job','payments')->findOrFail($id)]);
    }

    public function fromJob($jobId)
    {
        $job = AutoServiceJob::with('lines')->findOrFail($jobId);
        $invoice = app(AutoServiceInvoiceService::class)->makeFromJob($job);
        return redirect()->route('autoservice.invoices.edit', $invoice->id)->with('status','Invoice generated from job card.');
    }


    public function post($id)
    {
        $invoice=AutoServiceInvoice::where('business_id',$this->businessId())->findOrFail($id);
        app(AutoServicePackageStockPostingService::class)->post($invoice);
        return redirect()->route('autoservice.invoices.show',$invoice->id)->with('status','Invoice posted and stock items deducted successfully.');
    }

    private function formData($invoice)
    {
        return [
            'invoice' => $invoice,
            'vehicles' => AutoServiceVehicle::where('business_id',$this->businessId())->orderByDesc('id')->limit(100)->get(),
            'jobs' => AutoServiceJob::where('business_id',$this->businessId())->orderByDesc('id')->limit(100)->get(),
            'customers' => app(SharedCustomerAdapter::class)->list($this->businessId()),
            'products' => app(ProductPartsAdapter::class)->list($this->businessId()),
        ];
    }

    private function invoiceData(Request $request)
    {
        $data = $request->only(['contact_id','vehicle_id','job_id','invoice_date','status','discount_amount','tax_amount','paid_amount','terms','internal_note']);
        $data['business_id'] = $this->businessId();
        $data['location_id'] = $this->locationId();
        return $data;
    }
}
