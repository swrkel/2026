<?php

namespace Modules\Loan\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

use Modules\Loan\Models\Loan;
use Modules\Loan\Models\LoanRepayment;
use Modules\Loan\Models\LoanRecovery;
use Modules\Loan\Models\LoanCollection;
use Modules\Loan\Models\LoanAuditLog;
use Modules\Loan\Models\LoanRiskAnalytics;
use Modules\Loan\Models\LoanWriteOff;

class LoanRiskAnalyticsController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Enterprise Risk Analytics Dashboard
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $business_id = request()->session()
            ->get('user.business_id');

        /*
        |--------------------------------------------------------------------------
        | Portfolio Overview
        |--------------------------------------------------------------------------
        */

        $total_loans =
            Loan::where(
                'business_id',
                $business_id
            )->count();

        $active_loans =
            Loan::where(
                'business_id',
                $business_id
            )
            ->where(
                'status',
                'active'
            )
            ->count();

        $defaulted_loans =
            Loan::where(
                'business_id',
                $business_id
            )
            ->whereIn(
                'status',
                [
                    'defaulted',
                    'written_off'
                ]
            )
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Portfolio Exposure
        |--------------------------------------------------------------------------
        */

        $total_portfolio =
            Loan::where(
                'business_id',
                $business_id
            )
            ->sum('principal_amount');

        $outstanding_portfolio =
            Loan::where(
                'business_id',
                $business_id
            )
            ->sum('principal_outstanding');

        /*
        |--------------------------------------------------------------------------
        | PAR Analytics
        |--------------------------------------------------------------------------
        */

        $par_30 =
            Loan::where(
                'business_id',
                $business_id
            )
            ->where(
                'days_past_due',
                '>=',
                30
            )
            ->sum('principal_outstanding');

        $par_60 =
            Loan::where(
                'business_id',
                $business_id
            )
            ->where(
                'days_past_due',
                '>=',
                60
            )
            ->sum('principal_outstanding');

        $par_90 =
            Loan::where(
                'business_id',
                $business_id
            )
            ->where(
                'days_past_due',
                '>=',
                90
            )
            ->sum('principal_outstanding');

        /*
        |--------------------------------------------------------------------------
        | NPL Ratio
        |--------------------------------------------------------------------------
        */

        $npl_amount =
            Loan::where(
                'business_id',
                $business_id
            )
            ->whereIn(
                'status',
                [
                    'defaulted',
                    'written_off'
                ]
            )
            ->sum('principal_outstanding');

        $npl_ratio = 0;

        if ($outstanding_portfolio > 0) {

            $npl_ratio =
                round(
                    (
                        $npl_amount /
                        $outstanding_portfolio
                    ) * 100,
                    2
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Recovery Performance
        |--------------------------------------------------------------------------
        */

        $total_recoveries =
            LoanRecovery::where(
                'business_id',
                $business_id
            )
            ->sum('recovered_amount');

        $write_off_losses =
            LoanWriteOff::where(
                'business_id',
                $business_id
            )
            ->sum('loss_amount');

        /*
        |--------------------------------------------------------------------------
        | Risk Segmentation
        |--------------------------------------------------------------------------
        */

        $high_risk =
            Loan::where(
                'business_id',
                $business_id
            )
            ->where(
                'risk_level',
                'high'
            )
            ->count();

        $medium_risk =
            Loan::where(
                'business_id',
                $business_id
            )
            ->where(
                'risk_level',
                'medium'
            )
            ->count();

        $low_risk =
            Loan::where(
                'business_id',
                $business_id
            )
            ->where(
                'risk_level',
                'low'
            )
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Delinquency Trends
        |--------------------------------------------------------------------------
        */

        $delinquent_loans =
            Loan::where(
                'business_id',
                $business_id
            )
            ->where(
                'days_past_due',
                '>',
                0
            )
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Recovery Forecasting
        |--------------------------------------------------------------------------
        */

        $forecasted_recovery_rate = 0;

        if ($outstanding_portfolio > 0) {

            $forecasted_recovery_rate =
                round(
                    (
                        $total_recoveries /
                        $outstanding_portfolio
                    ) * 100,
                    2
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Analytics Snapshot
        |--------------------------------------------------------------------------
        */

        LoanRiskAnalytics::create([

            'business_id' =>
                $business_id,

            'portfolio_value' =>
                $total_portfolio,

            'outstanding_value' =>
                $outstanding_portfolio,

            'par_30' =>
                $par_30,

            'par_60' =>
                $par_60,

            'par_90' =>
                $par_90,

            'npl_ratio' =>
                $npl_ratio,

            'forecasted_recovery_rate' =>
                $forecasted_recovery_rate,

            'created_by' =>
                request()->session()
                    ->get('user.id')
        ]);

        /*
        |--------------------------------------------------------------------------
        | Audit Trail
        |--------------------------------------------------------------------------
        */

        LoanAuditLog::create([

            'business_id' =>
                $business_id,

            'action_type' =>
                'risk_analytics_generated',

            'description' =>
                'Enterprise portfolio risk analytics generated.',

            'performed_by' =>
                request()->session()
                    ->get('user.id')
        ]);

        return view(
            'loan::loan_risk_analytics.index',
            compact(

                'total_loans',
                'active_loans',
                'defaulted_loans',

                'total_portfolio',
                'outstanding_portfolio',

                'par_30',
                'par_60',
                'par_90',

                'npl_ratio',

                'total_recoveries',
                'write_off_losses',

                'high_risk',
                'medium_risk',
                'low_risk',

                'delinquent_loans',

                'forecasted_recovery_rate'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Portfolio Segmentation Analytics
    |--------------------------------------------------------------------------
    */

    public function segmentation()
    {
        $business_id = request()->session()
            ->get('user.business_id');

        /*
        |--------------------------------------------------------------------------
        | Segment Analytics
        |--------------------------------------------------------------------------
        */

        $segments =
            Loan::where(
                    'business_id',
                    $business_id
                )
                ->selectRaw('
                    risk_level,
                    COUNT(*) as total_loans,
                    SUM(principal_outstanding) as exposure
                ')
                ->groupBy('risk_level')
                ->get();

        return view(
            'loan::loan_risk_analytics.segmentation',
            compact('segments')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Recovery Forecast Intelligence
    |--------------------------------------------------------------------------
    */

    public function forecasting()
    {
        $business_id = request()->session()
            ->get('user.business_id');

        /*
        |--------------------------------------------------------------------------
        | Recovery Metrics
        |--------------------------------------------------------------------------
        */

        $recoveries =
            LoanRecovery::where(
                'business_id',
                $business_id
            )
            ->latest()
            ->take(12)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Forecast Logic
        |--------------------------------------------------------------------------
        */

        $average_recovery = 0;

        if ($recoveries->count() > 0) {

            $average_recovery =
                round(
                    $recoveries->avg(
                        'recovered_amount'
                    ),
                    2
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Forecast Projection
        |--------------------------------------------------------------------------
        */

        $projected_recovery =
            $average_recovery * 12;

        return view(
            'loan::loan_risk_analytics.forecasting',
            compact(
                'recoveries',
                'average_recovery',
                'projected_recovery'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PAR Monitoring Dashboard
    |--------------------------------------------------------------------------
    */

    public function parMonitoring()
    {
        $business_id = request()->session()
            ->get('user.business_id');

        $par_accounts =
            Loan::where(
                    'business_id',
                    $business_id
                )
                ->where(
                    'days_past_due',
                    '>',
                    0
                )
                ->latest()
                ->paginate(25);

        return view(
            'loan::loan_risk_analytics.par_monitoring',
            compact('par_accounts')
        );
    }
}