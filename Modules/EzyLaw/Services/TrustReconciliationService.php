<?php
namespace Modules\EzyLaw\Services;
use Illuminate\Support\Facades\DB;
use Modules\EzyLaw\Entities\{LawTrustAccount,LawTrustTransaction,LawTrustReconciliation,LawTrustReconciliationLine};
use Modules\EzyLaw\Utilities\EzyLawTenantGuard;
class TrustReconciliationService {
    public function start(LawTrustAccount $account,array $data): LawTrustReconciliation {
        return DB::transaction(function()use($account,$data){
            $txs=LawTrustTransaction::where('trust_account_id',$account->id)->where('transaction_date','<=',$data['statement_date'])->orderBy('transaction_date')->get();
            $book=0.0; foreach($txs as $tx){$book += $tx->direction==='credit' ? (float)$tx->amount : -(float)$tx->amount;}
            $statement=(float)$data['statement_balance'];
            $rec=LawTrustReconciliation::create(['business_id'=>EzyLawTenantGuard::businessId(),'trust_account_id'=>$account->id,'statement_date'=>$data['statement_date'],'statement_balance'=>$statement,'book_balance'=>$book,'difference'=>$statement-$book,'status'=>'open','notes'=>$data['notes']??null]);
            foreach($txs as $t){LawTrustReconciliationLine::create(['business_id'=>EzyLawTenantGuard::businessId(),'reconciliation_id'=>$rec->id,'trust_transaction_id'=>$t->id,'line_type'=>$t->direction,'amount'=>$t->amount,'matched'=>0]);}
            return $rec;
        });
    }
    public function complete(LawTrustReconciliation $rec,array $matchedIds=[]): void {
        DB::transaction(function()use($rec,$matchedIds){
            LawTrustReconciliationLine::where('reconciliation_id',$rec->id)->update(['matched'=>0]);
            if($matchedIds) LawTrustReconciliationLine::where('reconciliation_id',$rec->id)->whereIn('id',$matchedIds)->update(['matched'=>1]);
            $rec->update(['status'=>'completed','reconciled_by'=>auth()->id(),'reconciled_at'=>now()]);
        });
    }
}
