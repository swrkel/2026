<?php

namespace Modules\HotelManagement\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class StaffPayrollService
{
    public function dashboard(): array
    {
        $staff = $this->rows('hm_staff_members', 200);
        $rules = $this->rows('hm_staff_payroll_rules', 200);
        $runs = $this->rows('hm_staff_payroll_runs', 100);
        $lines = $this->rows('hm_staff_payroll_lines', 300);
        $latestRunId = !empty($runs) ? ($runs[0]->id ?? null) : null;
        $latestLines = $latestRunId ? array_values(array_filter($lines, fn($l) => (int)($l->payroll_run_id ?? 0) === (int)$latestRunId)) : [];
        return [
            'staff' => $staff,
            'rules' => $rules,
            'runs' => $runs,
            'lines' => $lines,
            'latest_lines' => $latestLines,
            'active_rules' => count(array_filter($rules, fn($r) => ($r->status ?? 'active') === 'active')),
            'draft_runs' => count(array_filter($runs, fn($r) => ($r->status ?? 'draft') === 'draft')),
            'latest_gross' => array_sum(array_map(fn($r) => (float)($r->gross_amount ?? 0), $runs)),
            'latest_net' => array_sum(array_map(fn($r) => (float)($r->net_amount ?? 0), $runs)),
            'notes' => [
                'Payroll records are tenant, business and location scoped and do not replace HR Manager payroll.',
                'This layer is for hotel operational payroll costing, service-charge allocation and shift-based payroll review.',
                'Approved payroll can later be bridged to Finance/Accounting without changing hotel source records.',
            ],
        ];
    }

    public function rule(array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_staff_payroll_rules')) return;
        DB::table('hm_staff_payroll_rules')->insert([
            'business_id' => $this->businessId(), 'business_location_id' => $this->locationId(),
            'staff_id' => $data['staff_id'], 'salary_type' => $data['salary_type'],
            'basic_salary' => $data['basic_salary'], 'ot_rate' => $data['ot_rate'] ?? 0,
            'allowance_amount' => $data['allowance_amount'] ?? 0, 'deduction_amount' => $data['deduction_amount'] ?? 0,
            'effective_from' => $data['effective_from'], 'status' => $data['status'] ?? 'active',
            'remarks' => $data['remarks'] ?? null, 'created_by' => $userId, 'updated_by' => $userId,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function run(array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_staff_payroll_runs') || !Schema::hasTable('hm_staff_payroll_lines')) return;
        DB::transaction(function () use ($data, $userId) {
            $runId = DB::table('hm_staff_payroll_runs')->insertGetId([
                'business_id' => $this->businessId(), 'business_location_id' => $this->locationId(),
                'payroll_no' => $data['payroll_no'] ?? $this->nextNumber('hm_staff_payroll_runs','payroll_no','HPY'),
                'period_from' => $data['period_from'], 'period_to' => $data['period_to'],
                'pay_date' => $data['pay_date'] ?? $data['period_to'], 'department' => $data['department'] ?? null,
                'gross_amount' => 0, 'deduction_amount' => 0, 'net_amount' => 0, 'status' => 'draft',
                'remarks' => $data['remarks'] ?? null, 'created_by' => $userId, 'updated_by' => $userId,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $rules = $this->payrollRules($data['department'] ?? null);
            $gross = $deduction = $net = 0;
            foreach ($rules as $rule) {
                $attendanceDays = $this->attendanceDays((int)$rule->staff_id, $data['period_from'], $data['period_to']);
                $base = (float)$rule->basic_salary;
                if (($rule->salary_type ?? 'monthly') === 'daily') { $base = $base * max(1, $attendanceDays); }
                if (($rule->salary_type ?? 'monthly') === 'hourly') { $base = $base * max(1, $attendanceDays) * 8; }
                $allowance = (float)($rule->allowance_amount ?? 0);
                $ded = (float)($rule->deduction_amount ?? 0);
                $lineGross = $base + $allowance;
                $lineNet = $lineGross - $ded;
                DB::table('hm_staff_payroll_lines')->insert([
                    'business_id' => $this->businessId(), 'business_location_id' => $this->locationId(),
                    'payroll_run_id' => $runId, 'staff_id' => $rule->staff_id, 'payroll_rule_id' => $rule->id,
                    'attendance_days' => $attendanceDays, 'ot_hours' => 0, 'basic_amount' => $base,
                    'ot_amount' => 0, 'allowance_amount' => $allowance, 'deduction_amount' => $ded,
                    'gross_amount' => $lineGross, 'net_amount' => $lineNet, 'status' => 'draft',
                    'remarks' => null, 'created_by' => $userId, 'updated_by' => $userId, 'created_at' => now(), 'updated_at' => now(),
                ]);
                $gross += $lineGross; $deduction += $ded; $net += $lineNet;
            }
            DB::table('hm_staff_payroll_runs')->where('id',$runId)->update([
                'gross_amount' => $gross, 'deduction_amount' => $deduction, 'net_amount' => $net, 'updated_at' => now(),
            ]);
        });
    }

    public function lineAdjust(int $id, array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_staff_payroll_lines')) return;
        $line = DB::table('hm_staff_payroll_lines')->where('id',$id)->where('business_id',$this->businessId())->first();
        if (!$line) return;
        $otHours = (float)($data['ot_hours'] ?? $line->ot_hours ?? 0);
        $rule = Schema::hasTable('hm_staff_payroll_rules') ? DB::table('hm_staff_payroll_rules')->where('id',$line->payroll_rule_id)->first() : null;
        $otAmount = $otHours * (float)($rule->ot_rate ?? 0);
        $allowance = (float)($data['allowance_amount'] ?? $line->allowance_amount ?? 0);
        $deduction = (float)($data['deduction_amount'] ?? $line->deduction_amount ?? 0);
        $gross = (float)$line->basic_amount + $otAmount + $allowance;
        $net = $gross - $deduction;
        DB::table('hm_staff_payroll_lines')->where('id',$id)->where('business_id',$this->businessId())->update([
            'ot_hours' => $otHours, 'ot_amount' => $otAmount, 'allowance_amount' => $allowance,
            'deduction_amount' => $deduction, 'gross_amount' => $gross, 'net_amount' => $net,
            'remarks' => $data['remarks'] ?? $line->remarks, 'updated_by' => $userId, 'updated_at' => now(),
        ]);
        $this->refreshRun((int)$line->payroll_run_id);
    }

    public function status(int $id, array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_staff_payroll_runs')) return;
        DB::table('hm_staff_payroll_runs')->where('id',$id)->where('business_id',$this->businessId())->update([
            'status' => $data['status'], 'remarks' => $data['remarks'] ?? DB::raw('remarks'),
            'approved_by' => in_array($data['status'], ['approved','posted']) ? $userId : null,
            'approved_at' => in_array($data['status'], ['approved','posted']) ? now() : null,
            'updated_by' => $userId, 'updated_at' => now(),
        ]);
        if (Schema::hasTable('hm_staff_payroll_lines')) {
            DB::table('hm_staff_payroll_lines')->where('payroll_run_id',$id)->where('business_id',$this->businessId())->update(['status'=>$data['status'], 'updated_by'=>$userId, 'updated_at'=>now()]);
        }
    }

    private function payrollRules(?string $department = null): array
    {
        if (!Schema::hasTable('hm_staff_payroll_rules')) return [];
        $q = DB::table('hm_staff_payroll_rules as pr')->where('pr.business_id',$this->businessId())->where('pr.status','active');
        if ($department && Schema::hasTable('hm_staff_members')) {
            $q->join('hm_staff_members as sm','sm.id','=','pr.staff_id')->where('sm.department',$department)->select('pr.*');
        }
        return $q->orderBy('pr.staff_id')->get()->all();
    }

    private function attendanceDays(int $staffId, string $from, string $to): int
    {
        if (!Schema::hasTable('hm_staff_attendance_logs')) return 0;
        try { return (int) DB::table('hm_staff_attendance_logs')->where('business_id',$this->businessId())->where('staff_id',$staffId)->whereBetween('attendance_date',[$from,$to])->where('status','!=','absent')->count(); }
        catch (Throwable $e) { return 0; }
    }

    private function refreshRun(int $runId): void
    {
        if (!Schema::hasTable('hm_staff_payroll_runs') || !Schema::hasTable('hm_staff_payroll_lines')) return;
        $totals = DB::table('hm_staff_payroll_lines')->where('business_id',$this->businessId())->where('payroll_run_id',$runId)->selectRaw('COALESCE(SUM(gross_amount),0) gross, COALESCE(SUM(deduction_amount),0) deduction, COALESCE(SUM(net_amount),0) net')->first();
        DB::table('hm_staff_payroll_runs')->where('id',$runId)->where('business_id',$this->businessId())->update(['gross_amount'=>$totals->gross ?? 0, 'deduction_amount'=>$totals->deduction ?? 0, 'net_amount'=>$totals->net ?? 0, 'updated_at'=>now()]);
    }

    private function rows(string $table, int $limit = 100): array
    {
        if (!Schema::hasTable($table)) return [];
        try { return DB::table($table)->where('business_id',$this->businessId())->orderByDesc('id')->limit($limit)->get()->all(); }
        catch (Throwable $e) { return []; }
    }

    private function nextNumber(string $table, string $column, string $prefix): string
    { $last = Schema::hasTable($table) ? DB::table($table)->where('business_id',$this->businessId())->orderByDesc('id')->value($column) : null; $n=1; if ($last && preg_match('/(\d+)$/',$last,$m)) $n=((int)$m[1])+1; return $prefix.'-'.date('ym').'-'.str_pad((string)$n,5,'0',STR_PAD_LEFT); }
    private function businessId(): int { return (int)(session('business.id') ?? session('business_id') ?? 1); }
    private function locationId(): ?int { return session('business_location_id') ?? session('location_id') ?? null; }
}
