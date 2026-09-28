<?php
namespace Modules\AutoService\Http\Controllers;

use Illuminate\Http\Request;
use Modules\AutoService\Entities\AutoServiceApprovalRequest;
use Modules\AutoService\Entities\AutoServiceJob;
use Modules\AutoService\Entities\AutoServiceTimeline;
use Modules\AutoService\Services\AutoServiceNotificationService;

class ApprovalController extends AutoServiceBaseController
{
    public function index()
    {
        $approvals = AutoServiceApprovalRequest::orderByDesc('id')->paginate(25);
        return view('autoservice::approvals.index', compact('approvals'));
    }

    public function create(Request $request)
    {
        $jobs = AutoServiceJob::orderByDesc('id')->limit(100)->pluck('job_no','id');
        return view('autoservice::approvals.form', compact('jobs'));
    }

    public function store(Request $request, AutoServiceNotificationService $notifier)
    {
        $data = $request->validate([
            'job_id'=>'required|integer', 'request_type'=>'nullable|string|max:100', 'title'=>'required|string|max:191',
            'description'=>'nullable|string', 'amount'=>'nullable|numeric'
        ]);
        $data['business_id'] = $request->session()->get('user.business_id');
        $data['approval_no'] = 'ASAP-' . date('ymd') . '-' . str_pad((string)(AutoServiceApprovalRequest::max('id') + 1), 5, '0', STR_PAD_LEFT);
        $data['status'] = 'pending';
        $data['requested_at'] = now();
        $data['requested_by'] = auth()->id();
        $approval = AutoServiceApprovalRequest::create($data);
        $job = AutoServiceJob::find($data['job_id']);
        if ($job) {
            AutoServiceTimeline::create(['vehicle_id'=>$job->vehicle_id,'job_id'=>$job->id,'event_type'=>'approval_requested','title'=>'Customer approval requested','description'=>$approval->title,'event_at'=>now(),'created_by'=>auth()->id()]);
            $notifier->queueJobNotification($job, 'additional_work_approval', 'Approval required for '.$approval->title.' Amount: '.$approval->amount);
        }
        return redirect()->route('autoservice.approvals.index')->with('status', __('Approval request created'));
    }

    public function respond(Request $request, $id)
    {
        $approval = AutoServiceApprovalRequest::findOrFail($id);
        $status = $request->get('status') === 'approved' ? 'approved' : 'rejected';
        $approval->update(['status'=>$status, 'responded_at'=>now(), 'customer_note'=>$request->get('customer_note')]);
        $job = AutoServiceJob::find($approval->job_id);
        if ($job) {
            AutoServiceTimeline::create(['vehicle_id'=>$job->vehicle_id,'job_id'=>$job->id,'event_type'=>'approval_'.$status,'title'=>'Approval '.ucfirst($status),'description'=>$approval->title,'event_at'=>now(),'created_by'=>auth()->id()]);
        }
        return back()->with('status', __('Updated successfully'));
    }
}
