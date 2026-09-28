<?php
namespace Modules\LeadsNew\Http\Controllers;
use Illuminate\Http\Request; use Illuminate\Routing\Controller; use Modules\LeadsNew\Models\LeadsNewCampaign;
class LeadsNewCampaignController extends Controller
{
    public function index(){ $campaigns=LeadsNewCampaign::latest('id')->paginate(25); return view('leadsnew::campaigns.index',compact('campaigns')); }
    public function store(Request $request){ $data=$request->validate(['name'=>'required|max:191','code'=>'nullable|max:50','start_date'=>'nullable|date','end_date'=>'nullable|date','budget'=>'nullable|numeric','cost'=>'nullable|numeric','revenue'=>'nullable|numeric','is_active'=>'nullable|boolean']); $data['created_by']=auth()->id(); LeadsNewCampaign::create($data); return back()->with('status','Campaign saved successfully'); }
    public function update(Request $request, LeadsNewCampaign $campaign){ $data=$request->validate(['name'=>'required|max:191','code'=>'nullable|max:50','start_date'=>'nullable|date','end_date'=>'nullable|date','budget'=>'nullable|numeric','cost'=>'nullable|numeric','revenue'=>'nullable|numeric','is_active'=>'nullable|boolean']); $data['updated_by']=auth()->id(); $campaign->update($data); return back()->with('status','Campaign updated successfully'); }
}
