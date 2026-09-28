<?php
namespace Modules\DealerManagement\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\DealerManagement\Services\HubDatabaseManager;
use Modules\DealerManagement\Services\HubContext;

class HubAuthController extends Controller
{
    public function showLogin(){ return view('dealermanagement::hub.auth.login'); }
    public function login(Request $r){$d=$r->validate(['hub_code'=>'required|string','login_code'=>'required|digits:4','password'=>'required|string']);$row=app(HubDatabaseManager::class)->central(function()use($d){return DB::table('dlr_hub_users as u')->join('dlr_hub_dealers as d','d.id','=','u.hub_dealer_id')->where('d.hub_code',strtoupper(trim($d['hub_code'])))->where('u.login_code',$d['login_code'])->where('u.is_active',1)->where('d.status','active')->select('u.*')->first();});if(!$row||!Hash::check($d['password'],$row->password))return back()->withInput($r->except('password'))->withErrors(['login_code'=>'Invalid Dealer Hub login details.']);session([config('dealermanagement.hub_session_key','dealer_hub_user_id')=>$row->id]);app(HubDatabaseManager::class)->central(fn()=>DB::table('dlr_hub_users')->where('id',$row->id)->update(['last_login_at'=>now(),'updated_at'=>now()]));return redirect()->route('dealermanagement.hub.dashboard');}
    public function logout(Request $r){$r->session()->forget(config('dealermanagement.hub_session_key','dealer_hub_user_id'));return redirect()->route('dealermanagement.hub.login');}
    public function passwordForm(){return view('dealermanagement::hub.auth.password');}
    public function passwordUpdate(Request $r){$d=$r->validate(['current_password'=>'required','password'=>'required|min:8|confirmed']);$u=app(HubContext::class)->user();if(!Hash::check($d['current_password'],$u->password))return back()->withErrors(['current_password'=>'Current password is incorrect.']);app(HubDatabaseManager::class)->central(fn()=>DB::table('dlr_hub_users')->where('id',$u->id)->update(['password'=>Hash::make($d['password']),'must_change_password'=>0,'updated_at'=>now()]));return back()->with('status','Password changed successfully.');}
    public function loginCodeForm(){return view('dealermanagement::hub.auth.login-code');}
    public function loginCodeUpdate(Request $r){$d=$r->validate(['login_code'=>'required|digits:4']);$u=app(HubContext::class)->user();$exists=app(HubDatabaseManager::class)->central(fn()=>DB::table('dlr_hub_users')->where('hub_dealer_id',$u->hub_dealer_id)->where('login_code',$d['login_code'])->where('id','<>',$u->id)->exists());if($exists)return back()->withErrors(['login_code'=>'This 4-digit code is already used by another staff member.']);app(HubDatabaseManager::class)->central(fn()=>DB::table('dlr_hub_users')->where('id',$u->id)->update(['login_code'=>$d['login_code'],'updated_at'=>now()]));return back()->with('status','Login code changed successfully.');}
}
