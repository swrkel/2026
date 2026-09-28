<?php

namespace Modules\Loan\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Loan\Services\LoanRepaymentPostingService;
use Modules\Loan\Services\LoanEarlySettlementService;

class LoanStatementController extends Controller
{
    protected LoanRepaymentPostingService $postingService;
    protected LoanEarlySettlementService $settlementService;

    public function __construct(LoanRepaymentPostingService $postingService, LoanEarlySettlementService $settlementService)
    {
        $this->postingService = $postingService;
        $this->settlementService = $settlementService;
    }

    public function show($id)
    {
        $loan = Schema::hasTable('loans') ? DB::table('loans')->where('id', $id)->first() : null;
        abort_if(!$loan, 404);

        $balance = $this->postingService->outstandingBalance((int) $id);
        $settlement = $this->settlementService->quote((int) $id);
        $transactions = $this->transactions((int) $id);

        return view('loan::statements.show', compact('loan', 'balance', 'settlement', 'transactions'));
    }

    protected function transactions(int $loanId)
    {
        if (!Schema::hasTable('loan_repayments')) {
            return collect();
        }

        $query = DB::table('loan_repayments');
        if (Schema::hasColumn('loan_repayments', 'loan_id')) {
            $query->where('loan_id', $loanId);
        }

        return $query->orderByDesc(Schema::hasColumn('loan_repayments', 'id') ? 'id' : 'created_at')
            ->limit(100)
            ->get();
    }
}
