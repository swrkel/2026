<?php

namespace Modules\RiceMill\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\RiceMill\Services\CustomerAccountsService;
use Modules\RiceMill\Services\ExternalMasterDataService;

class CustomerAccountsController extends BaseController
{
    public function ledger(Request $request)
    {
        $businessId=$this->bid();
        $customerId=(int)$request->input('customer_id',0);
        $customers=app(ExternalMasterDataService::class)->customers($businessId);
        $date=$this->listTools()->dateState($request,$businessId);
        $ledger=$customerId
            ? app(CustomerAccountsService::class)->ledger($businessId,$customerId,$date['from'] ?: null,$date['to'] ?: null)
            : ['opening_balance'=>0,'entries'=>collect(),'total_debit'=>0,'total_credit'=>0,'closing_balance'=>0];
        $customerName=$this->customerName($customers,$customerId);
        return view('RiceMill::customer-accounts.ledger',compact('customers','customerId','customerName','ledger'));
    }

    public function outstanding(Request $request)
    {
        if(!$request->hasAny(['range','from','to'])){$request->merge(['range'=>'all']);}
        $businessId=$this->bid();
        $customerId=(int)$request->input('customer_id',0);
        $customers=app(ExternalMasterDataService::class)->customers($businessId);
        $base=app(CustomerAccountsService::class)->outstandingQuery($businessId);
        if($customerId){$base->where('d.customer_id',$customerId);}
        $this->listTools()->applyDate($base,$request,$businessId,'d.dispatch_date');
        $this->listTools()->applySearch($base,$request,['d.dispatch_no','c.name','d.net_total']);

        $query=DB::query()->fromSub($base,'x')->where('outstanding_amount','>',0.00005);
        $summary=(clone $query)->selectRaw('COALESCE(SUM(net_total),0) invoice_total, COALESCE(SUM(paid_amount),0) paid_amount, COALESCE(SUM(outstanding_amount),0) outstanding_amount, COUNT(*) invoice_count')->first();
        $rows=$query->orderBy('due_date')->orderBy('id')->paginate($this->listPerPage($request,25))->appends($request->query());

        $advanceQ=DB::table('rcm_customer_payments')->where('business_id',$businessId)->where('status','posted');
        if($customerId){$advanceQ->where('customer_id',$customerId);}
        $unappliedAdvance=(float)$advanceQ->sum('advance_amount');

        return view('RiceMill::customer-accounts.outstanding',compact('customers','customerId','rows','summary','unappliedAdvance'));
    }

    public function aging(Request $request)
    {
        if(!$request->hasAny(['range','from','to'])){$request->merge(['range'=>'all']);}
        $businessId=$this->bid();
        $customerId=(int)$request->input('customer_id',0);
        $customers=app(ExternalMasterDataService::class)->customers($businessId);
        $date=$this->listTools()->dateState($request,$businessId);
        $rows=app(CustomerAccountsService::class)->aging($businessId,$customerId ?: null,$date['from'] ?: null,$date['to'] ?: null);
        $totals=(object)[
            'current'=>(float)$rows->sum('current'),'d1_30'=>(float)$rows->sum('d1_30'),'d31_60'=>(float)$rows->sum('d31_60'),
            'd61_90'=>(float)$rows->sum('d61_90'),'d91_120'=>(float)$rows->sum('d91_120'),'over_120'=>(float)$rows->sum('over_120'),'total'=>(float)$rows->sum('total'),
        ];
        return view('RiceMill::customer-accounts.aging',compact('customers','customerId','rows','totals'));
    }

    public function statement(Request $request)
    {
        $businessId=$this->bid();
        $customerId=(int)$request->input('customer_id',0);
        $customers=app(ExternalMasterDataService::class)->customers($businessId);
        $date=$this->listTools()->dateState($request,$businessId);
        $service=app(CustomerAccountsService::class);
        $ledger=$customerId
            ? $service->ledger($businessId,$customerId,$date['from'] ?: null,$date['to'] ?: null)
            : ['opening_balance'=>0,'entries'=>collect(),'total_debit'=>0,'total_credit'=>0,'closing_balance'=>0];
        $summary=$customerId ? $service->customerSummary($businessId,$customerId) : (object)['invoice_total'=>0,'paid_allocated'=>0,'outstanding'=>0,'unapplied_advance'=>0,'net_receivable'=>0];
        $customerName=$this->customerName($customers,$customerId);
        return view('RiceMill::customer-accounts.statement',compact('customers','customerId','customerName','ledger','summary'));
    }

    private function customerName(array $customers,int $customerId): string
    {
        foreach($customers as $customer){if((int)$customer['id']===$customerId){return (string)$customer['name'];}}
        return $customerId ? 'Customer #'.$customerId : '';
    }
}
