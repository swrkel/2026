<?php

namespace Modules\Loan\Services;

class LoanEarlySettlementService
{
    protected LoanRepaymentPostingService $postingService;

    public function __construct(LoanRepaymentPostingService $postingService)
    {
        $this->postingService = $postingService;
    }

    public function quote(int $loanId, float $penalty = 0, float $rebate = 0): array
    {
        $balance = $this->postingService->outstandingBalance($loanId);
        $settlementAmount = max(0, $balance['balance'] + $penalty - $rebate);

        return [
            'loan_id' => $loanId,
            'principal_balance' => $balance['balance'],
            'penalty' => round(max(0, $penalty), 2),
            'rebate' => round(max(0, $rebate), 2),
            'settlement_amount' => round($settlementAmount, 2),
        ];
    }
}
