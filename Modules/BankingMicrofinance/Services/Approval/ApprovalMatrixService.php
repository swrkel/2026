<?php
namespace Modules\BankingMicrofinance\Services\Approval;
use Illuminate\Support\Facades\DB;
class ApprovalMatrixService {
    public function requiredLevels(int $businessId,string $workflow,float $amount,?string $riskGrade=null){ return DB::table('bkg_mfi_approval_matrices')->where('business_id',$businessId)->where('workflow_key',$workflow)->where('is_active',1)->where('min_amount','<=',$amount)->where(function($q)use($amount){$q->where('max_amount','>=',$amount)->orWhere('max_amount',0);})->when($riskGrade,fn($q)=>$q->where(function($x)use($riskGrade){$x->whereNull('risk_grade')->orWhere('risk_grade',$riskGrade);}))->orderBy('level_no')->get(); }
}
