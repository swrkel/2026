<?php
namespace Modules\LeadsNew\Http\Controllers;
use Illuminate\Http\Request; use Illuminate\Routing\Controller; use Modules\LeadsNew\Models\LeadsNewActivity;
class LeadsNewActivityController extends Controller
{
    public function index(Request $request){ $activities=LeadsNewActivity::with('lead')->latest('activity_date')->paginate(50); return view('leadsnew::activities.index',compact('activities')); }
    public function store(Request $request){ $data=$request->validate(['lead_id'=>'required|integer','type'=>'required|max:50','title'=>'required|max:191','description'=>'nullable|string','activity_date'=>'nullable|date']); $data['created_by']=auth()->id(); LeadsNewActivity::create($data); return back()->with('status','Activity saved successfully'); }
}
