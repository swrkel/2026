<?php

namespace Modules\RiceMill\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\RiceMill\Models\CustomerPayment;

/**
 * Rice Mill customer receivables projection.
 *
 * Customers remain the host ERP Contacts master. Receivables are derived from
 * approved Rice Mill Sales Invoices plus posted Rice Mill Customer Payments.
 * Every payment/reversal is also emitted through the existing Finance outbox so
 * Finance/Customers integrations can consume one authoritative module event
 * instead of duplicating customer masters inside Rice Mill.
 */
class CustomerAccountsService
{
    private const EPSILON = 0.00005;

    public function __construct(
        private NumberSeriesService $numbers,
        private FinanceIntegrationService $finance
    ) {}

    /** Approved Rice Mill invoices with paid/outstanding amounts and customer terms. */
    public function outstandingQuery(int $businessId): Builder
    {
        $paid = DB::table('rcm_customer_payment_allocations as a')
            ->join('rcm_customer_payments as p', function ($join) {
                $join->on('p.id','=','a.payment_id')
                    ->on('p.business_id','=','a.business_id')
                    ->where('p.status','=','posted');
            })
            ->where('a.business_id',$businessId)
            ->groupBy('a.dispatch_id')
            ->select('a.dispatch_id', DB::raw('SUM(a.amount) as paid_amount'));

        return DB::table('rcm_dispatches as d')
            ->leftJoinSub($paid,'pa',fn($join)=>$join->on('pa.dispatch_id','=','d.id'))
            ->leftJoin('contacts as c', function ($join) use ($businessId) {
                $join->on('c.id','=','d.customer_id')->where('c.business_id','=',$businessId);
            })
            ->where('d.business_id',$businessId)
            ->where('d.status','approved')
            ->select([
                'd.id','d.business_id','d.dispatch_no','d.dispatch_date','d.created_at',
                'd.customer_id','d.location_id','d.store_id','d.net_total',
                DB::raw("COALESCE(c.name, CONCAT('Customer #', d.customer_id)) as customer_name"),
                'c.pay_term_number','c.pay_term_type',
                DB::raw('COALESCE(pa.paid_amount,0) as paid_amount'),
                DB::raw('GREATEST(d.net_total-COALESCE(pa.paid_amount,0),0) as outstanding_amount'),
                DB::raw("CASE
                    WHEN c.pay_term_type='months' AND COALESCE(c.pay_term_number,0)>0 THEN DATE_ADD(d.dispatch_date, INTERVAL c.pay_term_number MONTH)
                    WHEN c.pay_term_type='days' AND COALESCE(c.pay_term_number,0)>0 THEN DATE_ADD(d.dispatch_date, INTERVAL c.pay_term_number DAY)
                    ELSE d.dispatch_date END as due_date")
            ]);
    }

    public function outstandingForCustomer(int $businessId, int $customerId): Collection
    {
        return $this->outstandingQuery($businessId)
            ->where('d.customer_id',$customerId)
            ->having('outstanding_amount','>',self::EPSILON)
            ->orderBy('due_date')
            ->orderBy('d.id')
            ->get();
    }

    public function customerSummary(int $businessId, ?int $customerId = null): object
    {
        $q=$this->outstandingQuery($businessId);
        if($customerId){$q->where('d.customer_id',$customerId);}
        $rows=$q->get();
        $invoiceTotal=(float)$rows->sum(fn($r)=>(float)$r->net_total);
        $paid=(float)$rows->sum(fn($r)=>(float)$r->paid_amount);
        $outstanding=(float)$rows->sum(fn($r)=>(float)$r->outstanding_amount);

        $advanceQ=DB::table('rcm_customer_payments')
            ->where('business_id',$businessId)->where('status','posted');
        if($customerId){$advanceQ->where('customer_id',$customerId);}
        $advance=(float)$advanceQ->sum('advance_amount');

        return (object)[
            'invoice_total'=>$invoiceTotal,
            'paid_allocated'=>$paid,
            'outstanding'=>$outstanding,
            'unapplied_advance'=>$advance,
            'net_receivable'=>$outstanding-$advance,
        ];
    }

    /**
     * Build a chronological ledger using approved Sales Invoices as debit and
     * posted Customer Payments as credit. Opening balance is calculated from
     * activity before the selected start date.
     */
    public function ledger(int $businessId, int $customerId, ?string $from, ?string $to): array
    {
        $invoiceBase=DB::table('rcm_dispatches')
            ->where('business_id',$businessId)
            ->where('customer_id',$customerId)
            ->where('status','approved');
        $paymentBase=DB::table('rcm_customer_payments')
            ->where('business_id',$businessId)
            ->where('customer_id',$customerId)
            ->whereIn('status',['posted','reversed']);

        $openingDebit=0.0;$openingCredit=0.0;
        if($from){
            $openingDebit=(float)(clone $invoiceBase)->whereDate('dispatch_date','<',$from)->sum('net_total');
            $openingCredit=(float)(clone $paymentBase)->whereDate('payment_date','<',$from)->sum('amount');
            $openingDebit+=(float)(clone $paymentBase)->where('status','reversed')->whereNotNull('reversed_at')->whereDate('reversed_at','<',$from)->sum('amount');
        }

        $invoiceQ=(clone $invoiceBase)->select(['id','dispatch_no','dispatch_date','created_at','net_total','note']);
        $paymentQ=(clone $paymentBase)->select(['id','payment_no','payment_date','amount','allocated_amount','advance_amount','method','reference_no','note','status','reversed_at','reversal_note']);
        if($from){$invoiceQ->whereDate('dispatch_date','>=',$from);}
        if($to){$invoiceQ->whereDate('dispatch_date','<=',$to);}

        $entries=collect();
        foreach($invoiceQ->get() as $r){
            $time=Carbon::parse($r->dispatch_date)->startOfDay();
            if($r->created_at){$created=Carbon::parse($r->created_at);$time->setTime($created->hour,$created->minute,$created->second);}
            $entries->push((object)[
                'sort_time'=>$time,'date'=>$time,'type'=>'Sales Invoice','reference'=>$r->dispatch_no,
                'debit'=>(float)$r->net_total,'credit'=>0.0,'method'=>'','note'=>$r->note,
                'dispatch_id'=>(int)$r->id,'payment_id'=>null,
            ]);
        }
        foreach($paymentQ->get() as $r){
            $time=Carbon::parse($r->payment_date);
            $note=trim((string)$r->note);
            if((float)$r->advance_amount>self::EPSILON){
                $note=trim($note.' '.sprintf('(Unapplied advance %.4f)',(float)$r->advance_amount));
            }
            $paymentInRange=(!$from || $time->toDateString()>=$from) && (!$to || $time->toDateString()<=$to);
            if($paymentInRange){
                $entries->push((object)[
                    'sort_time'=>$time,'date'=>$time,'type'=>'Customer Payment','reference'=>$r->payment_no,
                    'debit'=>0.0,'credit'=>(float)$r->amount,'method'=>$this->methodLabel((string)$r->method),
                    'note'=>$note,'dispatch_id'=>null,'payment_id'=>(int)$r->id,
                ]);
            }
            if($r->status==='reversed' && $r->reversed_at){
                $reverseTime=Carbon::parse($r->reversed_at);
                $inRange=(!$from || $reverseTime->toDateString()>=$from) && (!$to || $reverseTime->toDateString()<=$to);
                if($inRange){
                    $entries->push((object)[
                        'sort_time'=>$reverseTime,'date'=>$reverseTime,'type'=>'Payment Reversal','reference'=>$r->payment_no,
                        'debit'=>(float)$r->amount,'credit'=>0.0,'method'=>$this->methodLabel((string)$r->method),
                        'note'=>(string)($r->reversal_note ?: 'Payment reversed'),'dispatch_id'=>null,'payment_id'=>(int)$r->id,
                    ]);
                }
            }
        }

        $entries=$entries->sortBy(fn($r)=>$r->sort_time->format('Y-m-d H:i:s').sprintf('%012d',$r->dispatch_id ?? ($r->payment_id ?? 0)))->values();
        $balance=$openingDebit-$openingCredit;
        foreach($entries as $entry){
            $balance+=(float)$entry->debit-(float)$entry->credit;
            $entry->balance=$balance;
        }

        return [
            'opening_balance'=>$openingDebit-$openingCredit,
            'entries'=>$entries,
            'total_debit'=>(float)$entries->sum('debit'),
            'total_credit'=>(float)$entries->sum('credit'),
            'closing_balance'=>$balance,
        ];
    }

    public function aging(int $businessId, ?int $customerId = null, ?string $from = null, ?string $to = null): Collection
    {
        $query=$this->outstandingQuery($businessId)
            ->when($customerId,fn($q)=>$q->where('d.customer_id',$customerId));
        if ($from) {$query->whereDate('d.dispatch_date','>=',$from);}
        if ($to) {$query->whereDate('d.dispatch_date','<=',$to);}
        $rows=$query->having('outstanding_amount','>',self::EPSILON)->get();

        return $rows->groupBy('customer_id')->map(function(Collection $items,$customerId){
            $bucket=['current'=>0.0,'d1_30'=>0.0,'d31_60'=>0.0,'d61_90'=>0.0,'d91_120'=>0.0,'over_120'=>0.0];
            foreach($items as $row){
                $due=Carbon::parse($row->due_date);
                $days=max(0,$due->diffInDays(today(),false));
                $amount=(float)$row->outstanding_amount;
                if($due->isToday() || $due->isFuture()){$bucket['current']+=$amount;}
                elseif($days<=30){$bucket['d1_30']+=$amount;}
                elseif($days<=60){$bucket['d31_60']+=$amount;}
                elseif($days<=90){$bucket['d61_90']+=$amount;}
                elseif($days<=120){$bucket['d91_120']+=$amount;}
                else{$bucket['over_120']+=$amount;}
            }
            return (object)array_merge($bucket,[
                'customer_id'=>(int)$customerId,
                'customer_name'=>(string)($items->first()->customer_name ?? ('Customer #'.$customerId)),
                'invoice_count'=>$items->count(),
                'total'=>(float)$items->sum('outstanding_amount'),
            ]);
        })->sortByDesc('total')->values();
    }

    public function createPayment(int $businessId,int $userId,array $data,array $allocations): CustomerPayment
    {
        $customerId=(int)$data['customer_id'];
        $amount=round((float)$data['amount'],4);
        if($amount<=0){throw ValidationException::withMessages(['amount'=>'Payment amount must be greater than zero.']);}

        $customer=DB::table('contacts')->where('business_id',$businessId)->where('id',$customerId)
            ->whereIn('type',['customer','both'])->first(['id','name']);
        if(!$customer){throw ValidationException::withMessages(['customer_id'=>'The selected customer is not available for this business.']);}

        $normalized=[];
        foreach($allocations as $row){
            $dispatchId=(int)($row['dispatch_id'] ?? 0);
            $value=round((float)($row['amount'] ?? 0),4);
            if($dispatchId>0 && $value>self::EPSILON){$normalized[$dispatchId]=round(($normalized[$dispatchId]??0)+$value,4);}
        }
        $allocated=round(array_sum($normalized),4);
        if($allocated-$amount>self::EPSILON){
            throw ValidationException::withMessages(['amount'=>'Allocated bill amounts cannot exceed the payment amount.']);
        }

        $payment=DB::transaction(function() use($businessId,$userId,$data,$customerId,$amount,$normalized,$allocated){
            $dispatchIds=array_keys($normalized);
            if($dispatchIds){
                $dispatches=DB::table('rcm_dispatches')->where('business_id',$businessId)
                    ->where('customer_id',$customerId)->where('status','approved')
                    ->whereIn('id',$dispatchIds)->orderBy('id')->lockForUpdate()->get(['id','dispatch_no','net_total'])->keyBy('id');
                if($dispatches->count()!==count($dispatchIds)){
                    throw ValidationException::withMessages(['allocations'=>'One or more selected Sales Invoices are no longer available for this customer.']);
                }

                $already=DB::table('rcm_customer_payment_allocations as a')
                    ->join('rcm_customer_payments as p',function($join){
                        $join->on('p.id','=','a.payment_id')->on('p.business_id','=','a.business_id')->where('p.status','=','posted');
                    })
                    ->where('a.business_id',$businessId)->whereIn('a.dispatch_id',$dispatchIds)
                    ->groupBy('a.dispatch_id')
                    ->select('a.dispatch_id',DB::raw('SUM(a.amount) as paid_amount'))
                    ->get()->pluck('paid_amount','dispatch_id');

                foreach($normalized as $dispatchId=>$value){
                    $invoice=$dispatches->get($dispatchId);
                    $open=round((float)$invoice->net_total-(float)($already[$dispatchId]??0),4);
                    if($value-$open>self::EPSILON){
                        throw ValidationException::withMessages([
                            'allocations'=>'Allocation for '.$invoice->dispatch_no.' exceeds its current outstanding amount of '.number_format(max(0,$open),4,'.','').'.'
                        ]);
                    }
                }
            }

            $payment=CustomerPayment::create([
                'business_id'=>$businessId,
                'payment_no'=>$this->numbers->next($businessId,'customer_payment','RCP-'),
                'customer_id'=>$customerId,
                'payment_date'=>$data['payment_date'],
                'amount'=>$amount,
                'allocated_amount'=>$allocated,
                'advance_amount'=>round($amount-$allocated,4),
                'method'=>$data['method'],
                'reference_no'=>$data['reference_no'] ?? null,
                'cheque_number'=>$data['cheque_number'] ?? null,
                'bank_name'=>$data['bank_name'] ?? null,
                'note'=>$data['note'] ?? null,
                'status'=>'posted',
                'created_by'=>$userId,
            ]);

            if($normalized){
                $now=now();$rows=[];
                foreach($normalized as $dispatchId=>$value){$rows[]=[
                    'business_id'=>$businessId,'payment_id'=>$payment->id,'dispatch_id'=>$dispatchId,
                    'amount'=>$value,'created_at'=>$now,'updated_at'=>$now,
                ];}
                DB::table('rcm_customer_payment_allocations')->insert($rows);
            }
            return $payment;
        });

        $this->finance->queue($businessId,'customer_payment_received','customer_payment',$payment->id,[
            'payment_no'=>$payment->payment_no,'customer_id'=>$customerId,'amount'=>(float)$payment->amount,
            'allocated_amount'=>(float)$payment->allocated_amount,'advance_amount'=>(float)$payment->advance_amount,
            'payment_date'=>(string)$payment->payment_date,'method'=>$payment->method,'reference_no'=>$payment->reference_no,
            'allocations'=>$normalized,
        ]);
        return $payment->fresh('allocations');
    }

    public function reversePayment(int $businessId,int $paymentId,int $userId,?string $reason=null): CustomerPayment
    {
        $payment=DB::transaction(function() use($businessId,$paymentId,$userId,$reason){
            $payment=CustomerPayment::forBusiness($businessId)->lockForUpdate()->findOrFail($paymentId);
            if($payment->status!=='posted'){throw new \RuntimeException('Only posted customer payments can be reversed.');}
            $payment->update([
                'status'=>'reversed','reversed_by'=>$userId,'reversed_at'=>now(),
                'reversal_note'=>trim((string)$reason) ?: 'Reversed by authorized user',
            ]);
            return $payment;
        });

        $this->finance->queue($businessId,'customer_payment_reversed','customer_payment',$payment->id,[
            'payment_no'=>$payment->payment_no,'customer_id'=>$payment->customer_id,'amount'=>(float)$payment->amount,
            'reversed_by'=>$userId,'reason'=>$payment->reversal_note,
        ]);
        return $payment;
    }

    public function paymentAllocations(int $businessId,int $paymentId): Collection
    {
        return DB::table('rcm_customer_payment_allocations as a')
            ->join('rcm_dispatches as d',function($join) use($businessId){$join->on('d.id','=','a.dispatch_id')->where('d.business_id','=',$businessId);})
            ->where('a.business_id',$businessId)->where('a.payment_id',$paymentId)
            ->orderBy('d.dispatch_date')->orderBy('d.id')
            ->get(['a.dispatch_id','d.dispatch_no','d.dispatch_date','d.net_total','a.amount']);
    }

    public function methodLabel(string $method): string
    {
        return [
            'cash'=>'Cash','bank_transfer'=>'Bank Transfer','card'=>'Card','cheque'=>'Cheque','other'=>'Other'
        ][$method] ?? ucwords(str_replace('_',' ',$method));
    }
}
