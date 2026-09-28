<?php
namespace Modules\HRManager\Services;

use Illuminate\Support\Facades\DB;
use Modules\HRManager\Models\HrWorkforcePlan;
use Modules\HRManager\Models\HrWorkforceAuditLog;

class HrWorkforcePlanningService
{
    public function createPlan(array $data): HrWorkforcePlan
    {
        return DB::transaction(function () use ($data) {
            $plan = HrWorkforcePlan::create([
                'business_id'=>$data['business_id'],
                'plan_no'=>$data['plan_no'] ?? 'WFP-'.now()->format('YmdHis'),
                'plan_name'=>$data['plan_name'],
                'plan_year'=>$data['plan_year'],
                'department_id'=>$data['department_id'] ?? null,
                'location_id'=>$data['location_id'] ?? null,
                'plan_status'=>'draft',
                'approval_status'=>'pending',
                'created_by'=>$data['user_id'] ?? null,
            ]);
            HrWorkforceAuditLog::create([
                'business_id'=>$data['business_id'],
                'reference_type'=>'workforce_plan',
                'reference_id'=>$plan->id,
                'action'=>'created',
                'new_status'=>'draft',
                'note'=>'Workforce plan created.',
                'action_by'=>$data['user_id'] ?? null,
                'action_at'=>now(),
            ]);
            return $plan;
        });
    }
}
