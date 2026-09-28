<?php
namespace Modules\AutoService\Http\Controllers;
use Illuminate\Support\Facades\DB;
class ReminderController extends AutoServiceBaseController
{
 public function index(){ $q=DB::table('auto_service_reminders'); if($this->businessId())$q->where('business_id',$this->businessId()); return view('autoservice::reminders.index',['reminders'=>$q->orderBy('send_on')->paginate(50)]); }
 public function markSent($id){ DB::table('auto_service_reminders')->where('id',$id)->update(['status'=>'sent','sent_at'=>now(),'updated_at'=>now()]); return back()->with('status','Reminder marked as sent.'); }
}
