<?php
namespace Modules\HRManager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\HRManager\Services\HrAnalyticsService;

class HrAnalyticsController extends Controller
{
    protected HrAnalyticsService $service;
    public function __construct(HrAnalyticsService $service){ $this->service=$service; }

    public function index(Request $request)
    {
        $b=session('business.id');
        $metrics=$this->service->dashboardMetrics($b);
        $snapshots=$this->rows('hr_analytics_snapshots',$b,25);
        $targets=$this->rows('hr_analytics_kpi_targets',$b,25);
        $rules=$this->rows('hr_analytics_alert_rules',$b,25);
        $alerts=$this->alertRows($request,$b);
        $exports=$this->rows('hr_analytics_report_exports',$b,25);
        return view('hrmanager::analytics.index', compact('metrics','snapshots','targets','rules','alerts','exports'));
    }

    public function createSnapshot()
    {
        $this->service->createSnapshot(session('business.id'), auth()->id());
        return back();
    }

    private function hasTable($t){ try{return DB::getSchemaBuilder()->hasTable($t);}catch(\Throwable $e){return false;} }
    private function rows($t,$b,$l){ return $this->hasTable($t)?DB::table($t)->where('business_id',$b)->orderByDesc('id')->limit($l)->get():collect(); }
    private function alertRows(Request $r,$b){ if(!$this->hasTable('hr_analytics_alerts')) return collect(); $q=DB::table('hr_analytics_alerts')->where('business_id',$b); if($r->search){$q->where('alert_no','like','%'.$r->search.'%')->orWhere('alert_title','like','%'.$r->search.'%')->orWhere('alert_status','like','%'.$r->search.'%');} return $q->orderByDesc('id')->paginate($r->per_page==='all'?1000:(int)$r->get('per_page',25)); }
}
