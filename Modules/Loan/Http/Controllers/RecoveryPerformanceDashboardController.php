<?php

namespace Modules\Loan\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class RecoveryPerformanceDashboardController extends Controller
{
    public function index(Request $request)
    {
        $business_id = session('business.id');

        /*
        |--------------------------------------------------------------------------
        | Core Loan Portfolio
        |--------------------------------------------------------------------------
        */

        $total_loans = DB::table('loans')
            ->where('business_id', $business_id)
            ->count();

        $active_loans = DB::table('loans')
            ->where('business_id', $business_id)
            ->whereIn('status', [
                'active',
                'disbursed',
                'ongoing'
            ])
            ->count();

$overdue_loans = DB::table('loans')
    ->where('business_id', $business_id)
    ->where(function ($query) {

        $query->where('status', 'overdue')
              ->orWhere('overdue_days', '>', 0)
              ->orWhere('overdue_amount', '>', 0);

    })
    ->count();
    
        $written_off_loans = DB::table('loans')
            ->where('business_id', $business_id)
            ->where('status', 'written_off')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Financial Exposure
        |--------------------------------------------------------------------------
        */

        $portfolio_value = DB::table('loans')
            ->where('business_id', $business_id)
            ->sum('principal_amount');

        $outstanding_value = DB::table('loans')
            ->where('business_id', $business_id)
            ->sum('outstanding_amount');

        $overdue_value = DB::table('loans')
            ->where('business_id', $business_id)
            ->sum('overdue_amount');

        /*
        |--------------------------------------------------------------------------
        | Collections
        |--------------------------------------------------------------------------
        */

        $total_collections = 0;

        if (DB::getSchemaBuilder()->hasTable('loan_collections')) {
            $total_collections = DB::table('loan_collections')
                ->where('business_id', $business_id)
                ->sum('amount');
        }

        /*
        |--------------------------------------------------------------------------
        | Recovery Escalations
        |--------------------------------------------------------------------------
        */

        $open_escalations = 0;

        if (DB::getSchemaBuilder()->hasTable('loan_recovery_escalations')) {
            $open_escalations = DB::table('loan_recovery_escalations')
                ->where('business_id', $business_id)
                ->whereIn('status', [
                    'open',
                    'pending',
                    'in_progress'
                ])
                ->count();
        }

        /*
        |--------------------------------------------------------------------------
        | PAR / Risk Ratios
        |--------------------------------------------------------------------------
        */

        $par_ratio = 0;

        if ($outstanding_value > 0) {
            $par_ratio = ($overdue_value / $outstanding_value) * 100;
        }

        $collection_efficiency = 0;

        if ($portfolio_value > 0) {
            $collection_efficiency = ($total_collections / $portfolio_value) * 100;
        }

        return view('loan::recovery.performance_dashboard')
            ->with(compact(
                'total_loans',
                'active_loans',
                'overdue_loans',
                'written_off_loans',
                'portfolio_value',
                'outstanding_value',
                'overdue_value',
                'total_collections',
                'open_escalations',
                'par_ratio',
                'collection_efficiency'
            ));
    }
}