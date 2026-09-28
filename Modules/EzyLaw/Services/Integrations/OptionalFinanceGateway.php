<?php
namespace Modules\EzyLaw\Services\Integrations;

use Illuminate\Support\Facades\DB;
use Modules\EzyLaw\Contracts\FinanceGateway;
use Modules\EzyLaw\Entities\{LawInvoice,LawPayment,LawExpense,LawTrustTransaction,LawIntegrationQueue,LawInvoiceAdjustment,LawClientAdvance,LawAdvanceAllocation};
use Modules\EzyLaw\Services\SettingsService;
use Modules\EzyLaw\Utilities\EzyLawTenantGuard;

class OptionalFinanceGateway implements FinanceGateway
{
    public function available(): bool { return class_exists('Modules\\Finance\\Entities\\AccountTransaction'); }

    public function syncInvoice(int $invoiceId): array
    {
        $invoice=LawInvoice::findOrFail($invoiceId);
        return $this->postPair('invoice',$invoice->id,(float)$invoice->total,'finance_receivable_account_id','finance_fee_income_account_id',$invoice->invoice_date,'EzyLaw Invoice '.$invoice->invoice_no);
    }
    public function syncPayment(int $paymentId): array
    {
        $p=LawPayment::findOrFail($paymentId);
        return $this->postPair('payment',$p->id,(float)$p->amount,'finance_cash_account_id','finance_receivable_account_id',$p->payment_date,'EzyLaw Payment '.$p->reference);
    }
    public function syncExpense(int $expenseId): array
    {
        $e=LawExpense::findOrFail($expenseId);
        return $this->postPair('expense',$e->id,(float)$e->amount,'finance_expense_account_id','finance_cash_account_id',$e->expense_date,'EzyLaw Expense #'.$e->id);
    }
    public function syncTrustTransaction(int $transactionId): array
    {
        $t=LawTrustTransaction::findOrFail($transactionId);
        if($t->type==='deposit') return $this->postPair('trust_deposit',$t->id,(float)$t->amount,'finance_trust_cash_account_id','finance_client_trust_liability_account_id',$t->transaction_date,'EzyLaw Trust Deposit #'.$t->id);
        if($t->type==='apply_invoice') return $this->postPair('trust_apply_invoice',$t->id,(float)$t->amount,'finance_client_trust_liability_account_id','finance_receivable_account_id',$t->transaction_date,'EzyLaw Trust Applied to Invoice #'.$t->invoice_id);
        if(in_array($t->type,['refund','disbursement'],true)) return $this->postPair('trust_'.$t->type,$t->id,(float)$t->amount,'finance_client_trust_liability_account_id','finance_trust_cash_account_id',$t->transaction_date,'EzyLaw Trust '.ucfirst($t->type).' #'.$t->id);
        return ['success'=>false,'status'=>'not_supported','message'=>'This trust transaction type is not configured for Finance synchronization.'];
    }
    public function syncInvoiceAdjustment(int $adjustmentId): array
    {
        $a=LawInvoiceAdjustment::with('invoice')->findOrFail($adjustmentId);
        if($a->adjustment_type==='credit') return $this->postPair('invoice_credit_adjustment',$a->id,(float)$a->amount,'finance_fee_income_account_id','finance_receivable_account_id',$a->created_at,'EzyLaw Invoice Credit Adjustment #'.$a->id.' / '.optional($a->invoice)->invoice_no);
        return $this->postPair('invoice_debit_adjustment',$a->id,(float)$a->amount,'finance_receivable_account_id','finance_fee_income_account_id',$a->created_at,'EzyLaw Invoice Debit Adjustment #'.$a->id.' / '.optional($a->invoice)->invoice_no);
    }
    public function syncClientAdvance(int $advanceId): array
    {
        $a=LawClientAdvance::findOrFail($advanceId);
        if($a->advance_type==='expense') return $this->postPair('expense_advance',$a->id,(float)$a->amount,'finance_expense_advance_account_id','finance_cash_account_id',$a->received_on,'EzyLaw Expense Advance '.$a->advance_no);
        return $this->postPair('client_advance',$a->id,(float)$a->amount,'finance_cash_account_id','finance_client_advance_liability_account_id',$a->received_on,'EzyLaw Client Advance '.$a->advance_no);
    }
    public function syncAdvanceAllocation(int $allocationId): array
    {
        $a=LawAdvanceAllocation::with(['advance','invoice'])->findOrFail($allocationId);
        return $this->postPair('advance_allocation',$a->id,(float)$a->amount,'finance_client_advance_liability_account_id','finance_receivable_account_id',$a->allocated_on,'EzyLaw Advance Allocation #'.$a->id.' / '.optional($a->invoice)->invoice_no);
    }

    private function postPair(string $type,int $id,float $amount,string $debitKey,string $creditKey,$date,string $note): array
    {
        $businessId=EzyLawTenantGuard::businessId(); $settings=app(SettingsService::class); $eventType=$type.'.post';
        $payload=['type'=>$type,'id'=>$id,'amount'=>$amount,'debit_key'=>$debitKey,'credit_key'=>$creditKey,'note'=>$note];
        $queue=LawIntegrationQueue::firstOrCreate(
            ['business_id'=>$businessId,'target_module'=>'finance','event_type'=>$eventType,'aggregate_type'=>$type,'aggregate_id'=>$id],
            ['payload_json'=>json_encode($payload),'status'=>'pending','attempts'=>0]
        );
        if($queue->status==='processed') return ['success'=>true,'status'=>'processed','message'=>'Finance entry was already synchronized.'];
        $queue->update(['payload_json'=>json_encode($payload)]);
        if(!$this->available()){ $queue->update(['status'=>'pending']); return ['success'=>false,'status'=>'queued','message'=>'Finance module is not available; entry remains queued.']; }
        $debit=(int)$settings->get($debitKey,0); $credit=(int)$settings->get($creditKey,0);
        if($debit<=0||$credit<=0){$queue->update(['status'=>'mapping_required','last_error'=>'Finance account mapping is required.']);return ['success'=>false,'status'=>'mapping_required','message'=>'Finance account mapping is required before synchronization.'];}
        try {
            DB::transaction(function() use($type,$amount,$businessId,$date,$note,$debit,$credit,$queue){
                $class='Modules\\Finance\\Entities\\AccountTransaction';
                $common=['business_id'=>$businessId,'amount'=>$amount,'operation_date'=>$date,'created_by'=>auth()->check()?auth()->id():null,'note'=>$note,'transaction_id'=>null];
                $d=$class::createAccountTransaction($common+['account_id'=>$debit,'type'=>'debit','sub_type'=>'ezylaw_'.$type]);
                if(!$d) throw new \RuntimeException('Finance did not create the debit ledger row.');
                $c=$class::createAccountTransaction($common+['account_id'=>$credit,'type'=>'credit','sub_type'=>'ezylaw_'.$type]);
                if(!$c) throw new \RuntimeException('Finance did not create the credit ledger row.');
                // Mark the same integration request processed inside the same DB transaction as both ledger rows.
                $queue->update(['status'=>'processed','attempts'=>(int)$queue->attempts+1,'last_error'=>null,'processed_at'=>now()]);
            });
            return ['success'=>true,'status'=>'processed','message'=>'Finance synchronization completed.'];
        } catch (\Throwable $e) {
            $queue->update(['status'=>'failed','attempts'=>(int)$queue->attempts+1,'last_error'=>substr($e->getMessage(),0,1000)]);
            return ['success'=>false,'status'=>'failed','message'=>$e->getMessage()];
        }
    }
}
