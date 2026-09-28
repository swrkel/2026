<?php
namespace Modules\HRManager\Services;

use Illuminate\Support\Facades\DB;
use Modules\HRManager\Models\HrAsset;
use Modules\HRManager\Models\HrAssetAssignment;
use Modules\HRManager\Models\HrAssetAuditLog;

class HrAssetService
{
    public function assign(array $data): HrAssetAssignment
    {
        return DB::transaction(function () use ($data) {
            $assignment = HrAssetAssignment::create([
                'business_id'=>$data['business_id'],
                'assignment_no'=>$data['assignment_no'] ?? 'AST-ASG-'.now()->format('YmdHis'),
                'asset_id'=>$data['asset_id'],
                'employee_id'=>$data['employee_id'],
                'assigned_date'=>$data['assigned_date'] ?? now()->toDateString(),
                'expected_return_date'=>$data['expected_return_date'] ?? null,
                'assigned_condition'=>$data['assigned_condition'] ?? 'good',
                'assignment_status'=>'assigned',
                'assigned_by'=>$data['user_id'] ?? null,
                'remarks'=>$data['remarks'] ?? null,
            ]);

            HrAsset::where('id', $data['asset_id'])
                ->where('business_id', $data['business_id'])
                ->update(['asset_status'=>'assigned']);

            $this->audit($data['business_id'], $data['asset_id'], $data['employee_id'], 'assignment', $assignment->id, 'assigned', null, 'assigned', 'Asset assigned to employee.', $data['user_id'] ?? null);

            return $assignment;
        });
    }

    private function audit($businessId, $assetId, $employeeId, $type, $id, $action, $old, $new, $note, $userId): void
    {
        HrAssetAuditLog::create([
            'business_id'=>$businessId,
            'asset_id'=>$assetId,
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
