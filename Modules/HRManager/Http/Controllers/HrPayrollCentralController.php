<?php
namespace Modules\HRManager\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\HRManager\Models\HrPayrollRun;
use Modules\HRManager\Services\HrPayrollCentralService;

class HrPayrollCentralController extends Controller
{
    protected HrPayrollCentralService $payrollService;
    public function __construct(HrPayrollCentralService $payrollService){ $this->payrollService=$payrollService; }

    public function index(Request $request)
    {
        $b=session('business.id');
        $periods=$this->safeRows('hr_payroll_periods',$b,100);
        $runs=$this->payrollRuns($request,$b);
        $runEmployees=$this->safeRows('hr_payroll_run_employees',$b,25);
        $payslips=$this->safeRows('hr_payslips',$b,25);
        $stats=['employees'=>$this->safeCount('hr_employees',$b),'periods'=>$this->safeCount('hr_payroll_periods',$b),'runs'=>$this->safeCount('hr_payroll_runs',$b),'payslips'=>$this->safeCount('hr_payslips',$b),'components'=>$this->safeCount('hr_payroll_components',$b),'net_total'=>$this->safeSum('hr_payroll_runs',$b,'net_total')];
        return view('hrmanager::payroll.index', compact('periods','runs','runEmployees','payslips','stats'));
    }
    public function storeRun(Request $request){ $request->validate(['payroll_period_id'=>'required|integer']); $this->payrollService->createRun(['business_id'=>session('business.id'),'payroll_period_id'=>$request->payroll_period_id,'remarks'=>$request->remarks,'user_id'=>auth()->id()]); return back(); }
    public function processRun($id){ $run=HrPayrollRun::where('business_id',session('business.id'))->findOrFail($id); $this->payrollService->generateEmployeeDrafts($run); return back(); }
    private function hasTable($t){ try{return DB::getSchemaBuilder()->hasTable($t);}catch(\Throwable $e){return false;} }
    private function safeRows($t,$b,$l){ return $this->hasTable($t)?DB::table($t)->where('business_id',$b)->orderByDesc('id')->limit($l)->get():collect(); }
    private function safeCount($t,$b){ return $this->hasTable($t)?DB::table($t)->where('business_id',$b)->count():0; }
    private function safeSum($t,$b,$c){ return $this->hasTable($t)?(float)DB::table($t)->where('business_id',$b)->sum($c):0; }
    private function payrollRuns(Request $r,$b){ if(!$this->hasTable('hr_payroll_runs'))return collect(); $q=DB::table('hr_payroll_runs')->where('business_id',$b); if($r->search){$q->where('run_no','like','%'.$r->search.'%')->orWhere('status','like','%'.$r->search.'%');} return $q->orderByDesc('id')->paginate($r->per_page==='all'?1000:(int)$r->get('per_page',25)); }
}
