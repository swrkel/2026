<?php

namespace Modules\Loan\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

use Modules\Loan\Models\Loan;
use Modules\Loan\Models\LoanRecoverySettlement;

class LoanRecoverySettlementController extends Controller
{
    /**
     * Store settlement.
     */
    public function store(Request $request)
    {
        try {

            $business_id = $request->session()
                ->get('user.business_id');

            $user_id = $request->session()
                ->get('user.id');

            /*
            |--------------------------------------------------------------------------
            | Validate loan
            |--------------------------------------------------------------------------
            */

            $loan = Loan::where(
                    'business_id',
                    $business_id
                )
                ->findOrFail(
                    $request->loan_id
                );

            /*
            |--------------------------------------------------------------------------
            | Create settlement
            |--------------------------------------------------------------------------
            */

            LoanRecoverySettlement::create([

                'business_id' =>
                    $business_id,

                'loan_id' =>
                    $loan->id,

                'customer_id' =>
                    $loan->customer_id,

                'settlement_amount' =>
                    $request->settlement_amount,

                'original_outstanding' =>
                    $request->original_outstanding,

                'discount_amount' =>
                    $request->discount_amount,

                'settlement_status' =>
                    'pending',

                'notes' =>
                    $request->notes,

                'created_by' =>
                    $user_id
            ]);

            /*
            |--------------------------------------------------------------------------
            | Audit log
            |--------------------------------------------------------------------------
            */

            \Modules\Loan\Models\LoanAuditLog::create([

                'business_id' =>
                    $business_id,

                'loan_id' =>
                    $loan->id,

                'action_type' =>
                    'recovery_settlement_created',

                'description' =>
                    'Recovery settlement created',

                'performed_by' =>
                    $user_id
            ]);

            return redirect()
                ->back()
                ->with(
                    'success',
                    'Settlement created successfully'
                );

        } catch (\Exception $e) {

            \Log::error($e);

            return back()->withErrors(
                $e->getMessage()
            );
        }
    }
}