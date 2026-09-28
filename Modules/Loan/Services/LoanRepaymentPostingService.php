<?php

namespace Modules\Loan\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LoanRepaymentPostingService
{
    public function outstandingBalance(int $loanId): array
    {
        $principal = $this->loanAmount($loanId);
        $paid = $this->paidAmount($loanId);
        $balance = max(0, $principal - $paid);

        return [
            'loan_id' => $loanId,
            'principal' => $principal,
            'paid' => $paid,
            'balance' => $balance,
        ];
    }

    protected function loanAmount(int $loanId): float
    {
        if (!Schema::hasTable('loans')) {
            return 0.0;
        }

        $loan = DB::table('loans')->where('id', $loanId)->first();
        if (!$loan) {
            return 0.0;
        }

        foreach (['principal_amount', 'loan_amount', 'approved_amount', 'amount'] as $column) {
            if (Schema::hasColumn('loans', $column) && isset($loan->{$column})) {
                return (float) $loan->{$column};
            }
        }

        return 0.0;
    }

    protected function paidAmount(int $loanId): float
    {
        if (!Schema::hasTable('loan_repayments')) {
            return 0.0;
        }

        $query = DB::table('loan_repayments');
        if (Schema::hasColumn('loan_repayments', 'loan_id')) {
            $query->where('loan_id', $loanId);
        }

        foreach (['amount', 'paid_amount', 'total_amount'] as $column) {
            if (Schema::hasColumn('loan_repayments', $column)) {
                return (float) $query->sum($column);
            }
        }

        return 0.0;
    }
}
