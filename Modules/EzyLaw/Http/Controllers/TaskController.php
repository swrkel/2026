<?php
namespace Modules\EzyLaw\Http\Controllers;
use Illuminate\Http\Request; use Illuminate\Routing\Controller; use Modules\EzyLaw\Entities\{LawTask,LawMatter}; use Modules\EzyLaw\Utilities\{EzyLawTenantGuard,EzyLawReferenceGuard};
class TaskController extends Controller
{
 public function index(){return view('ezylaw::tasks.index',['tasks'=>LawTask::orderByRaw("FIELD(status,'open','in_progress','completed','cancelled')")->orderBy('due_at')->paginate(25),'matters'=>LawMatter::whereIn('status',['open','pending'])->orderBy('matter_no')->get()]);}
 public function store(Request $r){$d=$r->validate(['matter_id'=>'nullable|integer','assigned_user_id'=>'nullable|integer','title'=>'required|string|max:191','description'=>'nullable|string','due_at'=>'nullable|date','priority'=>'required|in:low,normal,high,urgent','status'=>'required|in:open,in_progress,completed,cancelled']); EzyLawReferenceGuard::matter($d['matter_id']??null); if($d['status']==='completed')$d['completed_at']=now(); LawTask::create($d+['business_id'=>EzyLawTenantGuard::businessId(),'created_by'=>auth()->id()]); return back()->with('success','Task saved.');}
 public function update(Request $r,LawTask $task){$d=$r->validate(['status'=>'required|in:open,in_progress,completed,cancelled']);$d['completed_at']=$d['status']==='completed'?now():null;$task->update($d);return back()->with('success','Task updated.');}
}
