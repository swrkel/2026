<?php
namespace Modules\BankingMicrofinance\Services\Origination;
use Illuminate\Support\Facades\DB;
class LoanOriginationService {
    public function moveStage(int $businessId, int $applicationId, ?string $from, string $to, ?string $remarks, ?int $userId): void {
        DB::table('bkg_mfi_application_timelines')->insert(['business_id'=>$businessId,'loan_application_id'=>$applicationId,'from_stage'=>$from,'to_stage'=>$to,'event_type'=>'stage_change','remarks'=>$remarks,'created_by'=>$userId,'created_at'=>now(),'updated_at'=>now()]);
    }
    public function pipeline(int $businessId): array { return DB::table('bkg_mfi_application_timelines')->select('to_stage',DB::raw('count(distinct loan_application_id) as applications'))->where('business_id',$businessId)->groupBy('to_stage')->get()->toArray(); }
}
