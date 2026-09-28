<?php
namespace Modules\LeadsNew\Http\Controllers;
use App\Http\Controllers\Controller; use Illuminate\Http\Request; use Modules\LeadsNew\Models\LeadsNewWorkflow;
class LeadsNewWorkflowController extends Controller { public function index(){ $workflows=LeadsNewWorkflow::latest()->paginate(25); return view('leadsnew::workflows.index',compact('workflows')); } public function store(Request $r){ LeadsNewWorkflow::create($r->all()+['business_id'=>session('business.id'),'conditions'=>$r->input('conditions',[]),'actions'=>$r->input('actions',[])]); return back()->with('status',['success'=>1,'msg'=>'Workflow saved']); } public function update(Request $r,$id){ LeadsNewWorkflow::findOrFail($id)->update($r->all()); return back()->with('status',['success'=>1,'msg'=>'Workflow updated']); } }
