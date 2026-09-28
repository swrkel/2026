<?php
namespace Modules\HRManager\Services;
use Illuminate\Support\Facades\DB;
use Modules\HRManager\Models\HrPayrollRun;
use Modules\HRManager\Models\HrPayrollRunEmployee;
use Modules\HRManager\Models\HrPayslip;
use Modules\HRManager\Models\HrSalaryStructure;
use Modules\HRManager\Models\HrPayrollApprovalLog;

class HrPayrollCentralService
{
    public function createRun(array $data): HrPayrollRun
    {
        return DB::transaction(function () use ($data) {
            $run = HrPayrollRun::create([
                'business_id'=>$data['business_id'],
                'payroll_period_id'=>$data['payroll_period_id'],
                'run_no'=>$data['run_no'] ?? 'PAY-'.now()->format('YmdHis'),
                'run_date'=>$data['run_date'] ?? now()->toDateString(),
                'status'=>'draft',
                'remarks'=>$data['remarks'] ?? null,
                'processed_by'=>$data['user_id'] ?? null,
            ]);
            $this->log($run,'created',null,'draft','Payroll run created.',$data['user_id'] ?? null);
            return $run;
        });
    }

    public function generateEmployeeDrafts(HrPayrollRun $run): HrPayrollRun
    {
        return DB::transaction(function () use ($run) {
            $employees = DB::table('hr_employees')->where('business_id',$run->business_id)->where('status',1)->get();
            $grossTotal = $deductionTotal = $netTotal = 0;
            foreach ($employees as $employee) {
                $structure = HrSalaryStructure::where('business_id',$run->business_id)->where('employee_id',$employee->id)->where('status','active')->orderByDesc('effective_from')->first();
                $basic = $structure ? (float)$structure->basic_salary : (float)($employee->basic_salary ?? 0);
                $gross = $basic; $deductions = 0; $net = $gross - $deductions;
                $line = HrPayrollRunEmployee::updateOrCreate(
                    ['business_id'=>$run->business_id,'payroll_run_id'=>$run->id,'employee_id'=>$employee->id],
                    ['payroll_period_id'=>$run->payroll_period_id,'basic_salary'=>$basic,'earning_total'=>$gross,'deduction_total'=>$deductions,'gross_salary'=>$gross,'net_salary'=>$net,'processing_status'=>'draft']
                );
                $payslip = HrPayslip::updateOrCreate(
                    ['business_id'=>$run->business_id,'employee_id'=>$employee->id,'payroll_period_id'=>$run->payroll_period_id],
                    ['payroll_run_id'=>$run->id,'payslip_no'=>'PS-'.$run->id.'-'.$employee->id,'basic_salary'=>$basic,'gross_salary'=>$gross,'deduction_total'=>$deductions,'net_salary'=>$net,'payment_status'=>'unpaid']
                );
                $line->payslip_id = $payslip->id; $line->save();
                $grossTotal += $gross; $deductionTotal += $deductions; $netTotal += $net;
            }
            $run->employee_count = $employees->count(); $run->gross_total=$grossTotal; $run->deduction_total=$deductionTotal; $run->net_total=$netTotal; $run->status='processed'; $run->save();
            $this->log($run,'processed','draft','processed','Employee payroll drafts generated.',auth()->id());
            return $run;
        });
    }

    private function log(HrPayrollRun $run, string $action, ?string $from, string $to, ?string $note, ?int $userId): void
    {
        HrPayrollApprovalLog::create(['business_id'=>$run->business_id,'payroll_run_id'=>$run->id,'action'=>$action,'from_status'=>$from,'to_status'=>$to,'note'=>$note,'action_by'=>$userId,'action_at'=>now()]);
    }
}
