<?php

namespace Modules\Loan\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

use Modules\Loan\Models\Loan;
use Modules\Loan\Models\LoanRecovery;
use Modules\Loan\Models\LoanRepayment;
use Modules\Loan\Models\LoanCollection;
use Modules\Loan\Models\LoanWriteOff;
use Modules\Loan\Models\LoanAuditLog;
use Modules\Loan\Models\LoanMISReport;
use Modules\Loan\Models\LoanRecoveryEscalation;

class LoanMISController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Executive MIS Dashboard
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $business_id = request()->session()
            ->get('user.business_id');

        /*
        |--------------------------------------------------------------------------
        | Portfolio KPIs
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
        | Financial Intelligence
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
        | PAR & NPL Analytics
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

        $npl_ratio = 0;

        if ($outstanding_portfolio > 0) {

            $npl_ratio =
                round(
                    (
                        $par_90 /
                        $outstanding_portfolio
                    ) * 100,
                    2
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Recovery Efficiency
        |--------------------------------------------------------------------------
        */

        $recovery_rate = 0;

        if ($outstanding_portfolio > 0) {

            $recovery_rate =
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
        | Escalation Analytics
        |--------------------------------------------------------------------------
        */

        $open_escalations =
            LoanRecoveryEscalation::where(
                'business_id',
                $business_id
            )
            ->where(
                'status',
                'open'
            )
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Collections Intelligence
        |--------------------------------------------------------------------------
        */

        $collections_count =
            LoanCollection::where(
                'business_id',
                $business_id
            )->count();

        $repayment_count =
            LoanRepayment::where(
                'business_id',
                $business_id
            )->count();

        /*
        |--------------------------------------------------------------------------
        | Executive MIS Snapshot
        |--------------------------------------------------------------------------
        */

        LoanMISReport::create([

            'business_id' =>
                $business_id,

            'report_date' =>
                now()->toDateString(),

            'portfolio_value' =>
                $total_portfolio,

            'outstanding_value' =>
                $outstanding_portfolio,

            'recovery_value' =>
                $total_recoveries,

            'write_off_losses' =>
                $write_off_losses,

            'par_30' =>
                $par_30,

            'par_90' =>
                $par_90,

            'npl_ratio' =>
                $npl_ratio,

            'recovery_rate' =>
                $recovery_rate,

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
                'executive_mis_generated',

            'description' =>
                'Executive MIS dashboard generated.',

            'performed_by' =>
                request()->session()
                    ->get('user.id')
        ]);

        return view(
            'loan::loan_mis.index',
            compact(

                'total_loans',
                'active_loans',
                'defaulted_loans',

                'total_portfolio',
                'outstanding_portfolio',

                'total_recoveries',
                'write_off_losses',

                'par_30',
                'par_90',

                'npl_ratio',
                'recovery_rate',

                'open_escalations',

                'collections_count',
                'repayment_count'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Branch Performance Analytics
    |--------------------------------------------------------------------------
    */

    public function branchPerformance()
    {
        $business_id = request()->session()
            ->get('user.business_id');

        /*
        |--------------------------------------------------------------------------
        | Branch Aggregation
        |--------------------------------------------------------------------------
        */

        $branch_performance =
            Loan::where(
                    'business_id',
                    $business_id
                )
                ->selectRaw('
                    branch_id,
                    COUNT(*) as total_loans,
                    SUM(principal_amount) as portfolio_value,
                    SUM(principal_outstanding) as outstanding_value
                ')
                ->groupBy('branch_id')
                ->get();

        return view(
            'loan::loan_mis.branch_performance',
            compact('branch_performance')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Recovery Officer Analytics
    |--------------------------------------------------------------------------
    */

    public function officerPerformance()
    {
        $business_id = request()->session()
            ->get('user.business_id');

        /*
        |--------------------------------------------------------------------------
        | Officer Productivity
        |--------------------------------------------------------------------------
        */

        $officer_performance =
            LoanRecovery::where(
                    'business_id',
                    $business_id
                )
                ->selectRaw('
                    created_by,
                    COUNT(*) as recoveries,
                    SUM(recovered_amount) as total_recovery
                ')
                ->groupBy('created_by')
                ->get();

        return view(
            'loan::loan_mis.officer_performance',
            compact('officer_performance')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Regulatory Reporting
    |--------------------------------------------------------------------------
    */

    public function regulatoryReport()
    {
        $business_id = request()->session()
            ->get('user.business_id');

        /*
        |--------------------------------------------------------------------------
        | Regulatory Metrics
        |--------------------------------------------------------------------------
        */

        $total_portfolio =
            Loan::where(
                'business_id',
                $business_id
            )
            ->sum('principal_amount');

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

        $write_off_amount =
            LoanWriteOff::where(
                'business_id',
                $business_id
            )
            ->sum('write_off_amount');

        return view(
            'loan::loan_mis.regulatory_report',
            compact(
                'total_portfolio',
                'par_90',
                'write_off_amount'
            )
        );
    }
}