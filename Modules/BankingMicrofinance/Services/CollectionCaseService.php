<?php
namespace Modules\BankingMicrofinance\Services;
use Modules\BankingMicrofinance\Entities\CollectionCase;
class CollectionCaseService {
 public function createOrUpdateFromLoan(array $loan): CollectionCase { $case = CollectionCase::firstOrNew(['loan_id'=>$loan['loan_id']]); $case->fill($loan); if(!$case->case_no){$case->case_no='COL-'.date('Ymd').'-'.str_pad((string)(CollectionCase::count()+1),5,'0',STR_PAD_LEFT);} $case->bucket=$this->bucket((int)($case->days_past_due ?? 0)); $case->priority=$this->priority((int)($case->days_past_due ?? 0),(float)($case->arrears_amount ?? 0)); $case->save(); return $case; }
 public function bucket(int $dpd): string { return $dpd>=180?'loss':($dpd>=90?'npl_90':($dpd>=60?'bucket_60':($dpd>=30?'bucket_30':($dpd>0?'bucket_1_29':'current')))); }
 public function priority(int $dpd, float $arrears): string { if($dpd>=90 || $arrears>=100000) return 'critical'; if($dpd>=30) return 'high'; return 'normal'; }
}
