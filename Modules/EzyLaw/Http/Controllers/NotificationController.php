<?php
namespace Modules\EzyLaw\Http\Controllers;
use Illuminate\Http\Request; use Illuminate\Routing\Controller;
use Modules\EzyLaw\Entities\{LawNotification,LawMatter,LawClient}; use Modules\EzyLaw\Services\NotificationService;
class NotificationController extends Controller {
 public function index(){return view('ezylaw::notifications.index',['notifications'=>LawNotification::with(['matter','client'])->orderByDesc('id')->paginate(30),'matters'=>LawMatter::whereIn('status',['open','pending'])->orderBy('matter_no')->get(),'clients'=>LawClient::where('status','active')->orderBy('name')->get()]);}
 public function store(Request $r,NotificationService $s){$d=$r->validate(['user_id'=>'nullable|integer','client_id'=>'nullable|integer','matter_id'=>'nullable|integer','type'=>'required|string|max:50','title'=>'required|string|max:191','body'=>'nullable|string','channel'=>'required|in:in_app,email,sms,whatsapp','scheduled_at'=>'nullable|date']);$s->queue($d);return back()->with('success','Notification queued.');}
 public function read(LawNotification $notification){$notification->update(['read_at'=>now(),'status'=>$notification->status==='pending'?'sent':$notification->status]);return back();}
 public function dispatch(NotificationService $s){$n=$s->dispatchDue();return back()->with('success',$n.' due notification(s) processed/queued.');}
}
