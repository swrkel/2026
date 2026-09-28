<?php
namespace Modules\HRManager\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\HRManager\Models\HrLeaveApplication;
use Modules\HRManager\Models\HrLeaveApproval;
use Modules\HRManager\Models\HrLeaveCalendar;
use Modules\HRManager\Models\HrLeaveAuditLog;

class HrEnterpriseLeaveService
{
    public function apply(array $data): HrLeaveApplication
    {
        return DB::transaction(function () use ($data) {
            $from = Carbon::parse($data['from_date']);
            $to = Carbon::parse($data['to_date']);
            $days = $from->diffInDays($to) + 1;
            $application = HrLeaveApplication::create([
                'business_id'=>$data['business_id'],
                'application_no'=>$data['application_no'] ?? 'LVAPP-'.now()->format('YmdHis'),
                'employee_id'=>$data['employee_id'],
                'leave_type_id'=>$data['leave_type_id'],
                'from_date'=>$from->toDateString(),
                'to_date'=>$to->toDateString(),
                'total_days'=>$data['total_days'] ?? $days,
                'paid_days'=>$data['paid_days'] ?? $days,
                'unpaid_days'=>$data['unpaid_days'] ?? 0,
                'reason'=>$data['reason'] ?? null,
                'application_status'=>'pending',
                'approval_status'=>'pending',
                'applied_by'=>$data['user_id'] ?? null,
                'applied_at'=>now(),
            ]);
            HrLeaveApproval::create(['business_id'=>$data['business_id'],'leave_application_id'=>$application->id,'employee_id'=>$data['employee_id'],'approval_level'=>1,'approver_type'=>'manager','approval_status'=>'pending']);
            $this->audit($application,'applied',null,'pending','Leave application submitted.',$data['user_id'] ?? null);
            return $application;
        });
    }

    public function approve(HrLeaveApplication $application, ?int $userId): HrLeaveApplication
    {
        return DB::transaction(function () use ($application, $userId) {
            $application->application_status='approved';
            $application->approval_status='approved';
            $application->save();
            HrLeaveApproval::where('leave_application_id',$application->id)->where('approval_status','pending')->update(['approval_status'=>'approved','approver_user_id'=>$userId,'approved_at'=>now()]);
            $from=Carbon::parse($application->from_date); $to=Carbon::parse($application->to_date);
            for($date=$from->copy(); $date->lte($to); $date->addDay()){
                HrLeaveCalendar::updateOrCreate(
                    ['business_id'=>$application->business_id,'employee_id'=>$application->employee_id,'leave_application_id'=>$application->id,'calendar_date'=>$date->toDateString()],
                    ['calendar_type'=>'leave','leave_type_id'=>$application->leave_type_id,'day_value'=>1.00,'display_title'=>'Approved Leave #'.$application->application_no,'calendar_status'=>'active']
                );
            }
            $this->audit($application,'approved','pending','approved','Leave application approved.',$userId);
            return $application;
        });
    }

    public function reject(HrLeaveApplication $application, string $reason, ?int $userId): HrLeaveApplication
    {
        $application->application_status='rejected';
        $application->approval_status='rejected';
        $application->save();
        HrLeaveApproval::where('leave_application_id',$application->id)->where('approval_status','pending')->update(['approval_status'=>'rejected','approval_note'=>$reason,'approver_user_id'=>$userId,'rejected_at'=>now()]);
        $this->audit($application,'rejected','pending','rejected',$reason,$userId);
        return $application;
    }

    private function audit(HrLeaveApplication $application, string $action, ?string $old, ?string $new, ?string $note, ?int $userId): void
    {
        HrLeaveAuditLog::create(['business_id'=>$application->business_id,'employee_id'=>$application->employee_id,'leave_application_id'=>$application->id,'action'=>$action,'old_status'=>$old,'new_status'=>$new,'note'=>$note,'action_by'=>$userId,'action_at'=>now()]);
    }
}
