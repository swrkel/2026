<?php
namespace Modules\AutoService\Http\Controllers;

use Illuminate\Http\Request;
use Modules\AutoService\Entities\AutoServiceCommunication;
use Modules\AutoService\Entities\AutoServiceJob;

class CommunicationController extends AutoServiceBaseController
{
    public function index()
    {
        $communications = AutoServiceCommunication::orderByDesc('id')->limit(100)->get();
        $jobs = AutoServiceJob::orderByDesc('id')->limit(100)->get();
        return view('autoservice::communications.index', compact('communications','jobs'));
    }

    public function store(Request $request)
    {
        $data = $request->validate(['job_id'=>'nullable|integer','channel'=>'required|string|max:50','direction'=>'required|string|max:50','recipient'=>'nullable|string|max:191','subject'=>'nullable|string|max:191','message'=>'required|string','status'=>'nullable|string|max:50']);
        if (!empty($data['job_id']) && ($job = AutoServiceJob::find($data['job_id']))) {
            $data['vehicle_id'] = $job->vehicle_id; $data['contact_id'] = $job->contact_id;
        }
        AutoServiceCommunication::create($data + ['business_id'=>$this->businessId(),'location_id'=>$this->locationId(),'status'=>$data['status'] ?? 'draft','created_by'=>auth()->id()]);
        return back()->with('status','Communication recorded successfully.');
    }

    public function markSent($id)
    {
        AutoServiceCommunication::where('id',$id)->update(['status'=>'sent','sent_at'=>now()]);
        return back()->with('status','Communication marked as sent.');
    }
}
