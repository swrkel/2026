<?php
namespace Modules\EzyLaw\Services;
use Illuminate\Support\Facades\DB;
use Modules\EzyLaw\Entities\{LawTrustAccount,LawTrustTransaction,LawInvoice};
use Modules\EzyLaw\Utilities\EzyLawTenantGuard;
class TrustService
{
    public function createAccount(array $data): LawTrustAccount
    {
        if(!empty($data['location_id'])){
            $allowed=EzyLawTenantGuard::permittedLocationIds();
            if($allowed!==['all'] && !in_array((string)$data['location_id'],array_map('strval',$allowed),true)) throw new \RuntimeException('The selected location is not available to this user.');
        }
        return LawTrustAccount::create($data+['business_id'=>EzyLawTenantGuard::businessId(),'created_by'=>auth()->id(),'current_balance'=>0]);
    }

    public function clientBalance(int $accountId,int $clientId): float
    {
        $credit=(float)LawTrustTransaction::where('trust_account_id',$accountId)->where('client_id',$clientId)->where('direction','credit')->sum('amount');
        $debit=(float)LawTrustTransaction::where('trust_account_id',$accountId)->where('client_id',$clientId)->where('direction','debit')->sum('amount');
        return round($credit-$debit,4);
    }

    public function post(LawTrustAccount $account,array $data): LawTrustTransaction
    {
        return DB::transaction(function() use($account,$data){
            $locked=LawTrustAccount::whereKey($account->id)->lockForUpdate()->firstOrFail();
            $amount=round((float)$data['amount'],4);
            if(!empty($data['matter_id'])){
                $matter=\Modules\EzyLaw\Entities\LawMatter::findOrFail((int)$data['matter_id']);
                if((int)$matter->client_id!==(int)$data['client_id']) throw new \RuntimeException('The selected matter does not belong to the selected client.');
            }
            if($amount<=0) throw new \InvalidArgumentException('Trust transaction amount must be greater than zero.');
            $direction=$data['direction'];
            if($direction==='debit'){
                if((float)$locked->current_balance+0.0001<$amount) throw new \RuntimeException('Insufficient trust account balance.');
                $clientAvailable=$this->clientBalance($locked->id,(int)$data['client_id']);
                if($clientAvailable+0.0001<$amount) throw new \RuntimeException('Insufficient client trust balance.');
            }
            $newBalance=round((float)$locked->current_balance+($direction==='credit'?$amount:-$amount),4);
            $tx=LawTrustTransaction::create($data+[
                'business_id'=>EzyLawTenantGuard::businessId(),'trust_account_id'=>$locked->id,
                'amount'=>$amount,'running_balance'=>$newBalance,'created_by'=>auth()->id(),
            ]);
            $locked->update(['current_balance'=>$newBalance]);
            app(ActivityService::class)->log('post','trust_transaction',$tx->id,'Trust transaction posted',['type'=>$tx->type,'amount'=>$amount]);
            return $tx;
        });
    }

    public function applyToInvoice(LawTrustAccount $account,LawInvoice $invoice,array $data): LawTrustTransaction
    {
        return DB::transaction(function() use($account,$invoice,$data){
            $amount=round((float)$data['amount'],4);
            if($amount>(float)$invoice->balance+0.0001) throw new \RuntimeException('Amount cannot exceed the invoice balance.');
            $tx=$this->post($account,[
                'client_id'=>$invoice->client_id,'matter_id'=>$invoice->matter_id,'invoice_id'=>$invoice->id,
                'transaction_date'=>$data['transaction_date'],'type'=>'apply_invoice','direction'=>'debit','amount'=>$amount,
                'reference'=>$data['reference']??null,'payee'=>null,'description'=>$data['description']??('Applied to invoice '.$invoice->invoice_no),
            ]);
            app(BillingService::class)->recordPayment($invoice,[
                'payment_date'=>$data['transaction_date'],'amount'=>$amount,'method'=>'trust','reference'=>$data['reference']??('TRUST-'.$tx->id),
                'finance_sync_status'=>'handled_by_trust','notes'=>'Paid from EzyLaw client trust funds.',
            ]);
            return $tx;
        });
    }
}
