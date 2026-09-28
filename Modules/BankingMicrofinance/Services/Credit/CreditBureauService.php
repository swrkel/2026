<?php
namespace Modules\BankingMicrofinance\Services\Credit;
use Illuminate\Support\Facades\DB;
class CreditBureauService {
    public function createManualEnquiry(int $businessId, array $data, ?int $userId=null): int {
        return DB::table('bkg_mfi_credit_bureau_enquiries')->insertGetId([
            'business_id'=>$businessId,'provider_id'=>$data['provider_id']??null,'loan_application_id'=>$data['loan_application_id']??null,'member_id'=>$data['member_id']??null,
            'subject_type'=>$data['subject_type']??'borrower','nic_no'=>$data['nic_no']??null,'mobile_no'=>$data['mobile_no']??null,'status'=>'manual_review','score'=>$data['score']??null,
            'risk_band'=>$data['risk_band']??null,'summary_json'=>isset($data['summary_json'])?json_encode($data['summary_json']):null,'requested_by'=>$userId,'requested_at'=>now(),'created_at'=>now(),'updated_at'=>now()
        ]);
    }
    public function latestScore(int $businessId, int $memberId): ?object { return DB::table('bkg_mfi_credit_bureau_enquiries')->where('business_id',$businessId)->where('member_id',$memberId)->latest('id')->first(); }
}
