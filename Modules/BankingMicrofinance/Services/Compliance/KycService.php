<?php
namespace Modules\BankingMicrofinance\Services\Compliance;
use Illuminate\Support\Facades\DB;
class KycService {
    public function summary(int $businessId): array { return [
        'draft'=>DB::table('bkg_mfi_kyc_profiles')->where('business_id',$businessId)->where('status','draft')->count(),
        'pending'=>DB::table('bkg_mfi_kyc_profiles')->where('business_id',$businessId)->where('status','pending')->count(),
        'approved'=>DB::table('bkg_mfi_kyc_profiles')->where('business_id',$businessId)->where('status','approved')->count(),
        'high_risk'=>DB::table('bkg_mfi_kyc_profiles')->where('business_id',$businessId)->where('risk_category','high')->count(),
    ]; }
    public function approve(int $businessId, int $profileId, ?int $userId): void { DB::table('bkg_mfi_kyc_profiles')->where('business_id',$businessId)->where('id',$profileId)->update(['status'=>'approved','approved_by'=>$userId,'approved_at'=>now(),'updated_at'=>now()]); }
}
