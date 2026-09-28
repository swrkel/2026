<?php
namespace Modules\EzyLaw\Http\Controllers;
use Illuminate\Http\Request; use Illuminate\Routing\Controller;
use Modules\EzyLaw\Entities\{LawMatter,LawMatterClosure}; use Modules\EzyLaw\Services\MatterClosureService;
class MatterClosureController extends Controller {
 public function index(MatterClosureService $s){$matters=LawMatter::with('client')->whereIn('status',['open','pending','closed'])->orderByDesc('id')->paginate(30);$blockers=[];foreach($matters as $m){if($m->status!=='closed')$blockers[$m->id]=$s->blockers($m);}return view('ezylaw::closures.index',['matters'=>$matters,'closures'=>LawMatterClosure::with('matter')->get()->keyBy('matter_id'),'blockers'=>$blockers]);}
 public function close(Request $r,LawMatter $matter,MatterClosureService $s){$d=$r->validate(['closure_date'=>'required|date','closure_reason'=>'required|string|max:120','outcome'=>'nullable|string','final_fee_amount'=>'nullable|numeric|min:0','client_notified'=>'nullable|boolean','documents_archived'=>'nullable|boolean','force'=>'nullable|boolean']);$force=$r->boolean('force');if($force){$u=auth()->user();$allowed=method_exists($u,'roleAllowsPermission')?$u->roleAllowsPermission('ezylaw_matter_closure_force'):$u->can('ezylaw_matter_closure_force');if(!$allowed)abort(403,'Force close permission is required.');}$s->close($matter,$d,$force);return back()->with('success','Matter closed.');}
 public function reopen(Request $r,LawMatterClosure $closure,MatterClosureService $s){$d=$r->validate(['reason'=>'required|string|max:2000']);$s->reopen($closure,$d['reason']);return back()->with('success','Matter reopened.');}
}
