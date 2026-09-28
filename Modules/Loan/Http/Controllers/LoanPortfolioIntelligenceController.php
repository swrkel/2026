<?php

namespace Modules\Loan\Http\Controllers;

use App\Http\Controllers\Controller;

use Illuminate\Support\Facades\DB;

use Modules\Loan\Models\Loan;

class LoanPortfolioIntelligenceController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Portfolio Intelligence Dashboard
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $business_id = session('business.id');

        /*
        |--------------------------------------------------------------------------
        | BASE QUERY
        |--------------------------------------------------------------------------
        */

        $loan_query = Loan::where(
            'business_id',
            $business_id
        );

        /*
        |--------------------------------------------------------------------------
        | EXECUTIVE KPI SUMMARY
        |--------------------------------------------------------------------------
        */

        $total_loans =
            (clone $loan_query)->count();

        $active_loans =
            (clone $loan_query)
                ->where('status', 'active')
                ->count();

        $total_portfolio =
            (clone $loan_query)
                ->sum('outstanding_amount');

        $total_overdue =
            (clone $loan_query)
                ->sum('overdue_amount');

        $total_principal_outstanding =
            (clone $loan_query)
                ->sum('principal_outstanding');

        $total_interest_outstanding =
            (clone $loan_query)
                ->sum('interest_outstanding');

        /*
        |--------------------------------------------------------------------------
        | PAR ANALYTICS
        |--------------------------------------------------------------------------
        */

        $par_1_30 =
            (clone $loan_query)
                ->whereBetween(
                    'overdue_days',
                    [1, 30]
                )
                ->count();

        $par_31_60 =
            (clone $loan_query)
                ->whereBetween(
                    'overdue_days',
                    [31, 60]
                )
                ->count();

        $par_61_90 =
            (clone $loan_query)
                ->whereBetween(
                    'overdue_days',
                    [61, 90]
                )
                ->count();

        $par_90_plus =
            (clone $loan_query)
                ->where(
                    'overdue_days',
                    '>',
                    90
                )
                ->count();

        /*
        |--------------------------------------------------------------------------
        | HIGH RISK ACCOUNTS
        |--------------------------------------------------------------------------
        */

        $high_risk_loans =
            (clone $loan_query)
                ->where(
                    'overdue_days',
                    '>',
                    90
                )
                ->latest()
                ->take(10)
                ->get();

        /*
        |--------------------------------------------------------------------------
        | COLLECTION STATUS
        |--------------------------------------------------------------------------
        */

        $collection_summary =
            (clone $loan_query)
                ->select(
                    'collection_status',
                    DB::raw('COUNT(*) as total')
                )
                ->groupBy('collection_status')
                ->get();

        /*
        |--------------------------------------------------------------------------
        | RECENT OVERDUE LOANS
        |--------------------------------------------------------------------------
        */

        $recent_overdues =
            (clone $loan_query)
                ->where(
                    'overdue_amount',
                    '>',
                    0
                )
                ->latest()
                ->take(10)
                ->get();

        /*
        |--------------------------------------------------------------------------
        | PORTFOLIO HEALTH SCORE
        |--------------------------------------------------------------------------
        */

        $portfolio_health_score = 85;

        if ($total_overdue > 1000000) {

            $portfolio_health_score = 60;

        } elseif ($total_overdue > 500000) {

            $portfolio_health_score = 70;
        }

        /*
        |--------------------------------------------------------------------------
        | RETURN VIEW
        |--------------------------------------------------------------------------
        */

        return view(
            'loan::portfolio_intelligence.index',
            compact(
                'total_loans',
                'active_loans',
                'total_portfolio',
                'total_overdue',
                'total_principal_outstanding',
                'total_interest_outstanding',
                'par_1_30',
                'par_31_60',
                'par_61_90',
                'par_90_plus',
                'high_risk_loans',
                'collection_summary',
                'recent_overdues',
                'portfolio_health_score'
            )
        );
    }
}