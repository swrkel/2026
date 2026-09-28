<?php
namespace Modules\Audit\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Audit\Models\AuditFinding;
use Modules\Audit\Services\FilterOptionService;

class FindingController extends Controller
{
    public function index(FilterOptionService $filters)
    {
        $q=AuditFinding::query();
        foreach(['module','severity','status','business_id','location_id','audit_run_id'] as $f) if(request($f)!==null&&request($f)!=='')$q->where($f,request($f));
        if(request('search'))$q->where(function($x){$s='%'.request('search').'%';$x->where('finding_no','like',$s)->orWhere('title','like',$s)->orWhere('message','like',$s)->orWhere('rule_code','like',$s);});
        if(request('from'))$q->whereDate('last_seen_at','>=',request('from'));
        if(request('to'))$q->whereDate('last_seen_at','<=',request('to'));
        $findings=$q->latest('last_seen_at')->paginate(50)->withQueryString();
        $modules=AuditFinding::whereNotNull('module')->distinct()->orderBy('module')->pluck('module');
        return view('audit::findings.index',compact('findings','modules')+['businesses'=>$filters->businesses(),'locations'=>$filters->locations(request('business_id'))]);
    }
    public function show(AuditFinding $finding){$finding->load(['history','resolutions']);return view('audit::findings.show',compact('finding'));}
}
