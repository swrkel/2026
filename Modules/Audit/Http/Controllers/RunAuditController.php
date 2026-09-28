<?php
namespace Modules\Audit\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Audit\Services\AuditContext;
use Modules\Audit\Services\AuditEngine;
use Modules\Audit\Services\ModuleRegistry;

class RunAuditController extends Controller
{
    public function index(ModuleRegistry $registry){ return view('audit::run.index',['modules'=>$registry->modules()]); }
    public function store(AuditEngine $engine)
    {
        request()->validate(['modules'=>'nullable|array','modules.*'=>'string']);
        $run=$engine->run(AuditContext::fromRequest(),request('modules',[]));
        return redirect()->route('audit.findings.index',['audit_run_id'=>$run->id])->with('success','Audit completed: '.$run->run_no);
    }
}
