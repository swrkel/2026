<?php
namespace Modules\AutoService\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServiceAdvisorWorkspaceController extends AutoServiceBaseController
{
    public function index()
    {
        $b = $this->businessId();
        $jobs = DB::table('auto_service_jobs')->when($b, fn($q)=>$q->where('business_id',$b));
        $appointments = DB::table('auto_service_appointments')->when($b, fn($q)=>$q->where('business_id',$b));
        $estimates = DB::table('auto_service_estimates')->when($b, fn($q)=>$q->where('business_id',$b));
        $data = [
            'appointments_today' => (clone $appointments)->whereDate('appointment_date', date('Y-m-d'))->orderBy('appointment_date')->limit(10)->get(),
            'vehicles_received' => (clone $jobs)->whereIn('workflow_stage', ['received','inspection'])->orderByDesc('id')->limit(10)->get(),
            'awaiting_estimate' => (clone $jobs)->where('workflow_stage', 'estimate')->orderByDesc('id')->limit(10)->get(),
            'awaiting_approval' => (clone $estimates)->whereIn('status', ['draft','sent','pending'])->orderByDesc('id')->limit(10)->get(),
            'in_progress' => (clone $jobs)->whereIn('workflow_stage', ['work_order','in_progress','qc'])->orderByDesc('id')->limit(10)->get(),
            'ready_delivery' => (clone $jobs)->whereIn('workflow_stage', ['ready','delivery'])->orderByDesc('id')->limit(10)->get(),
            'due_reminders' => DB::table('auto_service_reminders')->when($b, fn($q)=>$q->where('business_id',$b))->where('status','pending')->whereDate('send_on','<=',date('Y-m-d'))->orderBy('send_on')->limit(10)->get(),
            'counts' => [
                'appointments_today' => (clone $appointments)->whereDate('appointment_date', date('Y-m-d'))->count(),
                'vehicles_received' => (clone $jobs)->whereIn('workflow_stage', ['received','inspection'])->count(),
                'awaiting_estimate' => (clone $jobs)->where('workflow_stage', 'estimate')->count(),
                'awaiting_approval' => (clone $estimates)->whereIn('status', ['draft','sent','pending'])->count(),
                'in_progress' => (clone $jobs)->whereIn('workflow_stage', ['work_order','in_progress','qc'])->count(),
                'ready_delivery' => (clone $jobs)->whereIn('workflow_stage', ['ready','delivery'])->count(),
            ],
        ];
        return view('autoservice::workspace.index', $data);
    }
}
