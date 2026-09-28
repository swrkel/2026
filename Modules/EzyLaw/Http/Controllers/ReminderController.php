<?php
namespace Modules\EzyLaw\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\EzyLaw\Entities\LawReminder;
use Modules\EzyLaw\Utilities\{EzyLawTenantGuard,EzyLawReferenceGuard};
class ReminderController extends Controller
{
    public function store(Request $r){$d=$r->validate(['client_id'=>'nullable|integer','matter_id'=>'nullable|integer','user_id'=>'nullable|integer','title'=>'required|string|max:191','remind_at'=>'required|date','channel'=>'required|in:in_app,email,sms,whatsapp','repeat_rule'=>'nullable|string|max:80','notes'=>'nullable|string']);if(!empty($d['client_id']))EzyLawReferenceGuard::client($d['client_id']);if(!empty($d['matter_id'])){if(!empty($d['client_id']))EzyLawReferenceGuard::matterForClient($d['matter_id'],$d['client_id']);else EzyLawReferenceGuard::matter($d['matter_id']);}LawReminder::create($d+['business_id'=>EzyLawTenantGuard::businessId(),'status'=>'pending','created_by'=>auth()->id()]);return back()->with('success','Reminder saved.');}
    public function dismiss(LawReminder $reminder){$reminder->update(['status'=>'dismissed']);return back()->with('success','Reminder dismissed.');}
    public function destroy(LawReminder $reminder){$reminder->delete();return back()->with('success','Reminder removed.');}
}
