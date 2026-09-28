<?php
namespace Modules\AutoService\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\AutoService\Entities\AutoServiceInvoice;
use Modules\AutoService\Entities\AutoServiceJob;
use Modules\AutoService\Entities\AutoServicePayment;
use Modules\AutoService\Entities\AutoServiceTimeline;

class PaymentController extends AutoServiceBaseController
{
    public function index()
    {
        $q = AutoServicePayment::query();
        if ($this->businessId()) $q->where('business_id', $this->businessId());
        $payments = $q->orderByDesc('payment_date')->orderByDesc('id')->paginate(25);
        return view('autoservice::payments.index', compact('payments'));
    }

    public function create(Request $request)
    {
        $payment = new AutoServicePayment(['payment_date' => date('Y-m-d'), 'payment_method' => 'cash']);
        if ($request->filled('invoice_id')) {
            $invoice = AutoServiceInvoice::findOrFail($request->invoice_id);
            $payment->invoice_id = $invoice->id;
            $payment->job_id = $invoice->job_id;
            $payment->contact_id = $invoice->contact_id;
            $payment->amount = $invoice->balance_amount;
        }
        return view('autoservice::payments.form', ['payment' => $payment, 'invoices' => $this->invoiceList()]);
    }

    public function store(Request $request)
    {
        $this->savePayment($request);
        return redirect()->route('autoservice.payments.index')->with('status','Payment saved successfully.');
    }

    public function edit($id)
    {
        return view('autoservice::payments.form', ['payment' => AutoServicePayment::findOrFail($id), 'invoices' => $this->invoiceList()]);
    }

    public function update(Request $request, $id)
    {
        $this->savePayment($request, $id);
        return redirect()->route('autoservice.payments.index')->with('status','Payment updated successfully.');
    }

    private function savePayment(Request $request, $id = null)
    {
        return DB::transaction(function () use ($request, $id) {
            $data = $request->only(['invoice_id','job_id','contact_id','payment_date','payment_method','reference_no','amount','note','status']);
            $data['business_id'] = $this->businessId();
            $data['location_id'] = $this->locationId();
            $data['created_by'] = auth()->id();
            $payment = $id ? AutoServicePayment::findOrFail($id) : new AutoServicePayment();
            $payment->fill($data);
            if ($payment->invoice_id && !$payment->job_id) {
                $invoice = AutoServiceInvoice::find($payment->invoice_id);
                if ($invoice) {
                    $payment->job_id = $invoice->job_id;
                    $payment->contact_id = $invoice->contact_id;
                }
            }
            $payment->save();
            $this->recalculateInvoice($payment->invoice_id);
            return $payment;
        });
    }

    private function recalculateInvoice($invoiceId)
    {
        if (!$invoiceId) return;
        $invoice = AutoServiceInvoice::find($invoiceId);
        if (!$invoice) return;
        $paid = AutoServicePayment::where('invoice_id',$invoice->id)->where('status','received')->sum('amount');
        $invoice->paid_amount = $paid;
        $invoice->balance_amount = max(0, (float)$invoice->total_amount - (float)$paid);
        $invoice->status = $invoice->balance_amount <= 0 ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid');
        $invoice->save();
        if ($invoice->job_id) {
            AutoServiceJob::where('id',$invoice->job_id)->update(['paid_amount'=>$invoice->paid_amount,'balance_amount'=>$invoice->balance_amount,'status'=>$invoice->status === 'paid' ? 'invoiced_paid' : 'invoiced']);
        }
        if ($invoice->vehicle_id) {
            AutoServiceTimeline::create(['business_id'=>$invoice->business_id,'location_id'=>$invoice->location_id,'vehicle_id'=>$invoice->vehicle_id,'job_id'=>$invoice->job_id,'event_type'=>'payment_saved','title'=>'Payment Saved','description'=>'Payment updated for invoice '.$invoice->invoice_no.'. Paid: '.number_format((float)$paid,2),'event_at'=>now()]);
        }
    }

    private function invoiceList()
    {
        $q = AutoServiceInvoice::query();
        if ($this->businessId()) $q->where('business_id', $this->businessId());
        return $q->orderByDesc('id')->limit(100)->get();
    }
}
