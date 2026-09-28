<?php
namespace Modules\Audit\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Audit\Models\AuditRuleSetting;
use Modules\Audit\Services\ModuleRegistry;

class RuleController extends Controller
{
    public function index(ModuleRegistry $registry)
    {
        $rules=[];foreach($registry->rules() as $class){try{$r=app($class);$rules[]=['code'=>$r->code(),'module'=>$r->module(),'title'=>$r->title(),'description'=>$r->description(),'severity'=>$r->defaultSeverity()];}catch(\Throwable $e){}}
        $settings=AuditRuleSetting::where(function($q){$q->whereNull('business_id');if(session('user.business_id'))$q->orWhere('business_id',session('user.business_id'));})->get()->keyBy('rule_code');
        return view('audit::rules.index',compact('rules','settings'));
    }
    public function update()
    {
        request()->validate(['rule_code'=>'required|string|max:100']);
        AuditRuleSetting::updateOrCreate(['rule_code'=>request('rule_code'),'business_id'=>session('user.business_id')],[
            'is_enabled'=>request('is_enabled') == '1','severity_override'=>request('severity_override'),'updated_by'=>auth()->id(),
        ]);
        return back()->with('success','Audit rule setting saved.');
    }
}
