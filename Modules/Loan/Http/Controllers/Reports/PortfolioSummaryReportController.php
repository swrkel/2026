<?php

namespace Modules\Loan\Http\Controllers\Reports;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Loan\Models\Loan;
use Modules\Loan\Services\LoanOverdueService;

class PortfolioSummaryReportController extends Controller
{
    /**
     * Display portfolio summary report.
     */
    public function index(Request $request)
    {
        $total_loans = Loan::count();

        $active_loans = Loan::where('status', 'active')->count();

        $closed_loans = Loan::where('status', 'closed')->count();

$total_disbursed =
    Loan::sum('principal_amount');

$principal_outstanding =
    Loan::sum('principal_amount')
    - Loan::sum('principal_paid');

$total_overdue =
    \Modules\Loan\Models\LoanRepaymentSchedule::sum(
        'overdue_amount'
    );
   
   /*
|--------------------------------------------------------------------------
| PAR Aging Buckets
|--------------------------------------------------------------------------
*/

$par_1_30 =
    \Modules\Loan\Models\LoanRepaymentSchedule::whereBetween(
        'days_overdue',
        [1, 30]
    )->sum('overdue_amount');

$par_31_60 =
    \Modules\Loan\Models\LoanRepaymentSchedule::whereBetween(
        'days_overdue',
        [31, 60]
    )->sum('overdue_amount');

$par_61_90 =
    \Modules\Loan\Models\LoanRepaymentSchedule::whereBetween(
        'days_overdue',
        [61, 90]
    )->sum('overdue_amount');

$par_90_plus =
    \Modules\Loan\Models\LoanRepaymentSchedule::where(
        'days_overdue',
        '>',
        90
    )->sum('overdue_amount');
    

return view('loan::reports.portfolio_summary', compact(
    
    'par_1_30',
    'par_31_60',
    'par_61_90',
    'par_90_plus',

    'total_loans',
    'active_loans',
    'closed_loans',
    'total_disbursed',
    'principal_outstanding',
    'total_overdue'
    
    
));


    }
}