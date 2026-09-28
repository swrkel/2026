<?php

namespace Modules\Loan\Http\Controllers;

use App\Http\Controllers\Controller;

use Modules\Loan\Models\Loan;

class LoanParAnalyticsController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | PAR Analytics Dashboard
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $business_id = session('business.id');

        $loan_query = Loan::where(
            'business_id',
            $business_id
        );

        $total_loans =
            (clone $loan_query)->count();

        $total_portfolio =
            (clone $loan_query)->sum('outstanding_amount');

        $total_overdue =
            (clone $loan_query)->sum('overdue_amount');

        $par_1_30 =
            (clone $loan_query)
                ->whereBetween('overdue_days', [1, 30])
                ->count();

        $par_31_60 =
            (clone $loan_query)
                ->whereBetween('overdue_days', [31, 60])
                ->count();

        $par_61_90 =
            (clone $loan_query)
                ->whereBetween('overdue_days', [61, 90])
                ->count();

        $par_90_plus =
            (clone $loan_query)
                ->where('overdue_days', '>', 90)
                ->count();

        $par_amount_1_30 =
            (clone $loan_query)
                ->whereBetween('overdue_days', [1, 30])
                ->sum('overdue_amount');

        $par_amount_31_60 =
            (clone $loan_query)
                ->whereBetween('overdue_days', [31, 60])
                ->sum('overdue_amount');

        $par_amount_61_90 =
            (clone $loan_query)
                ->whereBetween('overdue_days', [61, 90])
                ->sum('overdue_amount');

        $par_amount_90_plus =
            (clone $loan_query)
                ->where('overdue_days', '>', 90)
                ->sum('overdue_amount');

        $critical_accounts =
            (clone $loan_query)
                ->where('overdue_days', '>', 90)
                ->orderByDesc('overdue_amount')
                ->take(15)
                ->get();

        return view(
            'loan::par_analytics.index',
            compact(
                'total_loans',
                'total_portfolio',
                'total_overdue',
                'par_1_30',
                'par_31_60',
                'par_61_90',
                'par_90_plus',
                'par_amount_1_30',
                'par_amount_31_60',
                'par_amount_61_90',
                'par_amount_90_plus',
                'critical_accounts'
            )
        );
    }
}