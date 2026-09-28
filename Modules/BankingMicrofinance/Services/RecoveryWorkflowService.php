<?php
namespace Modules\BankingMicrofinance\Services;
use Modules\BankingMicrofinance\Entities\RecoveryCase;
class RecoveryWorkflowService {
 public function openRecoveryCase(int $collectionCaseId, float $amount): RecoveryCase { return RecoveryCase::create(['collection_case_id'=>$collectionCaseId,'recovery_no'=>'RCV-'.date('Ymd').'-'.str_pad((string)(RecoveryCase::count()+1),5,'0',STR_PAD_LEFT),'stage'=>'pre_legal','status'=>'active','recoverable_amount'=>$amount,'opened_on'=>date('Y-m-d')]); }
 public function moveStage(RecoveryCase $case, string $stage, ?string $remarks=null): RecoveryCase { $case->stage=$stage; if($remarks){$case->remarks=trim(($case->remarks? $case->remarks."
":'').date('Y-m-d H:i').' '.$remarks);} $case->save(); return $case; }
}
