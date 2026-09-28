<?php
namespace Modules\HRManager\Services;

use Illuminate\Support\Facades\DB;
use Modules\HRManager\Models\HrPerformanceGoal;
use Modules\HRManager\Models\HrPerformanceReview;
use Modules\HRManager\Models\HrPerformanceAuditLog;

class HrPerformanceService
{
    public function createGoal(array $data): HrPerformanceGoal
    {
        return DB::transaction(function () use ($data) {
            $goal = HrPerformanceGoal::create([
                'business_id'=>$data['business_id'],
                'goal_no'=>$data['goal_no'] ?? 'GOAL-'.now()->format('YmdHis'),
                'cycle_id'=>$data['cycle_id'] ?? null,
                'employee_id'=>$data['employee_id'] ?? null,
                'department_id'=>$data['department_id'] ?? null,
                'goal_scope'=>$data['goal_scope'] ?? 'employee',
                'goal_title'=>$data['goal_title'],
                'goal_description'=>$data['goal_description'] ?? null,
                'start_date'=>$data['start_date'] ?? null,
                'due_date'=>$data['due_date'] ?? null,
                'target_value'=>$data['target_value'] ?? 0,
                'weightage'=>$data['weightage'] ?? 0,
                'goal_status'=>'open',
                'created_by'=>$data['user_id'] ?? null,
            ]);
            $this->audit($data['business_id'], $data['employee_id'] ?? null, 'goal', $goal->id, 'created', null, 'open', 'Performance goal created.', $data['user_id'] ?? null);
            return $goal;
        });
    }

    public function createReview(array $data): HrPerformanceReview
    {
        return DB::transaction(function () use ($data) {
            $review = HrPerformanceReview::create([
                'business_id'=>$data['business_id'],
                'review_no'=>$data['review_no'] ?? 'REV-'.now()->format('YmdHis'),
                'cycle_id'=>$data['cycle_id'],
                'employee_id'=>$data['employee_id'],
                'reviewer_employee_id'=>$data['reviewer_employee_id'] ?? null,
                'review_type'=>$data['review_type'] ?? 'manager',
                'review_date'=>$data['review_date'] ?? now()->toDateString(),
                'review_status'=>'draft',
                'created_by'=>$data['user_id'] ?? null,
            ]);
            $this->audit($data['business_id'], $data['employee_id'], 'review', $review->id, 'created', null, 'draft', 'Performance review created.', $data['user_id'] ?? null);
            return $review;
        });
    }

    private function audit($businessId, $employeeId, $type, $id, $action, $old, $new, $note, $userId): void
    {
        HrPerformanceAuditLog::create([
            'business_id'=>$businessId,
            'employee_id'=>$employeeId,
            'reference_type'=>$type,
            'reference_id'=>$id,
            'action'=>$action,
            'old_status'=>$old,
            'new_status'=>$new,
            'note'=>$note,
            'action_by'=>$userId,
            'action_at'=>now(),
        ]);
    }
}
