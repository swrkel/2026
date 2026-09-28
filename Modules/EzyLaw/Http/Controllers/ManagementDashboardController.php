<?php
namespace Modules\EzyLaw\Http\Controllers;
use Illuminate\Http\Request; use Illuminate\Routing\Controller;
use Modules\EzyLaw\Services\ManagementMetricsService; use Modules\EzyLaw\Entities\LawLawyerTarget; use Modules\EzyLaw\Utilities\EzyLawTenantGuard;
class ManagementDashboardController extends Controller {
 public function index(Request $r,ManagementMetricsService $s){$from=$r->input('from',now()->startOfMonth()->toDateString());$to=$r->input('to',now()->endOfMonth()->toDateString());return view('ezylaw::management.index',$s->dashboard($from,$to)+['targets'=>LawLawyerTarget::orderByDesc('period_start')->limit(100)->get()]);}
 public function target(Request $r){$d=$r->validate(['user_id'=>'required|integer','period_type'=>'required|in:monthly,quarterly,annual,custom','period_start'=>'required|date','period_end'=>'required|date|after_or_equal:period_start','target_hours'=>'nullable|numeric|min:0','target_billing'=>'nullable|numeric|min:0','target_collections'=>'nullable|numeric|min:0','target_new_matters'=>'nullable|integer|min:0','cost_rate'=>'nullable|numeric|min:0','notes'=>'nullable|string']);LawLawyerTarget::updateOrCreate(['business_id'=>EzyLawTenantGuard::businessId(),'user_id'=>$d['user_id'],'period_start'=>$d['period_start'],'period_end'=>$d['period_end']],$d+['created_by'=>auth()->id()]);return back()->with('success','Lawyer target / cost rate saved.');}
}
