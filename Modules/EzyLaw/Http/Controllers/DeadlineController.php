<?php
namespace Modules\EzyLaw\Http\Controllers;
use Illuminate\Http\Request; use Illuminate\Routing\Controller;
use Modules\EzyLaw\Entities\{LawDeadline,LawMatter,LawClient}; use Modules\EzyLaw\Services\DeadlineService;
class DeadlineController extends Controller {
 public function index(Request $r){$status=$r->input('status','open');$q=LawDeadline::with(['matter','client'])->orderBy('due_at');if($status!=='all')$q->where('status',$status);return view('ezylaw::deadlines.index',['deadlines'=>$q->paginate(30),'matters'=>LawMatter::whereIn('status',['open','pending'])->orderBy('matter_no')->get(),'clients'=>LawClient::where('status','active')->orderBy('name')->get(),'status'=>$status]);}
 public function store(Request $r,DeadlineService $s){$d=$r->validate(['matter_id'=>'nullable|integer','client_id'=>'nullable|integer','deadline_type'=>'required|string|max:80','title'=>'required|string|max:191','basis_date'=>'nullable|date','due_at'=>'required|date','warning_days'=>'nullable|integer|min:0','assigned_user_id'=>'nullable|integer','notes'=>'nullable|string']);$s->create($d);return back()->with('success','Legal deadline created.');}
 public function complete(LawDeadline $deadline,DeadlineService $s){$s->complete($deadline);return back()->with('success','Deadline completed.');}
}
