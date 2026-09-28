<?php
namespace Modules\EzyLaw\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\EzyLaw\Entities\LawConflictCheck;
use Modules\EzyLaw\Services\ConflictCheckService;
class ConflictController extends Controller
{
    public function index(){return view('ezylaw::conflicts.index',['checks'=>LawConflictCheck::withCount('matches')->orderByDesc('id')->paginate(25)]);}
    public function run(Request $r,ConflictCheckService $s){$d=$r->validate(['query_name'=>'required|string|max:191','identifiers'=>'nullable|string','notes'=>'nullable|string']);$check=$s->run($d['query_name'],$d['identifiers']??null,$d['notes']??null);return redirect()->route('ezylaw.conflicts.show',$check);}
    public function show(LawConflictCheck $check){$check->load('matches');return view('ezylaw::conflicts.show',compact('check'));}
}
