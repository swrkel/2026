<?php
namespace Modules\HRManager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\HRManager\Models\HrPayrollRun;
use Modules\HRManager\Services\HrPayrollEngineService;

class HrPayrollEngineController extends Controller
{
    protected HrPayrollEngineService $service;

    public function __construct(HrPayrollEngineService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $b = session('business.id');

        $periods = $this->rows('hr_payroll_periods', $b, 100);
        $components = $this->rows('hr_salary_components', $b, 25);
        $structures = $this->rows('hr_salary_structures', $b, 25);
        $assignments = $this->rows('hr_employee_salary_assignments', $b, 25);
        $runs = $this->payrollRuns($request, $b);
        $runLines = $this->rows('hr_payroll_run_lines', $b, 25);
        $payslips = $this->rows('hr_payslips', $b, 25);

        $stats = [
            'employees' => $this->count('hr_employees', $b),
            'periods' => $this->count('hr_payroll_periods', $b),
            'components' => $this->count('hr_salary_components', $b),
            'structures' => $this->count('hr_salary_structures', $b),
            'assignments' => $this->count('hr_employee_salary_assignments', $b),
            'runs' => $this->count('hr_payroll_runs', $b),
            'payslips' => $this->count('hr_payslips', $b),
            'net_total' => $this->sum('hr_payroll_runs', $b, 'net_total'),
        ];

        return view('hrmanager::payroll_engine.index', compact('periods', 'components', 'structures', 'assignments', 'runs', 'runLines', 'payslips', 'stats'));
    }

    public function storeRun(Request $request)
    {
        $request->validate(['payroll_period_id' => 'required|integer']);

        $this->service->createRun([
            'business_id' => session('business.id'),
            'payroll_period_id' => $request->payroll_period_id,
            'remarks' => $request->remarks,
            'user_id' => auth()->id(),
        ]);

        return back();
    }

    public function calculateRun($id)
    {
        $run = HrPayrollRun::where('business_id', session('business.id'))->findOrFail($id);
        $this->service->calculateRun($run, auth()->id());

        return back();
    }

    private function hasTable($table)
    {
        try { return DB::getSchemaBuilder()->hasTable($table); } catch (\Throwable $e) { return false; }
    }

    private function rows($table, $businessId, $limit)
    {
        return $this->hasTable($table)
            ? DB::table($table)->where('business_id', $businessId)->orderByDesc('id')->limit($limit)->get()
            : collect();
    }

    private function count($table, $businessId)
    {
        return $this->hasTable($table) ? DB::table($table)->where('business_id', $businessId)->count() : 0;
    }

    private function sum($table, $businessId, $column)
    {
        return $this->hasTable($table) ? (float) DB::table($table)->where('business_id', $businessId)->sum($column) : 0;
    }

    private function payrollRuns(Request $request, $businessId)
    {
        if (!$this->hasTable('hr_payroll_runs')) return collect();

        $q = DB::table('hr_payroll_runs')->where('business_id', $businessId);

        if ($request->search) {
            $q->where(function ($qq) use ($request) {
                $qq->where('run_no', 'like', '%' . $request->search . '%')
                    ->orWhere('run_status', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->date_from) $q->whereDate('run_date', '>=', $request->date_from);
        if ($request->date_to) $q->whereDate('run_date', '<=', $request->date_to);

        return $q->orderByDesc('id')->paginate($request->per_page === 'all' ? 1000 : (int)$request->get('per_page', 25));
    }
}
