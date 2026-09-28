<?php
namespace Modules\EzyLaw\Http\Controllers;
use Illuminate\Http\Request; use Illuminate\Routing\Controller;
use Modules\EzyLaw\Entities\{LawWorkflowTemplate,LawWorkflowStage,LawMatter,LawMatterStageHistory,LawPracticeArea};
use Modules\EzyLaw\Services\WorkflowService; use Modules\EzyLaw\Utilities\{EzyLawTenantGuard,EzyLawReferenceGuard};
class WorkflowController extends Controller {
 public function index(Request $r){$matterId=$r->input('matter_id');return view('ezylaw::workflow.index',['templates'=>LawWorkflowTemplate::with('stages')->orderBy('name')->get(),'areas'=>LawPracticeArea::where('active',1)->orderBy('name')->get(),'matters'=>LawMatter::whereIn('status',['open','pending'])->orderBy('matter_no')->get(),'selectedMatter'=>$matterId?LawMatter::find($matterId):null,'history'=>$matterId?LawMatterStageHistory::with('stage')->where('matter_id',$matterId)->orderByDesc('started_at')->get():collect()]);}
 public function template(Request $r){$d=$r->validate(['name'=>'required|string|max:191','practice_area_id'=>'nullable|integer','description'=>'nullable|string']);EzyLawReferenceGuard::practiceArea($d['practice_area_id']??null);LawWorkflowTemplate::create($d+['business_id'=>EzyLawTenantGuard::businessId(),'active'=>1]);return back()->with('success','Workflow template created.');}
 public function stage(Request $r){$d=$r->validate(['workflow_template_id'=>'required|integer','name'=>'required|string|max:191','sequence_no'=>'required|integer|min:1','default_days'=>'nullable|integer|min:0','required'=>'nullable|boolean']);LawWorkflowStage::create($d+['business_id'=>EzyLawTenantGuard::businessId(),'required'=>$r->boolean('required'),'active'=>1]);return back()->with('success','Workflow stage added.');}
 public function start(Request $r,LawMatter $matter,WorkflowService $s){$d=$r->validate(['workflow_stage_id'=>'required|integer','due_at'=>'nullable|date','notes'=>'nullable|string']);$stage=LawWorkflowStage::findOrFail($d['workflow_stage_id']);$s->start($matter,$stage,$d);return back()->with('success','Matter workflow stage started.');}
 public function complete(Request $r,LawMatterStageHistory $history,WorkflowService $s){$s->complete($history,$r->input('notes'));return back()->with('success','Workflow stage completed.');}
}
