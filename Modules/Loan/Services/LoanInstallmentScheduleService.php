<?php

namespace Modules\Loan\Services;

use Carbon\Carbon;

class LoanInstallmentScheduleService
{
    public function generate(float $principal, float $annualRate, int $term, string $frequency = 'monthly', ?string $startDate = null): array
    {
        $term = max(1, $term);
        $principal = max(0, $principal);
        $annualRate = max(0, $annualRate);
        $start = $startDate ? Carbon::parse($startDate) : Carbon::today();
        $periodsPerYear = $this->periodsPerYear($frequency);
        $periodicRate = $annualRate > 0 ? ($annualRate / 100) / $periodsPerYear : 0;
        $principalPerInstallment = $principal / $term;
        $balance = $principal;
        $rows = [];

        for ($i = 1; $i <= $term; $i++) {
            $interest = round($balance * $periodicRate, 2);
            $principalComponent = round($i === $term ? $balance : $principalPerInstallment, 2);
            $amount = round($principalComponent + $interest, 2);
            $balance = round(max(0, $balance - $principalComponent), 2);

            $rows[] = [
                'installment_no' => $i,
                'due_date' => $this->nextDueDate($start, $frequency, $i)->toDateString(),
                'principal' => $principalComponent,
                'interest' => $interest,
                'amount' => $amount,
                'balance' => $balance,
            ];
        }

        return $rows;
    }

    protected function periodsPerYear(string $frequency): int
    {
        return [
            'daily' => 365,
            'weekly' => 52,
            'fortnightly' => 26,
            'monthly' => 12,
            'quarterly' => 4,
            'half_yearly' => 2,
            'yearly' => 1,
        ][$frequency] ?? 12;
    }

    protected function nextDueDate(Carbon $start, string $frequency, int $installmentNo): Carbon
    {
        $date = $start->copy();

        switch ($frequency) {
            case 'daily': return $date->addDays($installmentNo);
            case 'weekly': return $date->addWeeks($installmentNo);
            case 'fortnightly': return $date->addWeeks($installmentNo * 2);
            case 'quarterly': return $date->addMonths($installmentNo * 3);
            case 'half_yearly': return $date->addMonths($installmentNo * 6);
            case 'yearly': return $date->addYears($installmentNo);
            default: return $date->addMonths($installmentNo);
        }
    }
}
