<?php
namespace Modules\DealerManagement\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\DealerManagement\Services\DealerAuthService;
use Modules\DealerManagement\Services\DealerContext;
use Modules\DealerManagement\Services\DistributionAvailabilityService;
use Modules\DealerManagement\Services\LoginCodeService;
use Modules\DealerManagement\Services\AuditService;

class AuthController extends Controller
{
    public function showLogin(DistributionAvailabilityService $availability)
    {
        $businessIds = $availability->enabledBusinesses();
        $businesses = collect();
        if (!empty($businessIds) && \Illuminate\Support\Facades\Schema::hasTable('business')) {
            $businesses = DB::table('business')->whereIn('id',$businessIds)->get(['id','name']);
        } elseif (!empty($businessIds) && \Illuminate\Support\Facades\Schema::hasTable('businesses')) {
            $businesses = DB::table('businesses')->whereIn('id',$businessIds)->get(['id','name']);
        }
        return view('dealermanagement::auth.login', compact('businessIds','businesses'));
    }

    public function login(Request $request, DistributionAvailabilityService $availability, DealerAuthService $auth)
    {
        $data = $request->validate(['business_id'=>'required|integer','login_code'=>'required|digits:4','password'=>'required|string']);
        if (!$availability->enabledForBusiness((int)$data['business_id'])) return back()->withErrors(['login_code'=>'Dealer login is not enabled for this business.'])->withInput();
        $user = $auth->attempt((int)$data['business_id'],$data['login_code'],$data['password']);
        if (!$user) return back()->withErrors(['login_code'=>'Invalid login code or password.'])->withInput();
        session(['dealer_management_business_id'=>(int)$data['business_id']]);
        if ((int)$user->must_change_password === 1) return redirect()->route('dealermanagement.portal.profile.password');
        return redirect()->route('dealermanagement.portal.dashboard');
    }

    public function logout(DealerAuthService $auth)
    {
        $auth->logout();
        return redirect()->route('dealermanagement.login');
    }

    public function passwordForm() { return view('dealermanagement::auth.change-password',['user'=>app(DealerContext::class)->user()]); }

    public function passwordUpdate(Request $request)
    {
        $user = app(DealerContext::class)->user(); abort_unless($user,403);
        $data=$request->validate(['current_password'=>'required|string','password'=>'required|string|min:8|confirmed']);
        if (!Hash::check($data['current_password'],$user->password)) return back()->withErrors(['current_password'=>'Current password is incorrect.']);
        DB::table('dlr_users')->where('id',$user->id)->update(['password'=>Hash::make($data['password']),'must_change_password'=>0,'password_reset_at'=>now(),'updated_at'=>now()]);
        app(AuditService::class)->log('password_changed','dealer_user',$user->id);
        return redirect()->route('dealermanagement.portal.dashboard')->with('status','Password changed successfully.');
    }

    public function loginCodeForm() { return view('dealermanagement::auth.change-login-code',['user'=>app(DealerContext::class)->user()]); }

    public function loginCodeUpdate(Request $request, LoginCodeService $codes)
    {
        $user=app(DealerContext::class)->user(); abort_unless($user,403);
        $data=$request->validate(['login_code'=>'required|digits:4']);
        if (!$codes->validateAvailable((int)$user->business_id,$data['login_code'],(int)$user->id)) return back()->withErrors(['login_code'=>'This 4 digit login code is already in use.']);
        DB::table('dlr_users')->where('id',$user->id)->update(['login_code'=>$data['login_code'],'updated_at'=>now()]);
        app(AuditService::class)->log('login_code_changed','dealer_user',$user->id,null,['login_code'=>$data['login_code']]);
        return back()->with('status','Login code changed successfully.');
    }
}
