<?php
namespace Modules\TeaEstateManagement\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Optional adapter into the system Finance ledger.
 * Tea remains authoritative in tea_* tables. When Finance/core accounting tables are
 * available, this service posts idempotent double-entry rows so Account Books,
 * Trial Balance, P&L and Balance Sheet see Tea operations automatically.
 * If Finance is unavailable or mappings are incomplete, the Tea operation is NOT lost:
 * tea_finance_events keeps it pending for later reconciliation.
 */
class TeaFinanceBridgeService
{
    public function __construct(private TenantContextService $context) {}

    public function available(): bool
    {
        return Schema::hasTable('accounts') && Schema::hasTable('transactions') && Schema::hasTable('account_transactions');
    }

    public function queue(string $eventType,string $sourceType,int $sourceId,int $locationId,string $documentNo,$date,float $amount,array $payload=[]): int
    {
        $businessId=$this->context->businessId();
        $row=DB::table('tea_finance_events')
            ->where('business_id',$businessId)->where('source_type',$sourceType)->where('source_id',$sourceId)->where('event_type',$eventType)->first();
        $data=['location_id'=>$locationId,'document_no'=>$documentNo,'operation_date'=>$date,'amount'=>$amount,'payload_json'=>json_encode($payload),'updated_at'=>now()];
        if ($row) { DB::table('tea_finance_events')->where('id',$row->id)->update($data); $id=(int)$row->id; }
        else { $id=(int)DB::table('tea_finance_events')->insertGetId($data+['business_id'=>$businessId,'source_type'=>$sourceType,'source_id'=>$sourceId,'event_type'=>$eventType,'status'=>'pending','attempts'=>0,'created_at'=>now()]); }
        if ($amount <= 0.000001) { DB::table('tea_finance_events')->where('id',$id)->update(['status'=>'posted','error_message'=>'Zero-value event: no ledger posting required.','posted_at'=>now(),'updated_at'=>now()]); return $id; }
        $this->post($id);
        return $id;
    }

    public function post(int $eventId): bool
    {
        $businessId=$this->context->businessId();
        $event=DB::table('tea_finance_events')->where('business_id',$businessId)->where('id',$eventId)->first();
        if (!$event) return false;
        if (!$this->available()) return $this->markPending($eventId,'Finance tables are not available.');
        try {
            $payload=json_decode((string)$event->payload_json,true) ?: [];
            $lines=$this->lines($event,$payload);
            if (!$lines) return $this->markPending($eventId,'Finance account mapping is incomplete.');
            $debits=array_sum(array_map(fn($l)=>$l['type']==='debit'?$l['amount']:0,$lines));
            $credits=array_sum(array_map(fn($l)=>$l['type']==='credit'?$l['amount']:0,$lines));
            if (abs($debits-$credits)>0.005) throw new \RuntimeException('Tea finance entry is not balanced.');
            DB::transaction(function() use($event,$eventId,$lines,$payload,$businessId){
                $transactionId=(int)($event->core_transaction_id ?: 0);
                if ($transactionId<=0 || !DB::table('transactions')->where('id',$transactionId)->where('business_id',$businessId)->exists()) {
                    $data=[
                        'business_id'=>$businessId,'location_id'=>$event->location_id,'type'=>'tea_estate',
                        'sub_type'=>substr((string)$event->event_type,0,20),'status'=>'final','payment_status'=>'paid',
                        'invoice_no'=>$event->document_no,'transaction_date'=>$event->operation_date,
                        'total_before_tax'=>$event->amount,'tax_amount'=>(float)($payload['tax_amount']??0),
                        'final_total'=>$event->amount,'created_by'=>$this->context->userId(),
                        'additional_notes'=>'Tea Estate Management / '.$event->source_type.' / '.$event->document_no,
                        'created_at'=>now(),'updated_at'=>now()
                    ];
                    $transactionId=(int)DB::table('transactions')->insertGetId($this->columns('transactions',$data));
                    DB::table('tea_finance_events')->where('id',$eventId)->update(['core_transaction_id'=>$transactionId]);
                } else {
                    DB::table('transactions')->where('id',$transactionId)->update($this->columns('transactions',[
                        'location_id'=>$event->location_id,'transaction_date'=>$event->operation_date,'final_total'=>$event->amount,'updated_at'=>now()
                    ]));
                    DB::table('account_transactions')->where('transaction_id',$transactionId)->delete();
                }
                foreach ($lines as $line) {
                    if (!$this->isValidAccount((int) $line['account_id'])) throw new \RuntimeException('Mapped Finance account is invalid or closed.');
                    $at=[
                        'business_id'=>$businessId,'location_id'=>$event->location_id,'account_id'=>$line['account_id'],
                        'amount'=>$line['amount'],'type'=>$line['type'],'txnType'=>'tea_estate','operation_date'=>$event->operation_date,
                        'created_by'=>$this->context->userId(),'transaction_id'=>$transactionId,
                        'note'=>'[TEA#'.$eventId.'] '.$event->document_no.' - '.$line['label'],'created_at'=>now(),'updated_at'=>now()
                    ];
                    DB::table('account_transactions')->insert($this->columns('account_transactions',$at));
                }
                DB::table('tea_finance_events')->where('id',$eventId)->update(['status'=>'posted','attempts'=>DB::raw('attempts + 1'),'error_message'=>null,'posted_at'=>now(),'updated_at'=>now()]);
            });
            return true;
        } catch (\Throwable $e) {
            DB::table('tea_finance_events')->where('id',$eventId)->update(['status'=>'error','attempts'=>DB::raw('attempts + 1'),'error_message'=>substr($e->getMessage(),0,1000),'updated_at'=>now()]);
            return false;
        }
    }

    public function retryPending(): int
    {
        $ids=DB::table('tea_finance_events')->where('business_id',$this->context->businessId())->whereIn('status',['pending','error'])->orderBy('id')->pluck('id');
        $count=0; foreach($ids as $id) if($this->post((int)$id)) $count++; return $count;
    }

    public function accountOptions(): array
    {
        if (!Schema::hasTable('accounts')) return [];
        $q=DB::table('accounts')->where('business_id',$this->context->businessId());
        if (Schema::hasColumn('accounts','deleted_at')) $q->whereNull('deleted_at');
        if (Schema::hasColumn('accounts','is_closed')) $q->where(function($x){$x->where('is_closed',0)->orWhereNull('is_closed');});
        return $q->orderBy('name')->pluck('name','id')->mapWithKeys(fn($v,$k)=>[(int)$k=>$v])->all();
    }

    public function isValidAccount(int $accountId): bool
    {
        if ($accountId <= 0 || !Schema::hasTable('accounts')) return false;
        $q = DB::table('accounts')->where('business_id', $this->context->businessId())->where('id', $accountId);
        if (Schema::hasColumn('accounts', 'deleted_at')) $q->whereNull('deleted_at');
        if (Schema::hasColumn('accounts', 'is_closed')) $q->where(function($x){$x->where('is_closed',0)->orWhereNull('is_closed');});
        return $q->exists();
    }

    public function mapping(string $purpose,int $locationId): ?int
    {
        $businessId=$this->context->businessId();
        $row=DB::table('tea_finance_account_mappings')->where('business_id',$businessId)->where('purpose',$purpose)
            ->whereIn('location_id',[$locationId,0])->orderByDesc('location_id')->first();
        return $row ? (int)$row->account_id : null;
    }

    private function lines($e,array $p): array
    {
        $loc=(int)$e->location_id; $amount=(float)$e->amount; $m=fn($purpose)=>$this->mapping($purpose,$loc);
        $line=fn($type,$account,$amt,$label)=>$account&&$amt>0?['type'=>$type,'account_id'=>$account,'amount'=>round($amt,4),'label'=>$label]:null;
        $lines=[];
        switch($e->event_type){
            case 'field_activity_cost':
                $cash=(int)($p['finance_account_id']??0); $lines=[$line('debit',$m('estate_operating_expense'),$amount,'Tea estate operating expense'),$line('credit',$cash,$amount,'Cash/Bank field activity cost')]; break;
            case 'harvest_inventory':
                $lines=[$line('debit',$m('green_leaf_inventory'),$amount,'Own-estate green leaf inventory'),$line('credit',$m('estate_cost_recovery'),$amount,'Estate production cost recovery')]; break;
            case 'leaf_purchase':
                $lines=[$line('debit',$m('green_leaf_inventory'),$amount,'Green leaf inventory'),$line('credit',$m('supplier_payable'),$amount,'Tea supplier payable')]; break;
            case 'purchase_payment':
                $cash=(int)($p['finance_account_id']??0); $lines=[$line('debit',$m('supplier_payable'),$amount,'Tea supplier payable'),$line('credit',$cash,$amount,'Cash/Bank payment')]; break;
            case 'processing_cost':
                $cash=(int)($p['finance_account_id']??0); $lines=[$line('debit',$m('processing_wip'),$amount,'Tea processing work in progress'),$line('credit',$cash,$amount,'Cash/Bank processing cost')]; break;
            case 'processing_transfer':
                $leaf=max(0,(float)($p['leaf_cost']??0)); $proc=max(0,(float)($p['processing_cost']??0));
                $lines=[$line('debit',$m('finished_tea_inventory'),$amount,'Finished tea inventory')];
                if($leaf>0)$lines[]=$line('credit',$m('green_leaf_inventory'),$leaf,'Green leaf inventory transfer');
                if($proc>0)$lines[]=$line('credit',$m('processing_wip'),$proc,'Processing WIP transfer');
                break;
            case 'tea_sale':
                $tax=max(0,(float)($p['tax_amount']??0)); $revenue=max(0,$amount-$tax);
                $lines=[$line('debit',$m('accounts_receivable'),$amount,'Tea accounts receivable'),$line('credit',$m('sales_revenue'),$revenue,'Tea sales revenue')];
                if($tax>0) $lines[]=$line('credit',$m('tax_payable'),$tax,'Tax payable');
                break;
            case 'tea_cogs':
                $lines=[$line('debit',$m('cogs'),$amount,'Cost of tea sold'),$line('credit',$m('finished_tea_inventory'),$amount,'Finished tea inventory')]; break;
            case 'sale_receipt':
                $cash=(int)($p['finance_account_id']??0); $lines=[$line('debit',$cash,$amount,'Cash/Bank receipt'),$line('credit',$m('accounts_receivable'),$amount,'Tea accounts receivable')]; break;
        }
        return array_values(array_filter($lines));
    }

    private function markPending(int $id,string $message): bool
    { DB::table('tea_finance_events')->where('id',$id)->update(['status'=>'pending','error_message'=>$message,'updated_at'=>now()]); return false; }

    private function columns(string $table,array $data): array
    { return array_filter($data,fn($v,$k)=>Schema::hasColumn($table,$k),ARRAY_FILTER_USE_BOTH); }
}
