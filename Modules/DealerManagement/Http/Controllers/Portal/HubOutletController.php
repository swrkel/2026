<?php
namespace Modules\DealerManagement\Http\Controllers\Portal;
use App\Http\Controllers\Controller;use Illuminate\Http\Request;use Illuminate\Support\Facades\DB;use Modules\DealerManagement\Services\{HubContext,HubDatabaseManager};
class HubOutletController extends Controller
{
 public function index(){ $u=app(HubContext::class)->user();abort_unless($u->is_hub_admin,403);$rows=app(HubDatabaseManager::class)->central(fn()=>DB::table('dlr_hub_outlets')->where('hub_dealer_id',$u->hub_dealer_id)->orderByDesc('is_default')->orderBy('name')->get());return view('dealermanagement::hub.outlets.index',compact('rows')); }
 public function store(Request $r){$u=app(HubContext::class)->user();abort_unless($u->is_hub_admin,403);$d=$r->validate(['outlet_code'=>'required|string|max:30','name'=>'required|string|max:191','address'=>'nullable|string','mobile'=>'nullable|string|max:50','notes'=>'nullable|string']);app(HubDatabaseManager::class)->central(fn()=>DB::table('dlr_hub_outlets')->insert(['hub_dealer_id'=>$u->hub_dealer_id,'outlet_code'=>$d['outlet_code'],'name'=>$d['name'],'address'=>$d['address']??null,'mobile'=>$d['mobile']??null,'is_default'=>0,'is_active'=>1,'notes'=>$d['notes']??null,'created_at'=>now(),'updated_at'=>now()]));return back()->with('status','Outlet created.'); }
}
