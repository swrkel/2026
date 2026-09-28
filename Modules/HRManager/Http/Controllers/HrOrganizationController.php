<?php
namespace Modules\HRManager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\HRManager\Services\HrOrganizationService;

class HrOrganizationController extends Controller
{
    protected HrOrganizationService $service;
    public function __construct(HrOrganizationService $service){ $this->service=$service; }

    public function index(Request $request)
    {
        $b=session('business.id');
        $employees=$this->rows('hr_employees',$b,300);
        $units=$this->unitRows($request,$b);
        $allUnits=$this->rows('hr_organization_units',$b,300);
        $positions=$this->rows('hr_organization_positions',$b,100);
        $assignments=$this->rows('hr_employee_position_assignments',$b,50);
        $relationships=$this->rows('hr_reporting_relationships',$b,50);
        $snapshots=$this->rows('hr_organization_chart_snapshots',$b,25);
        $vacancies=$this->rows('hr_position_vacancy_map',$b,25);

        $stats=[
            'employees'=>$this->count('hr_employees',$b),
            'units'=>$this->count('hr_organization_units',$b),
            'positions'=>$this->count('hr_organization_positions',$b),
            'assignments'=>$this->count('hr_employee_position_assignments',$b),
            'relationships'=>$this->count('hr_reporting_relationships',$b),
            'snapshots'=>$this->count('hr_organization_chart_snapshots',$b),
            'vacancies'=>$this->count('hr_position_vacancy_map',$b),
            'open_vacancies'=>$this->count('hr_position_vacancy_map',$b,['vacancy_status'=>'open']),
        ];

        return view('hrmanager::organization.index', compact('employees','units','allUnits','positions','assignments','relationships','snapshots','vacancies','stats'));
    }

    public function storeUnit(Request $request)
    {
        $request->validate(['unit_name'=>'required|string|max:180']);
        $this->service->createUnit([
            'business_id'=>session('business.id'),
            'unit_name'=>$request->unit_name,
            'unit_type'=>$request->unit_type ?? 'department',
            'parent_unit_id'=>$request->parent_unit_id,
            'manager_employee_id'=>$request->manager_employee_id,
            'user_id'=>auth()->id(),
        ]);
        return back();
    }

    public function storePosition(Request $request)
    {
        $request->validate(['position_title'=>'required|string|max:180']);
        $this->service->createPosition([
            'business_id'=>session('business.id'),
            'position_title'=>$request->position_title,
            'organization_unit_id'=>$request->organization_unit_id,
            'reports_to_position_id'=>$request->reports_to_position_id,
            'position_level'=>$request->position_level ?? 'staff',
            'approved_headcount'=>$request->approved_headcount ?? 1,
            'user_id'=>auth()->id(),
        ]);
        return back();
    }

    public function storeAssignment(Request $request)
    {
        $request->validate(['employee_id'=>'required|integer','position_id'=>'required|integer']);
        $this->service->assignEmployee([
            'business_id'=>session('business.id'),
            'employee_id'=>$request->employee_id,
            'organization_unit_id'=>$request->organization_unit_id,
            'position_id'=>$request->position_id,
            'reports_to_employee_id'=>$request->reports_to_employee_id,
            'effective_from'=>$request->effective_from,
            'assignment_type'=>$request->assignment_type ?? 'primary',
            'user_id'=>auth()->id(),
        ]);
        return back();
    }

    private function hasTable($t){ try{return DB::getSchemaBuilder()->hasTable($t);}catch(\Throwable $e){return false;} }
    private function rows($t,$b,$l){ return $this->hasTable($t)?DB::table($t)->where('business_id',$b)->orderByDesc('id')->limit($l)->get():collect(); }
    private function count($t,$b,$w=[]){ if(!$this->hasTable($t)) return 0; $q=DB::table($t)->where('business_id',$b); foreach($w as $k=>$v){$q->where($k,$v);} return $q->count(); }
    private function unitRows(Request $r,$b){ if(!$this->hasTable('hr_organization_units')) return collect(); $q=DB::table('hr_organization_units')->where('business_id',$b); if($r->search){$q->where('unit_no','like','%'.$r->search.'%')->orWhere('unit_name','like','%'.$r->search.'%')->orWhere('unit_type','like','%'.$r->search.'%');} return $q->orderByDesc('id')->paginate($r->per_page==='all'?1000:(int)$r->get('per_page',25)); }
}
