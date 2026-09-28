<?php
namespace Modules\DealerManagement\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class DealerAuthService
{
    public function attempt(int $businessId, string $loginCode, string $password): ?object
    {
        $key = 'dealer-login:'.$businessId.':'.$loginCode.':'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, (int)config('dealermanagement.login_rate_limit_per_minute',10))) return null;
        $user = DB::table('dlr_users')->where('business_id',$businessId)->where('login_code',$loginCode)->where('is_active',1)->first();
        if (!$user || !Hash::check($password, $user->password)) {
            RateLimiter::hit($key, 60);
            return null;
        }
        RateLimiter::clear($key);
        session()->regenerate();
        session([config('dealermanagement.session_key','dealer_management_user_id') => $user->id]);
        DB::table('dlr_users')->where('id',$user->id)->update(['last_login_at'=>now(),'updated_at'=>now()]);
        app(AuditService::class)->log('login','dealer_user',$user->id,null,['login_code'=>$user->login_code],$user);
        return $user;
    }

    public function logout(): void
    {
        $user = app(DealerContext::class)->user();
        if ($user) app(AuditService::class)->log('logout','dealer_user',$user->id,null,null,$user);
        session()->forget(config('dealermanagement.session_key','dealer_management_user_id'));
        session()->regenerateToken();
    }
}
