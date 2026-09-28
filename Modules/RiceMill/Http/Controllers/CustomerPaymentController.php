<?php

namespace Modules\RiceMill\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Modules\RiceMill\Models\CustomerPayment;
use Modules\RiceMill\Services\CustomerAccountsService;
use Modules\RiceMill\Services\ExternalMasterDataService;
use Modules\RiceMill\Services\NumberSeriesService;

class CustomerPaymentController extends BaseController
{
    public function index(Request $request)
    {
        $businessId=$this->bid();
        $customerId=(int)$request->input('customer_id',0);
        $customers=app(ExternalMasterDataService::class)->customers($businessId);
        $query=DB::table('rcm_customer_payments as p')
            ->leftJoin('contacts as c',function($join) use($businessId){$join->on('c.id','=','p.customer_id')->where('c.business_id','=',$businessId);})
            ->where('p.business_id',$businessId)
            ->select(['p.*',DB::raw("COALESCE(c.name, CONCAT('Customer #',p.customer_id)) as customer_name")]);
        if($customerId){$query->where('p.customer_id',$customerId);}
        $this->listTools()->applySearch($query,$request,['p.payment_no','c.name','p.reference_no','p.cheque_number','p.bank_name','p.method','p.status','p.amount']);
        $this->listTools()->applyDate($query,$request,$businessId,'p.payment_date',true);
        $rows=$query->orderByDesc('p.payment_date')->orderByDesc('p.id')->paginate($this->listPerPage($request,25))->appends($request->query());
        return view('RiceMill::customer-payments.index',compact('customers','customerId','rows'));
    }

    public function create(Request $request)
    {
        $businessId=$this->bid();
        $customers=app(ExternalMasterDataService::class)->customers($businessId);
        $customerId=(int)$request->input('customer_id',0);
        $invoiceId=(int)$request->input('invoice_id',0);
        $preview=app(NumberSeriesService::class)->peek($businessId,'customer_payment','RCP-');
        return view('RiceMill::customer-payments.create',compact('customers','customerId','invoiceId','preview'));
    }

    public function outstanding(int $customer)
    {
        $businessId=$this->bid();
        $exists=DB::table('contacts')->where('business_id',$businessId)->where('id',$customer)->whereIn('type',['customer','both'])->exists();
        abort_unless($exists,404);
        $rows=app(CustomerAccountsService::class)->outstandingForCustomer($businessId,$customer)->map(function($row){
            return [
                'id'=>(int)$row->id,'invoice_no'=>(string)$row->dispatch_no,'invoice_date'=>(string)$row->dispatch_date,
                'due_date'=>(string)$row->due_date,'invoice_amount'=>(float)$row->net_total,'paid_amount'=>(float)$row->paid_amount,
                'outstanding_amount'=>(float)$row->outstanding_amount,'view_url'=>route('rice-mill.dispatch.show',$row->id),
            ];
        })->values();
        $summary=app(CustomerAccountsService::class)->customerSummary($businessId,$customer);
        return response()->json(['data'=>$rows,'summary'=>$summary]);
    }

    public function store(Request $request)
    {
        $data=$request->validate([
            'customer_id'=>'required|integer|min:1','payment_date'=>'required|date','amount'=>'required|numeric|min:0.0001',
            'method'=>['required',Rule::in(['cash','bank_transfer','card','cheque','other'])],
            'reference_no'=>'nullable|string|max:100','cheque_number'=>'nullable|string|max:100','bank_name'=>'nullable|string|max:150','note'=>'nullable|string|max:1000',
            'allocations'=>'nullable|array','allocations.*.dispatch_id'=>'required_with:allocations|integer|min:1','allocations.*.amount'=>'required_with:allocations|numeric|min:0',
        ]);
        $payment=app(CustomerAccountsService::class)->createPayment($this->bid(),$this->uid(),$data,(array)$request->input('allocations',[]));
        return redirect()->route('rice-mill.customer-payments.show',$payment->id)->with('status','Customer payment posted successfully.');
    }

    public function show(int $id)
    {
        $businessId=$this->bid();
        $payment=CustomerPayment::forBusiness($businessId)->findOrFail($id);
        $customer=DB::table('contacts')->where('business_id',$businessId)->where('id',$payment->customer_id)->first(['id','name']);
        $allocations=app(CustomerAccountsService::class)->paymentAllocations($businessId,$payment->id);
        return view('RiceMill::customer-payments.show',compact('payment','customer','allocations'));
    }

    public function reverse(Request $request,int $id)
    {
        $data=$request->validate(['reversal_note'=>'nullable|string|max:500']);
        app(CustomerAccountsService::class)->reversePayment($this->bid(),$id,$this->uid(),$data['reversal_note'] ?? null);
        return redirect()->route('rice-mill.customer-payments.show',$id)->with('status','Customer payment reversed. Its allocations no longer reduce outstanding invoices.');
    }
}
