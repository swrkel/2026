<?php
namespace Modules\DealerManagement\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\DealerManagement\Services\DealerContext;

class OutletController extends Controller
{
    public function index(){ $u=app(DealerContext::class)->user(); $outlets=DB::table('dlr_outlets')->where('dealer_id',$u->dealer_id)->orderByDesc('is_default')->orderBy('name')->get(); return view('dealermanagement::outlets.index',compact('outlets','u')); }
    public function store(Request $r){ $u=app(DealerContext::class)->user(); abort_unless((int)$u->is_dealer_admin===1,403); $d=$r->validate(['outlet_code'=>'required|string|max:30','name'=>'required|string|max:191','address'=>'nullable|string','mobile'=>'nullable|string|max:50','is_default'=>'nullable|boolean','notes'=>'nullable|string']); if(!empty($d['is_default'])) DB::table('dlr_outlets')->where('dealer_id',$u->dealer_id)->update(['is_default'=>0]); DB::table('dlr_outlets')->insert(['business_id'=>$u->business_id,'dealer_id'=>$u->dealer_id,'outlet_code'=>$d['outlet_code'],'name'=>$d['name'],'address'=>$d['address']??null,'mobile'=>$d['mobile']??null,'is_default'=>(int)!empty($d['is_default']),'is_active'=>1,'notes'=>$d['notes']??null,'created_at'=>now(),'updated_at'=>now()]); return back()->with('status','Outlet created.'); }
}
