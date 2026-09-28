<?php
namespace Modules\AutoService\Http\Controllers;

use Illuminate\Http\Request;
use Modules\AutoService\Entities\AutoServiceBay;
use Modules\AutoService\Entities\AutoServiceBayAllocation;
use Modules\AutoService\Entities\AutoServiceJob;
use Modules\AutoService\Entities\AutoServiceMechanic;

class BayController extends AutoServiceBaseController
{
    public function index()
    {
        $bays = AutoServiceBay::orderBy('name')->get();
        $active = AutoServiceBayAllocation::whereNull('released_at')->orderByDesc('allocated_at')->get();
        $jobs = AutoServiceJob::whereNotIn('status', ['delivered','cancelled'])->orderByDesc('id')->limit(100)->get();
        $mechanics = AutoServiceMechanic::orderBy('name')->get();
        return view('autoservice::bays.index', compact('bays','active','jobs','mechanics'));
    }

    public function store(Request $request)
    {
        $data = $request->validate(['bay_id'=>'required|integer','job_id'=>'required|integer','mechanic_id'=>'nullable|integer','notes'=>'nullable|string|max:2000']);
        $job = AutoServiceJob::findOrFail($data['job_id']);
        AutoServiceBayAllocation::create($data + ['business_id'=>$this->businessId(),'location_id'=>$this->locationId(),'vehicle_id'=>$job->vehicle_id,'allocated_at'=>now(),'status'=>'allocated','created_by'=>auth()->id()]);
        return back()->with('status','Bay allocated successfully.');
    }

    public function release($id)
    {
        AutoServiceBayAllocation::where('id',$id)->update(['released_at'=>now(),'status'=>'released']);
        return back()->with('status','Bay released successfully.');
    }
}
