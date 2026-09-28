<?php
namespace Modules\HRManager\Services;

use Illuminate\Support\Facades\DB;
use Modules\HRManager\Models\HrPayrollRun;
use Modules\HRManager\Models\HrPayrollRunLine;
use Modules\HRManager\Models\HrPayslip;
use Modules\HRManager\Models\HrPayrollAuditLog;

class HrPayrollEngineService
{
    public function createRun(array $data): HrPayrollRun
    {
        return DB::transaction(function () use ($data) {
            $run = HrPayrollRun::create([
                'business_id' => $data['business_id'],
                'payroll_period_id' => $data['payroll_period_id'],
                'run_no' => $data['run_no'] ?? 'PR-' . now()->format('YmdHis'),
                'run_date' => $data['run_date'] ?? now()->toDateString(),
                'run_status' => 'draft',
                'approval_status' => 'pending',
                'remarks' => $data['remarks'] ?? null,
            ]);

            $this->audit($run, null, 'created', null, 'draft', 'Payroll run created.', $data['user_id'] ?? null);

            return $run;
        });
    }

    public function calculateRun(HrPayrollRun $run, ?int $userId = null): HrPayrollRun
    {
        return DB::transaction(function () use ($run, $userId) {
            $employees = DB::table('hr_employees')
                ->where('business_id', $run->business_id)
                ->where('status', 1)
                ->get();

            $earningTotal = 0;
            $deductionTotal = 0;
            $grossTotal = 0;
            $netTotal = 0;

            foreach ($employees as $employee) {
                $salary = DB::table('hr_employee_salary_assignments')
                    ->where('business_id', $run->business_id)
                    ->where('employee_id', $employee->id)
                    ->where('assignment_status', 'active')
                    ->orderByDesc('effective_from')
                    ->first();

                $basic = $salary ? (float)$salary->basic_salary : (float)($employee->basic_salary ?? 0);
                $earnings = $basic;
                $deductions = 0;
                $gross = $earnings;
                $net = $gross - $deductions;

                $line = HrPayrollRunLine::updateOrCreate(
                    [
                        'business_id' => $run->business_id,
                        'payroll_run_id' => $run->id,
                        'employee_id' => $employee->id,
                    ],
                    [
                        'payroll_period_id' => $run->payroll_period_id,
                        'basic_salary' => $basic,
                        'earning_total' => $earnings,
                        'deduction_total' => $deductions,
                        'gross_salary' => $gross,
                        'net_salary' => $net,
                        'line_status' => 'calculated',
                    ]
                );

                $payslip = HrPayslip::updateOrCreate(
                    [
                        'business_id' => $run->business_id,
                        'payroll_run_id' => $run->id,
                        'employee_id' => $employee->id,
                    ],
                    [
                        'payslip_no' => 'PS-' . $run->id . '-' . $employee->id,
                        'payroll_period_id' => $run->payroll_period_id,
                        'basic_salary' => $basic,
                        'gross_salary' => $gross,
                        'deduction_total' => $deductions,
                        'net_salary' => $net,
                        'payslip_status' => 'draft',
                        'payment_status' => 'unpaid',
                    ]
                );

                $line->payslip_id = $payslip->id;
                $line->save();

                $earningTotal += $earnings;
                $deductionTotal += $deductions;
                $grossTotal += $gross;
                $netTotal += $net;
            }

            $run->employee_count = $employees->count();
            $run->earning_total = $earningTotal;
            $run->deduction_total = $deductionTotal;
            $run->gross_total = $grossTotal;
            $run->net_total = $netTotal;
            $run->run_status = 'calculated';
            $run->processed_by = $userId;
            $run->processed_at = now();
            $run->save();

            $this->audit($run, null, 'calculated', 'draft', 'calculated', 'Payroll run calculated.', $userId);

            return $run;
        });
    }

    private function audit(HrPayrollRun $run, $employeeId, string $action, ?string $old, ?string $new, ?string $note, ?int $userId): void
    {
        HrPayrollAuditLog::create([
            'business_id' => $run->business_id,
            'payroll_run_id' => $run->id,
            'employee_id' => $employeeId,
            'action' => $action,
            'old_status' => $old,
            'new_status' => $new,
            'note' => $note,
            'action_by' => $userId,
            'action_at' => now(),
        ]);
    }
}
