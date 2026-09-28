<?php
namespace Modules\HRManager\Services;
use Illuminate\Support\Facades\DB;
use Modules\HRManager\Models\HrExpenseClaim;
use Modules\HRManager\Models\HrClaimApproval;
use Modules\HRManager\Models\HrClaimAuditLog;

class HrClaimService
{
    public function createClaim(array $data): HrExpenseClaim
    {
        return DB::transaction(function () use ($data) {
            $claim = HrExpenseClaim::create([
                'business_id'=>$data['business_id'],
                'claim_no'=>$data['claim_no'] ?? 'CLM-'.now()->format('YmdHis'),
                'employee_id'=>$data['employee_id'],
                'travel_request_id'=>$data['travel_request_id'] ?? null,
                'claim_date'=>$data['claim_date'] ?? now()->toDateString(),
                'claim_title'=>$data['claim_title'],
                'total_amount'=>$data['total_amount'] ?? 0,
                'claim_status'=>'submitted',
                'approval_status'=>'pending',
                'payment_status'=>'unpaid',
                'submitted_by'=>$data['user_id'] ?? null,
                'submitted_at'=>now(),
                'remarks'=>$data['remarks'] ?? null,
            ]);
            HrClaimApproval::create(['business_id'=>$data['business_id'],'reference_type'=>'expense_claim','reference_id'=>$claim->id,'employee_id'=>$data['employee_id'],'approval_level'=>1,'approval_status'=>'pending']);
            HrClaimAuditLog::create(['business_id'=>$data['business_id'],'employee_id'=>$data['employee_id'],'reference_type'=>'expense_claim','reference_id'=>$claim->id,'action'=>'submitted','new_status'=>'submitted','note'=>'Expense claim submitted.','action_by'=>$data['user_id'] ?? null,'action_at'=>now()]);
            return $claim;
        });
    }
}
