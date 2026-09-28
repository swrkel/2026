<?php
namespace Modules\BankingMicrofinance\Services\Committee;
use Illuminate\Support\Facades\DB;
class CreditCommitteeService {
    public function decisions(int $businessId, ?string $decision=null){ return DB::table('bkg_mfi_credit_committee_decisions')->where('business_id',$businessId)->when($decision,fn($q)=>$q->where('decision',$decision))->latest('decision_date')->get(); }
}
