<?php
namespace Modules\EzyLaw\Http\Controllers;
use Illuminate\Http\Request; use Illuminate\Routing\Controller;
use Modules\EzyLaw\Entities\LawNotificationRule; use Modules\EzyLaw\Services\{NotificationRuleService,NotificationService}; use Modules\EzyLaw\Utilities\EzyLawTenantGuard;
class NotificationRuleController extends Controller {
 public function index(){return view('ezylaw::notification_rules.index',['rules'=>LawNotificationRule::orderBy('name')->get()]);}
 public function store(Request $r){$d=$r->validate(['name'=>'required|string|max:191','event_key'=>'required|in:deadline_due,hearing_due,task_due','channel'=>'required|in:in_app,email,sms,whatsapp','days_before'=>'required|integer|min:0|max:365','recipient_type'=>'required|in:assigned_user,responsible_user,client,specific_user','user_id'=>'nullable|integer','active'=>'nullable|boolean']);LawNotificationRule::create($d+['business_id'=>EzyLawTenantGuard::businessId(),'active'=>$r->boolean('active',true),'created_by'=>auth()->id()]);return back()->with('success','Notification rule saved.');}
 public function toggle(LawNotificationRule $rule){$rule->update(['active'=>!$rule->active]);return back()->with('success','Notification rule updated.');}
 public function destroy(LawNotificationRule $rule){$rule->delete();return back()->with('success','Notification rule deleted.');}
 public function run(NotificationRuleService $rules,NotificationService $notifications){$created=$rules->generate();$sent=$notifications->dispatchDue();return back()->with('success','Notification run completed: '.$created.' generated, '.$sent.' dispatched/queued.');}
}
