<?php
namespace Modules\PumperDashboardNew\Http\Controllers\Admin;
use Modules\PumperDashboardNew\Entities\PoneLoginAttempt;
use Modules\PumperDashboardNew\Http\Controllers\Controller;
use Modules\PumperDashboardNew\Services\PoneLoginAttemptService;
class LoginAttemptController extends Controller
{
    public function __construct(private PoneLoginAttemptService $attempts){}
    public function index(){ $businessId=$this->businessId();$attempts=PoneLoginAttempt::query()->where(function($q)use($businessId){$q->where('business_id',$businessId)->orWhereNull('business_id');})->latest('last_attempt_at')->paginate(100);return view('pumperdashboardnew::admin.login-attempts.index',compact('attempts'));}
    public function unblock(int $attempt){$row=PoneLoginAttempt::query()->whereKey($attempt)->firstOrFail();abort_unless(!$row->business_id||(int)$row->business_id===$this->businessId(),404);$this->attempts->unblock($attempt);return $this->ok(__('pumperdashboardnew::lang.login_unblocked'));}
    public function unblockAll(){$count=$this->attempts->unblockAll($this->businessId());return $this->ok(__('pumperdashboardnew::lang.logins_unblocked',['count'=>$count]));}
}
