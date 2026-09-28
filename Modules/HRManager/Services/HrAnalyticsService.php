<?php
namespace Modules\HRManager\Services;

use Illuminate\Support\Facades\DB;
use Modules\HRManager\Models\HrAnalyticsSnapshot;
use Modules\HRManager\Models\HrAnalyticsAuditLog;

class HrAnalyticsService
{
    public function createSnapshot(int $businessId, ?int $userId = null): HrAnalyticsSnapshot
    {
        $snapshot = HrAnalyticsSnapshot::create([
            'business_id'=>$businessId,
            'snapshot_no'=>'HR-ANL-'.now()->format('YmdHis'),
            'snapshot_date'=>now()->toDateString(),
            'snapshot_type'=>'manual',
            'total_employees'=>$this->count($businessId,'hr_employees'),
            'active_employees'=>$this->count($businessId,'hr_employees',['status'=>1]),
            'new_joiners'=>$this->count($businessId,'hr_employees'),
            'leave_count'=>$this->count($businessId,'hr_leave_applications'),
            'payroll_net_total'=>$this->sum($businessId,'hr_payroll_runs','net_total'),
            'claims_total'=>$this->sum($businessId,'hr_expense_claims','total_amount'),
            'training_completed'=>$this->count($businessId,'hr_training_enrollments',['enrollment_status'=>'completed']),
            'created_by'=>$userId,
        ]);

        HrAnalyticsAuditLog::create([
            'business_id'=>$businessId,
            'reference_type'=>'snapshot',
            'reference_id'=>$snapshot->id,
            'action'=>'created',
            'new_status'=>'created',
            'note'=>'Executive analytics snapshot created.',
            'action_by'=>$userId,
            'action_at'=>now(),
        ]);

        return $snapshot;
    }

    public function dashboardMetrics(int $businessId): array
    {
        return [
            'employees'=>$this->count($businessId,'hr_employees'),
            'active'=>$this->count($businessId,'hr_employees',['status'=>1]),
            'attendance'=>$this->count($businessId,'hr_attendance_logs'),
            'leave'=>$this->count($businessId,'hr_leave_applications'),
            'payroll'=>$this->sum($businessId,'hr_payroll_runs','net_total'),
            'claims'=>$this->sum($businessId,'hr_expense_claims','total_amount'),
            'training'=>$this->count($businessId,'hr_training_enrollments'),
            'performance'=>$this->count($businessId,'hr_performance_appraisals'),
        ];
    }

    private function hasTable(string $table): bool
    {
        try { return DB::getSchemaBuilder()->hasTable($table); } catch (\Throwable $e) { return false; }
    }

    private function count(int $businessId, string $table, array $where = []): int
    {
        if (!$this->hasTable($table)) return 0;
        $q = DB::table($table)->where('business_id',$businessId);
        foreach ($where as $k=>$v) { $q->where($k,$v); }
        return (int)$q->count();
    }

    private function sum(int $businessId, string $table, string $column): float
    {
        if (!$this->hasTable($table)) return 0;
        return (float)DB::table($table)->where('business_id',$businessId)->sum($column);
    }
}
