<?php
namespace Modules\DealerManagement\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\DealerManagement\Services\DealerContext;
use Modules\DealerManagement\Services\LoginCodeService;
use Modules\DealerManagement\Services\AuditService;

class UserController extends Controller
{
    public function index()
    {
        $ctx=app(DealerContext::class); $me=$ctx->user();
        $users=DB::table('dlr_users')->leftJoin('dlr_roles','dlr_roles.id','=','dlr_users.role_id')->where('dlr_users.dealer_id',$me->dealer_id)
            ->select('dlr_users.*','dlr_roles.name as role_name')->orderBy('dlr_users.name')->get();
        $roles=DB::table('dlr_roles')->where('dealer_id',$me->dealer_id)->orderBy('name')->get();
        $outlets=DB::table('dlr_outlets')->where('dealer_id',$me->dealer_id)->where('is_active',1)->orderBy('name')->get();
        return view('dealermanagement::users.index',compact('users','roles','outlets','me'));
    }

    public function store(Request $request, LoginCodeService $codes)
    {
        $me=app(DealerContext::class)->user(); abort_unless($me && (int)$me->is_dealer_admin===1,403);
        $data=$request->validate(['name'=>'required|string|max:191','email'=>'nullable|email','mobile'=>'nullable|string|max:50','role_id'=>'nullable|integer','outlet_ids'=>'array','outlet_ids.*'=>'integer','notes'=>'nullable|string']);
        $code=$codes->generate($me->business_id); $temporary=Str::random(10);
        $id=DB::table('dlr_users')->insertGetId([
            'business_id'=>$me->business_id,'dealer_id'=>$me->dealer_id,'role_id'=>$data['role_id']??null,'name'=>$data['name'],'login_code'=>$code,
            'email'=>$data['email']??null,'mobile'=>$data['mobile']??null,'password'=>Hash::make($temporary),'is_dealer_admin'=>0,'is_active'=>1,'must_change_password'=>1,
            'created_by_dealer_user_id'=>$me->id,'notes'=>$data['notes']??null,'created_at'=>now(),'updated_at'=>now(),
        ]);
        $requestedOutletIds=array_values(array_unique(array_map('intval',$data['outlet_ids']??[])));
        if($requestedOutletIds){
            $validOutletIds=DB::table('dlr_outlets')->where('dealer_id',$me->dealer_id)->whereIn('id',$requestedOutletIds)->pluck('id')->all();
            $now=now(); $links=[];
            foreach($validOutletIds as $outletId) $links[]=['dealer_user_id'=>$id,'outlet_id'=>$outletId,'created_at'=>$now];
            if($links) DB::table('dlr_user_outlets')->insert($links);
        }
        app(AuditService::class)->log('user_created','dealer_user',$id,null,['name'=>$data['name'],'login_code'=>$code]);
        return back()->with('status','User created. Login Code: '.$code.' | Temporary Password: '.$temporary.' (copy this now).');
    }

    public function resetPassword(int $id)
    {
        $me=app(DealerContext::class)->user(); abort_unless($me && (int)$me->is_dealer_admin===1,403);
        $target=DB::table('dlr_users')->where('dealer_id',$me->dealer_id)->where('id',$id)->first(); abort_unless($target,404);
        $temporary=Str::random(10);
        DB::table('dlr_users')->where('id',$id)->update(['password'=>Hash::make($temporary),'must_change_password'=>1,'password_reset_at'=>now(),'updated_at'=>now()]);
        app(AuditService::class)->log('user_password_reset','dealer_user',$id);
        return back()->with('status','Password reset for '.$target->name.'. Temporary Password: '.$temporary.' (copy this now).');
    }

    public function toggle(int $id)
    {
        $me=app(DealerContext::class)->user(); abort_unless($me && (int)$me->is_dealer_admin===1,403);
        $target=DB::table('dlr_users')->where('dealer_id',$me->dealer_id)->where('id',$id)->first(); abort_unless($target,404); abort_if((int)$target->id===(int)$me->id,422,'You cannot deactivate your own account.');
        DB::table('dlr_users')->where('id',$id)->update(['is_active'=>(int)!$target->is_active,'updated_at'=>now()]);
        return back()->with('status','User status updated.');
    }
}
