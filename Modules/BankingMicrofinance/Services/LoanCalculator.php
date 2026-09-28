<?php

namespace Modules\BankingMicrofinance\Services;

use Carbon\Carbon;

class LoanCalculator
{
    public function totals(float $principal, float $annualRate, int $weeks, string $method = 'flat'): array
    {
        $weeks = max($weeks, 1);
        $rate = $annualRate / 100;
        $interest = $method === 'declining'
            ? ($principal * $rate * ($weeks / 52) / 2)
            : ($principal * $rate * ($weeks / 52));
        $total = $principal + $interest;
        return [
            'interest_amount' => round($interest, 4),
            'total_payable' => round($total, 4),
            'installment_amount' => round($total / $weeks, 4),
        ];
    }

    public function schedule(float $principal, float $interest, int $weeks, ?string $startDate = null): array
    {
        $weeks = max($weeks, 1);
        $start = $startDate ? Carbon::parse($startDate) : now();
        $principalEach = round($principal / $weeks, 4);
        $interestEach = round($interest / $weeks, 4);
        $rows = [];
        for ($i = 1; $i <= $weeks; $i++) {
            $rows[] = [
                'installment_no' => $i,
                'due_date' => $start->copy()->addWeeks($i)->toDateString(),
                'principal_due' => $principalEach,
                'interest_due' => $interestEach,
                'fee_due' => 0,
                'total_due' => round($principalEach + $interestEach, 4),
                'status' => 'pending',
            ];
        }
        return $rows;
    }
}
