<?php
namespace Modules\RiceMill\Services;

use App\AccountTransaction;
use App\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\RiceMill\Models\{Dispatch,PaddyPurchase,PaddyReceipt,ProductionBatch,Setting};

/**
 * Writes Rice Mill financial/stock movements into the ERP's existing Finance
 * account-book tables.  Rice Mill owns the orchestration; Finance remains the
 * system of record because every entry is an ordinary transactions /
 * account_transactions row.
 */
class FinanceAccountPostingService
{
    private array $columns = [];

    public function available(): bool
    {
        return class_exists(Transaction::class)
            && class_exists(AccountTransaction::class)
            && Schema::hasTable('transactions')
            && Schema::hasTable('account_transactions')
            && Schema::hasTable('accounts');
    }

    /** Replace Purchase Order transit-only stock posting with Product stock Account Books. */
    public function syncPurchaseOrder(
        int $businessId,
        int $userId,
        PaddyPurchase $purchase,
        Transaction $transaction,
        string $paymentMethod,
        int $paymentAccountId,
        int $payableAccountId
    ): void {
        if (! $this->available()) return;

        $transaction->refresh();
        $this->clearRows((int)$transaction->id, [
            'rice_mill_purchase_stock',
            'rice_mill_purchase_payable',
            'rice_mill_purchase_payment_fallback',
        ]);

        // TransactionUtil creates Goods in Transit for an ordered purchase. Rice
        // Mill has its own Product->Stock Account mapping, therefore remove only
        // that generic transit row on this Rice Mill transaction and replace it
        // with the mapped Product stock Account Books below.
        $gitIds = DB::table('accounts')
            ->where('business_id',$businessId)
            ->where(function($q){
                $q->whereRaw('LOWER(name) = ?', ['goods in transit'])
                  ->orWhereRaw('LOWER(name) LIKE ?', ['%goods in transit%']);
            })
            ->pluck('id')->map(fn($v)=>(int)$v)->all();
        if ($gitIds) {
            DB::table('account_transactions')
                ->where('transaction_id',(int)$transaction->id)
                ->whereNull('transaction_payment_id')
                ->whereIn('account_id',$gitIds)
                ->delete();
        }

        $lines = DB::table('rcm_paddy_purchase_lines as pl')
            ->leftJoin('rcm_paddy_varieties as pv','pv.id','=','pl.paddy_variety_id')
            ->where('pl.business_id',$businessId)
            ->where('pl.purchase_id',(int)$purchase->id)
            ->get(['pl.id','pl.line_total','pv.paddy_product_id']);

        $stockAmounts = [];
        $base = 0.0;
        foreach ($lines as $line) {
            $amount = max(0.0,(float)$line->line_total);
            if ($amount <= 0) continue;
            $base += $amount;
            $accountId = $this->stockAccountForProduct(
                $businessId,
                (int)($line->paddy_product_id ?? 0),
                ['Raw Material Account','Finished Goods Account','Stock Account']
            );
            if ($accountId > 0) {
                $stockAmounts[$accountId] = ($stockAmounts[$accountId] ?? 0.0) + $amount;
            }
        }

        $target = round((float)$purchase->net_total,4);
        if ($target <= 0) return;
        if (! $stockAmounts) {
            $fallback = $this->accountByNames($businessId,['Raw Material Account','Finished Goods Account','Stock Account']);
            if ($fallback > 0) $stockAmounts[$fallback] = $target;
        } elseif ($base > 0 && abs($base-$target) > 0.00005) {
            $factor = $target / $base;
            foreach ($stockAmounts as $id => $amount) $stockAmounts[$id] = round($amount*$factor,4);
            $this->fixRounding($stockAmounts,$target);
        }

        $note = 'Rice Mill Purchase Order: '.$purchase->purchase_no;
        foreach ($stockAmounts as $accountId => $amount) {
            $this->writeRow($businessId,$userId,$transaction,'debit',(int)$accountId,(float)$amount,'rice_mill_purchase_stock',$note);
        }

        if ($paymentMethod === 'credit_purchase') {
            if ($payableAccountId > 0) {
                $this->writeRow($businessId,$userId,$transaction,'credit',$payableAccountId,$target,'rice_mill_purchase_payable',$note.' | Due');
            }
        } elseif ($paymentAccountId > 0) {
            // The standard Purchase listener normally creates this row. Add a
            // fallback only if that standard posting did not materialise.
            $exists = DB::table('account_transactions')
                ->where('transaction_id',(int)$transaction->id)
                ->where('account_id',$paymentAccountId)
                ->where('type','credit')
                ->whereNull('deleted_at')
                ->exists();
            if (! $exists) {
                $this->writeRow($businessId,$userId,$transaction,'credit',$paymentAccountId,$target,'rice_mill_purchase_payment_fallback',$note);
            }
        }
    }

    /** Stock receipt -> Paddy stock Account Book / mapped Current Liability. */
    public function syncPaddyReceipt(int $businessId,int $userId,PaddyReceipt $receipt): void
    {
        if (! $this->available()) return;
        $transaction = $this->ensureTransaction($businessId,$userId,'purchase','rice_mill_paddy_receipt','RM-RCV-'.$receipt->receipt_no,[
            'location_id'=>$receipt->location_id,
            'store_id'=>$receipt->store_id,
            'contact_id'=>$receipt->supplier_id,
            'transaction_date'=>$receipt->received_at ?: now(),
            'invoice_no'=>$receipt->receipt_no,
            'final_total'=>0,
            'status'=>'received',
            'payment_status'=>'due',
            'additional_notes'=>'Rice Mill Receive Paddy: '.$receipt->receipt_no,
        ]);
        if (! $transaction) return;

        $this->clearRows((int)$transaction->id,['rice_mill_receipt_stock','rice_mill_receipt_payable']);

        $productId = (int) DB::table('rcm_paddy_varieties')->where('business_id',$businessId)
            ->where('id',(int)$receipt->paddy_variety_id)->value('paddy_product_id');
        $stockAccount = $this->stockAccountForProduct($businessId,$productId,['Raw Material Account','Finished Goods Account','Stock Account']);
        $value = $this->receiptValue($businessId,$receipt);
        $payable = $this->paddyPayableAccount($businessId);

        if ($value > 0 && $stockAccount > 0) {
            $note = 'Receive Paddy: '.$receipt->receipt_no;
            $this->updateTransactionTotal($transaction,$value);
            $this->writeRow($businessId,$userId,$transaction,'debit',$stockAccount,$value,'rice_mill_receipt_stock',$note);
            if ($payable > 0) {
                $this->writeRow($businessId,$userId,$transaction,'credit',$payable,$value,'rice_mill_receipt_payable',$note);
            }
        }
    }

    /** Convert Paddy stock to Rice/By-product stock Account Books at production cost. */
    public function syncProduction(int $businessId,int $userId,ProductionBatch $batch): void
    {
        if (! $this->available()) return;
        $batch->refresh();
        $transaction = $this->ensureTransaction($businessId,$userId,'expense','rice_mill_production','RM-PROD-'.$batch->batch_no,[
            'location_id'=>$batch->location_id,
            'store_id'=>$batch->store_id,
            'transaction_date'=>$batch->completed_at ?: $batch->started_at ?: now(),
            'ref_no'=>'RM-PROD-'.$batch->batch_no,
            'final_total'=>(float)$batch->production_cost,
            'status'=>'final',
            'payment_status'=>'due',
            'additional_notes'=>'Rice Mill Production: '.$batch->batch_no,
        ]);
        if (! $transaction) return;

        $this->clearRows((int)$transaction->id,[
            'rice_mill_production_input_stock','rice_mill_production_output_stock','rice_mill_production_overhead_payable'
        ]);

        $inputs = DB::table('rcm_production_inputs as pi')
            ->join('rcm_paddy_lots as l','l.id','=','pi.paddy_lot_id')
            ->leftJoin('rcm_paddy_varieties as pv','pv.id','=','l.paddy_variety_id')
            ->leftJoin('rcm_paddy_receipts as r','r.id','=','l.receipt_id')
            ->leftJoin('rcm_paddy_purchase_lines as pl',function($j){
                $j->on('pl.purchase_id','=','r.purchase_id')->on('pl.paddy_variety_id','=','l.paddy_variety_id');
            })
            ->where('pi.business_id',$businessId)
            ->where('pi.production_batch_id',(int)$batch->id)
            ->orderBy('pi.id')->orderBy('pl.id')
            ->get(['pi.id','pi.quantity','pv.paddy_product_id','pl.unit_rate']);

        $seenInput=[]; $inputAmounts=[]; $materialCost=0.0;
        foreach ($inputs as $row) {
            if (isset($seenInput[(int)$row->id])) continue;
            $seenInput[(int)$row->id]=true;
            $amount=max(0.0,(float)$row->quantity * (float)($row->unit_rate ?? 0));
            if ($amount<=0) continue;
            $account=$this->stockAccountForProduct($businessId,(int)($row->paddy_product_id??0),['Raw Material Account','Finished Goods Account','Stock Account']);
            if($account>0) $inputAmounts[$account]=($inputAmounts[$account]??0)+$amount;
            $materialCost += $amount;
        }

        $outputRows=DB::table('rcm_production_outputs')->where('business_id',$businessId)
            ->where('production_batch_id',(int)$batch->id)->get(['output_type','product_id','quantity']);
        $outputWeights=[]; $totalWeight=0.0;
        foreach($outputRows as $row){
            $qty=max(0.0,(float)$row->quantity); if($qty<=0) continue;
            $sourceId=0;
            if((string)$row->output_type==='rice' && (int)$row->product_id>0){
                if(Schema::hasColumn('rcm_products','products_new_product_id')){
                    $sourceId=(int)DB::table('rcm_products')->where('business_id',$businessId)->where('id',(int)$row->product_id)->value('products_new_product_id');
                }
            } elseif (preg_match('/^pn_(\d+)$/',(string)$row->output_type,$m)) {
                $sourceId=(int)$m[1];
            }
            $account=$this->stockAccountForProduct($businessId,$sourceId,['Finished Goods Account','Stock Account','Raw Material Account']);
            if($account>0){$outputWeights[$account]=($outputWeights[$account]??0)+$qty;$totalWeight+=$qty;}
        }

        $productionCost=max(0.0,(float)$batch->production_cost);
        $outputAmounts=[];
        if($productionCost>0 && $outputWeights && $totalWeight>0){
            foreach($outputWeights as $account=>$qty){$outputAmounts[$account]=round($productionCost*$qty/$totalWeight,4);}
            $this->fixRounding($outputAmounts,$productionCost);
        }

        $note='Rice Mill Production: '.$batch->batch_no;
        foreach($inputAmounts as $account=>$amount){$this->writeRow($businessId,$userId,$transaction,'credit',(int)$account,(float)$amount,'rice_mill_production_input_stock',$note);}
        foreach($outputAmounts as $account=>$amount){$this->writeRow($businessId,$userId,$transaction,'debit',(int)$account,(float)$amount,'rice_mill_production_output_stock',$note);}
        $overhead=max(0.0,$productionCost-$materialCost);
        $payable=$this->paddyPayableAccount($businessId) ?: $this->accountByNames($businessId,['Accounts Payable']);
        if($overhead>0 && $payable>0){$this->writeRow($businessId,$userId,$transaction,'credit',$payable,$overhead,'rice_mill_production_overhead_payable',$note);}
    }

    /** Approved Sales/Dispatch -> A/R + Income and stock + COGS Account Books. */
    public function syncDispatch(int $businessId,int $userId,Dispatch $dispatch): void
    {
        if (! $this->available()) return;
        $dispatch->refresh();
        if ((string)$dispatch->status !== 'approved') return;
        $transaction=$this->ensureTransaction($businessId,$userId,'sell','rice_mill_dispatch','RM-SALE-'.$dispatch->dispatch_no,[
            'location_id'=>$dispatch->location_id,'store_id'=>$dispatch->store_id,'contact_id'=>$dispatch->customer_id,
            'transaction_date'=>$dispatch->dispatch_date,'invoice_no'=>$dispatch->dispatch_no,'ref_no'=>'RM-SALE-'.$dispatch->dispatch_no,
            'total_before_tax'=>(float)$dispatch->subtotal,'discount_type'=>(string)($dispatch->discount_type?:'fixed'),
            'discount_amount'=>(float)$dispatch->discount_amount,'tax_amount'=>(float)$dispatch->tax_amount,
            'final_total'=>(float)$dispatch->net_total,'status'=>'final','payment_status'=>'due',
            'additional_notes'=>'Rice Mill Sales / Dispatch: '.$dispatch->dispatch_no,
        ]);
        if(!$transaction)return;
        $this->clearRows((int)$transaction->id,['rice_mill_dispatch_receivable','rice_mill_dispatch_income','rice_mill_dispatch_stock','rice_mill_dispatch_cogs']);

        $lines=DB::table('rcm_dispatch_lines as dl')->leftJoin('rcm_products as rp','rp.id','=','dl.product_id')
            ->where('dl.business_id',$businessId)->where('dl.dispatch_id',(int)$dispatch->id)
            ->get(['dl.id','dl.product_id','dl.quantity','dl.line_total','rp.products_new_product_id']);
        $lineBase=max(0.0,(float)$dispatch->subtotal);
        if($lineBase<=0){$lineBase=(float)$lines->sum(fn($r)=>(float)$r->line_total);}
        $net=max(0.0,(float)$dispatch->net_total);
        $income=[];$stock=[];$cogs=[];
        foreach($lines as $line){
            $sourceId=(int)($line->products_new_product_id??0);
            $revenue=$lineBase>0?round($net*((float)$line->line_total)/$lineBase,4):0.0;
            $incomeAccount=$this->productCategoryAccount($businessId,$sourceId,'sales_income_account_id',['Sales Income','Sale Income']);
            if($revenue>0 && $incomeAccount>0)$income[$incomeAccount]=($income[$incomeAccount]??0)+$revenue;
            $unitCost=$this->riceProductUnitCost($businessId,(int)$line->product_id,$sourceId);
            $cost=round(max(0.0,(float)$line->quantity)*$unitCost,4);
            if($cost>0){
                $stockAccount=$this->stockAccountForProduct($businessId,$sourceId,['Finished Goods Account','Stock Account']);
                $cogsAccount=$this->productCategoryAccount($businessId,$sourceId,'cogs_account_id',['Cost of Goods Sold','COGS']);
                if($stockAccount>0)$stock[$stockAccount]=($stock[$stockAccount]??0)+$cost;
                if($cogsAccount>0)$cogs[$cogsAccount]=($cogs[$cogsAccount]??0)+$cost;
            }
        }
        if($income)$this->fixRounding($income,$net);
        $note='Rice Mill Sales / Dispatch: '.$dispatch->dispatch_no;
        $receivable=$this->accountByNames($businessId,['Accounts Receivable','Account Receivable']);
        if($net>0 && $receivable>0)$this->writeRow($businessId,$userId,$transaction,'debit',$receivable,$net,'rice_mill_dispatch_receivable',$note);
        foreach($income as $a=>$v)$this->writeRow($businessId,$userId,$transaction,'credit',(int)$a,(float)$v,'rice_mill_dispatch_income',$note);
        foreach($stock as $a=>$v)$this->writeRow($businessId,$userId,$transaction,'credit',(int)$a,(float)$v,'rice_mill_dispatch_stock',$note);
        foreach($cogs as $a=>$v)$this->writeRow($businessId,$userId,$transaction,'debit',(int)$a,(float)$v,'rice_mill_dispatch_cogs',$note);
    }

    /** Add the selected operational payment to the same source transaction. */
    public function syncOperationalPayment(int $businessId,string $sourceType,int $sourceId,array $payment,int $userId): void
    {
        if (! $this->available()) return;
        $amount=round((float)($payment['amount']??0),4);$paymentAccount=(int)($payment['payment_account_id']??0);
        if($amount<=0||$paymentAccount<=0||!$this->validAccount($businessId,$paymentAccount))return;
        $transaction=null;$counterpart=0;$debitPayment=false;$sub='';$note='';
        if($sourceType==='paddy_receipt'){
            $src=PaddyReceipt::forBusiness($businessId)->find($sourceId); if(!$src)return;
            $this->syncPaddyReceipt($businessId,$userId,$src);
            $transaction=$this->findTransaction($businessId,'rice_mill_paddy_receipt','RM-RCV-'.$src->receipt_no);
            $counterpart=$this->paddyPayableAccount($businessId);$sub='rice_mill_receipt_payment';$note='Receive Paddy: '.$src->receipt_no;$debitPayment=false;
        }elseif($sourceType==='production_batch'){
            $src=ProductionBatch::forBusiness($businessId)->find($sourceId);if(!$src)return;
            $this->syncProduction($businessId,$userId,$src);
            $transaction=$this->findTransaction($businessId,'rice_mill_production','RM-PROD-'.$src->batch_no);
            $counterpart=$this->paddyPayableAccount($businessId) ?: $this->accountByNames($businessId,['Accounts Payable']);$sub='rice_mill_production_payment';$note='Rice Mill Production: '.$src->batch_no;$debitPayment=false;
        }elseif($sourceType==='dispatch'){
            $src=Dispatch::forBusiness($businessId)->find($sourceId);if(!$src || (string)$src->status!=='approved')return;
            $this->syncDispatch($businessId,$userId,$src);
            $transaction=$this->findTransaction($businessId,'rice_mill_dispatch','RM-SALE-'.$src->dispatch_no);
            $counterpart=$this->accountByNames($businessId,['Accounts Receivable','Account Receivable']);$sub='rice_mill_dispatch_payment';$note='Rice Mill Sales / Dispatch: '.$src->dispatch_no;$debitPayment=true;
        }
        if(!$transaction||$counterpart<=0)return;
        $this->clearRows((int)$transaction->id,[$sub.'_payment_account',$sub.'_counterpart']);
        if($debitPayment){
            $this->writeRow($businessId,$userId,$transaction,'debit',$paymentAccount,$amount,$sub.'_payment_account',$note);
            $this->writeRow($businessId,$userId,$transaction,'credit',$counterpart,$amount,$sub.'_counterpart',$note);
        }else{
            $this->writeRow($businessId,$userId,$transaction,'credit',$paymentAccount,$amount,$sub.'_payment_account',$note);
            $this->writeRow($businessId,$userId,$transaction,'debit',$counterpart,$amount,$sub.'_counterpart',$note);
        }
    }

    private function receiptValue(int $businessId,PaddyReceipt $receipt): float
    {
        $q=DB::table('rcm_paddy_purchase_lines')->where('business_id',$businessId)->where('paddy_variety_id',(int)$receipt->paddy_variety_id);
        if($receipt->purchase_id)$q->where('purchase_id',(int)$receipt->purchase_id);
        $line=$q->orderByDesc('id')->first(['net_weight','line_total','unit_rate']);
        if(!$line && $receipt->purchase_id){
            $line=DB::table('rcm_paddy_purchase_lines')->where('business_id',$businessId)->where('paddy_variety_id',(int)$receipt->paddy_variety_id)->orderByDesc('id')->first(['net_weight','line_total','unit_rate']);
        }
        if(!$line)return 0.0;
        $unit=(float)$line->net_weight>0?(float)$line->line_total/(float)$line->net_weight:(float)$line->unit_rate;
        return round(max(0.0,(float)$receipt->net_weight)*max(0.0,$unit),4);
    }

    private function paddyPayableAccount(int $businessId): int
    {
        $row=Setting::forBusiness($businessId)->select('settings')->first();$settings=(array)optional($row)->settings;
        $id=(int)($settings['paddy_payment_account_id']??0);
        return $this->validAccount($businessId,$id)?$id:$this->accountByNames($businessId,['Accounts Payable']);
    }

    private function stockAccountForProduct(int $businessId,int $productId,array $fallbackNames): int
    {
        if($productId>0 && Schema::hasTable('products') && Schema::hasColumn('products','stock_type')){
            $q=DB::table('products')->where('id',$productId);
            if(Schema::hasColumn('products','business_id'))$q->where('business_id',$businessId);
            $id=(int)$q->value('stock_type'); if($this->validAccount($businessId,$id))return $id;
        }
        return $this->accountByNames($businessId,$fallbackNames);
    }

    private function productCategoryAccount(int $businessId,int $productId,string $column,array $fallbackNames): int
    {
        if($productId>0 && Schema::hasTable('products') && Schema::hasTable('categories') && Schema::hasColumn('categories',$column)){
            $pq=DB::table('products')->where('id',$productId);
            if(Schema::hasColumn('products','business_id'))$pq->where('business_id',$businessId);
            $p=$pq->first(['category_id','sub_category_id']);
            foreach([(int)($p->sub_category_id??0),(int)($p->category_id??0)] as $cid){
                if($cid<=0)continue;$cq=DB::table('categories')->where('id',$cid);
                if(Schema::hasColumn('categories','business_id'))$cq->where('business_id',$businessId);
                $id=(int)$cq->value($column);if($this->validAccount($businessId,$id))return $id;
            }
        }
        return $this->accountByNames($businessId,$fallbackNames);
    }

    private function riceProductUnitCost(int $businessId,int $riceProductId,int $sourceProductId): float
    {
        $value=(float)DB::table('rcm_production_outputs')->where('business_id',$businessId)->where('product_id',$riceProductId)->where('unit_cost','>',0)->orderByDesc('id')->value('unit_cost');
        if($value>0)return $value;
        if($sourceProductId>0 && Schema::hasTable('variations')){
            $cols=$this->cols('variations');$select=[];foreach(['dpp_inc_tax','default_purchase_price'] as $c)if(in_array($c,$cols,true))$select[]=$c;
            if($select){$v=DB::table('variations')->where('product_id',$sourceProductId)->orderBy('id')->first($select);foreach(['dpp_inc_tax','default_purchase_price'] as $c){$x=(float)($v->{$c}??0);if($x>0)return $x;}}
        }
        return 0.0;
    }

    private function ensureTransaction(int $businessId,int $userId,string $type,string $subType,string $refNo,array $data): ?Transaction
    {
        $tx=$this->findTransaction($businessId,$subType,$refNo);
        $base=array_merge([
            'business_id'=>$businessId,'type'=>$type,'sub_type'=>$subType,'ref_no'=>$refNo,'transaction_date'=>now(),
            'status'=>'final','payment_status'=>'due','total_before_tax'=>0,'discount_type'=>'fixed','discount_amount'=>0,
            'tax_amount'=>0,'final_total'=>0,'exchange_rate'=>1,'created_by'=>$userId,
        ],$data);
        $allowed=array_flip($this->cols('transactions'));$base=array_intersect_key($base,$allowed);
        if($tx){$tx->fill($base);$tx->save();return $tx;}
        return Transaction::create($base);
    }

    private function findTransaction(int $businessId,string $subType,string $refNo): ?Transaction
    {return Transaction::where('business_id',$businessId)->where('sub_type',$subType)->where('ref_no',$refNo)->first();}

    private function updateTransactionTotal(Transaction $transaction,float $amount): void
    {if(Schema::hasColumn('transactions','final_total')){$transaction->final_total=$amount;if(Schema::hasColumn('transactions','total_before_tax'))$transaction->total_before_tax=$amount;$transaction->save();}}

    private function writeRow(int $businessId,int $userId,Transaction $transaction,string $type,int $accountId,float $amount,string $subType,string $note): void
    {
        $amount=round(abs($amount),4);if($amount<=0||!$this->validAccount($businessId,$accountId))return;
        $data=['business_id'=>$businessId,'amount'=>$amount,'account_id'=>$accountId,'type'=>$type,'sub_type'=>$subType,
            'operation_date'=>$transaction->transaction_date ?: now(),'created_by'=>$userId,'transaction_id'=>(int)$transaction->id,
            'transaction_payment_id'=>null,'note'=>$note];
        if(Schema::hasColumn('account_transactions','location_id'))$data['location_id']=$transaction->location_id ?: null;
        $allowed=array_flip($this->cols('account_transactions'));$data=array_intersect_key($data,$allowed);
        AccountTransaction::create($data);
    }

    private function clearRows(int $transactionId,array $subTypes): void
    {if(!$subTypes)return;DB::table('account_transactions')->where('transaction_id',$transactionId)->whereIn('sub_type',$subTypes)->delete();}

    private function validAccount(int $businessId,int $accountId): bool
    {
        if($accountId<=0||!Schema::hasTable('accounts'))return false;$cols=$this->cols('accounts');$q=DB::table('accounts')->where('id',$accountId);
        if(in_array('business_id',$cols,true))$q->where('business_id',$businessId);if(in_array('is_closed',$cols,true))$q->where('is_closed',0);if(in_array('deleted_at',$cols,true))$q->whereNull('deleted_at');return $q->exists();
    }

    private function accountByNames(int $businessId,array $names): int
    {
        if(!Schema::hasTable('accounts'))return 0;$cols=$this->cols('accounts');
        foreach($names as $name){$q=DB::table('accounts')->whereRaw('LOWER(name) = ?', [strtolower($name)]);if(in_array('business_id',$cols,true))$q->where('business_id',$businessId);if(in_array('is_closed',$cols,true))$q->where('is_closed',0);if(in_array('deleted_at',$cols,true))$q->whereNull('deleted_at');$id=(int)$q->value('id');if($id>0)return $id;}
        return 0;
    }

    private function fixRounding(array &$amounts,float $target): void
    {if(!$amounts)return;$sum=array_sum($amounts);$diff=round($target-$sum,4);if(abs($diff)>0.00001){$key=array_key_first($amounts);$amounts[$key]=round($amounts[$key]+$diff,4);}}

    private function cols(string $table): array
    {return $this->columns[$table] ??= (Schema::hasTable($table)?Schema::getColumnListing($table):[]);}
}
