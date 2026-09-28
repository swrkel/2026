<?php

namespace Modules\BankingMicrofinance\Services;

use Illuminate\Support\Facades\DB;
use Modules\BankingMicrofinance\Entities\ProvisioningRule;
use Modules\BankingMicrofinance\Entities\ProvisioningRun;
use Modules\BankingMicrofinance\Entities\ProvisioningRunLine;

class ProvisioningService
{
    public function ruleForDays(int $days): ?ProvisioningRule
    {
        return ProvisioningRule::where('is_active', true)
            ->where('days_past_due_from', '<=', $days)
            ->where(function($q) use ($days){ $q->whereNull('days_past_due_to')->orWhere('days_past_due_to','>=',$days); })
            ->orderByDesc('days_past_due_from')->first();
    }

    public function generate(array $loans, string $runDate): ProvisioningRun
    {
        return DB::transaction(function () use ($loans, $runDate) {
            $run = ProvisioningRun::create(['run_date'=>$runDate,'status'=>'draft','created_by'=>auth()->id()]);
            $totalOutstanding = 0; $totalProvision = 0;
            foreach ($loans as $loan) {
                $days = (int)($loan['days_past_due'] ?? 0);
                $outstanding = (float)($loan['outstanding_amount'] ?? 0);
                $rule = $this->ruleForDays($days);
                $pct = $rule ? (float)$rule->percentage : 0;
                $prov = round($outstanding * $pct / 100, 4);
                ProvisioningRunLine::create(['run_id'=>$run->id,'loan_id'=>$loan['loan_id'],'days_past_due'=>$days,'outstanding_amount'=>$outstanding,'provision_percentage'=>$pct,'provision_amount'=>$prov]);
                $totalOutstanding += $outstanding; $totalProvision += $prov;
            }
            $run->update(['total_outstanding'=>$totalOutstanding,'total_provision'=>$totalProvision]);
            return $run;
        });
    }
}
