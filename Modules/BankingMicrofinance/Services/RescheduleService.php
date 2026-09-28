<?php
namespace Modules\BankingMicrofinance\Services;
use Illuminate\Support\Facades\DB;use Modules\BankingMicrofinance\Entities\LoanReschedule;
class RescheduleService{public function approve(LoanReschedule $reschedule,?int $userId=null): LoanReschedule{return DB::transaction(function()use($reschedule,$userId){$reschedule->update(['status'=>'approved','approved_by'=>$userId,'approved_at'=>now()]);DB::table('bkg_mfi_loans')->where('id',$reschedule->loan_id)->update(['term'=>$reschedule->new_term,'installment_amount'=>$reschedule->new_installment_amount,'updated_at'=>now()]);return $reschedule->fresh();});}}
