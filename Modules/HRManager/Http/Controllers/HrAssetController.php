<?php
namespace Modules\HRManager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\HRManager\Services\HrAssetService;

class HrAssetController extends Controller
{
    protected HrAssetService $service;
    public function __construct(HrAssetService $service){ $this->service=$service; }

    public function index(Request $request)
    {
        $b=session('business.id');
        $employees=$this->rows('hr_employees',$b,200);
        $categories=$this->rows('hr_asset_categories',$b,25);
        $assets=$this->assetRows($request,$b);
        $availableAssets=$this->availableAssets($b);
        $assignments=$this->rows('hr_asset_assignments',$b,25);
        $returns=$this->rows('hr_asset_returns',$b,25);
        $maintenance=$this->rows('hr_asset_maintenance',$b,25);
        $damageReports=$this->rows('hr_asset_damage_reports',$b,25);
        $stats=[
            'employees'=>$this->count('hr_employees',$b),
            'categories'=>$this->count('hr_asset_categories',$b),
            'assets'=>$this->count('hr_assets',$b),
            'available'=>$this->count('hr_assets',$b,['asset_status'=>'available']),
            'assigned'=>$this->count('hr_assets',$b,['asset_status'=>'assigned']),
            'returns'=>$this->count('hr_asset_returns',$b),
            'maintenance'=>$this->count('hr_asset_maintenance',$b),
            'damages'=>$this->count('hr_asset_damage_reports',$b),
        ];
        return view('hrmanager::assets.index', compact('employees','categories','assets','availableAssets','assignments','returns','maintenance','damageReports','stats'));
    }

    public function assign(Request $request)
    {
        $request->validate(['asset_id'=>'required|integer','employee_id'=>'required|integer']);
        $this->service->assign([
            'business_id'=>session('business.id'),
            'asset_id'=>$request->asset_id,
            'employee_id'=>$request->employee_id,
            'assigned_date'=>$request->assigned_date,
            'expected_return_date'=>$request->expected_return_date,
            'remarks'=>$request->remarks,
            'user_id'=>auth()->id(),
        ]);
        return back();
    }

    private function hasTable($t){ try{return DB::getSchemaBuilder()->hasTable($t);}catch(\Throwable $e){return false;} }
    private function rows($t,$b,$l){ return $this->hasTable($t)?DB::table($t)->where('business_id',$b)->orderByDesc('id')->limit($l)->get():collect(); }
    private function count($t,$b,$w=[]){ if(!$this->hasTable($t)) return 0; $q=DB::table($t)->where('business_id',$b); foreach($w as $k=>$v){$q->where($k,$v);} return $q->count(); }
    private function availableAssets($b){ return $this->hasTable('hr_assets')?DB::table('hr_assets')->where('business_id',$b)->where('asset_status','available')->orderBy('asset_name')->limit(200)->get():collect(); }
    private function assetRows(Request $r,$b){ if(!$this->hasTable('hr_assets')) return collect(); $q=DB::table('hr_assets')->where('business_id',$b); if($r->search){$q->where('asset_no','like','%'.$r->search.'%')->orWhere('asset_name','like','%'.$r->search.'%')->orWhere('serial_no','like','%'.$r->search.'%');} return $q->orderByDesc('id')->paginate($r->per_page==='all'?1000:(int)$r->get('per_page',25)); }
}
