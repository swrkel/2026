<?php

namespace Modules\HRManager\Services;

use Illuminate\Support\Facades\DB;
use Modules\HRManager\Models\HrPayrollRun;
use Modules\HRManager\Models\HrPayslip;
use Modules\HRManager\Models\HrSalaryStructure;

class HrPayrollService
{
    public function createDraftRun(array $data): HrPayrollRun
    {
        return HrPayrollRun::create([
            'business_id' => $data['business_id'],
            'payroll_period_id' => $data['payroll_period_id'],
            'run_no' => $data['run_no'] ?? ('PAY-' . now()->format('YmdHis')),
            'run_date' => $data['run_date'] ?? now()->toDateString(),
            'status' => 'draft',
            'remarks' => $data['remarks'] ?? null,
            'processed_by' => $data['user_id'] ?? null,
        ]);
    }

    public function generateDraftPayslip(HrPayrollRun $run, int $employeeId): HrPayslip
    {
        return DB::transaction(function () use ($run, $employeeId) {
            $structure = HrSalaryStructure::where('business_id', $run->business_id)
                ->where('employee_id', $employeeId)
                ->where('status', 'active')
                ->orderByDesc('effective_from')
                ->first();

            $basic = $structure ? $structure->basic_salary : 0;
            $gross = $basic;
            $deductions = 0;
            $net = $gross - $deductions;

            return HrPayslip::updateOrCreate(
                [
                    'business_id' => $run->business_id,
                    'employee_id' => $employeeId,
                    'payroll_period_id' => $run->payroll_period_id,
                ],
                [
                    'payroll_run_id' => $run->id,
                    'payslip_no' => 'PS-' . $run->id . '-' . $employeeId,
                    'basic_salary' => $basic,
                    'gross_salary' => $gross,
                    'allowance_total' => 0,
                    'deduction_total' => $deductions,
                    'net_salary' => $net,
                    'payment_status' => 'unpaid',
                ]
            );
        });
    }
}
