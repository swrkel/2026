<?php

namespace Modules\Loan\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

use Modules\Loan\Models\LoanRecoverySettlement;

class LoanRecoverySettlementRejectionController extends Controller
{
    /**
     * Reject settlement.
     */
    public function reject(Request $request, $id)
    {
        try {

            $business_id = $request->session()
                ->get('user.business_id');

            $user_id = $request->session()
                ->get('user.id');

            /*
            |--------------------------------------------------------------------------
            | Validate settlement
            |--------------------------------------------------------------------------
            */

            $settlement =
                LoanRecoverySettlement::where(
                        'business_id',
                        $business_id
                    )
                    ->findOrFail($id);

            /*
            |--------------------------------------------------------------------------
            | Reject settlement
            |--------------------------------------------------------------------------
            */

            $settlement->settlement_status =
                'rejected';

            $settlement->approved_by =
                $user_id;

            $settlement->settlement_date =
                now()->toDateString();

            $settlement->save();

            /*
            |--------------------------------------------------------------------------
            | Audit log
            |--------------------------------------------------------------------------
            */

            \Modules\Loan\Models\LoanAuditLog::create([

                'business_id' =>
                    $business_id,

                'loan_id' =>
                    $settlement->loan_id,

                'action_type' =>
                    'recovery_settlement_rejected',

                'description' =>
                    'Recovery settlement rejected',

                'performed_by' =>
                    $user_id
            ]);

            return redirect()
                ->back()
                ->with(
                    'success',
                    'Settlement rejected successfully'
                );

        } catch (\Exception $e) {

            \Log::error($e);

            return back()->withErrors(
                $e->getMessage()
            );
        }
    }
}