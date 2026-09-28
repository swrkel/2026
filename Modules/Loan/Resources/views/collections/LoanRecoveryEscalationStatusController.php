<?php

namespace Modules\Loan\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

use Modules\Loan\Models\LoanRecoveryEscalation;

class LoanRecoveryEscalationStatusController extends Controller
{
    /**
     * Update escalation status.
     */
    public function update(Request $request, $id)
    {
        try {

            $business_id = $request->session()
                ->get('user.business_id');

            $user_id = $request->session()
                ->get('user.id');

            /*
            |--------------------------------------------------------------------------
            | Validate escalation
            |--------------------------------------------------------------------------
            */

            $escalation =
                LoanRecoveryEscalation::where(
                        'business_id',
                        $business_id
                    )
                    ->findOrFail($id);

            /*
            |--------------------------------------------------------------------------
            | Update escalation
            |--------------------------------------------------------------------------
            */

            $escalation->status =
                $request->status;

            /*
            |--------------------------------------------------------------------------
            | Resolution tracking
            |--------------------------------------------------------------------------
            */

            if (
                $request->status == 'resolved'
            ) {

                $escalation->resolution_date =
                    now()->toDateString();
            }

            $escalation->save();

            /*
            |--------------------------------------------------------------------------
            | Audit log
            |--------------------------------------------------------------------------
            */

            \Modules\Loan\Models\LoanAuditLog::create([

                'business_id' =>
                    $business_id,

                'loan_id' =>
                    $escalation->loan_id,

                'action_type' =>
                    'recovery_escalation_updated',

                'description' =>
                    'Recovery escalation status updated',

                'performed_by' =>
                    $user_id
            ]);

            return redirect()
                ->back()
                ->with(
                    'success',
                    'Escalation status updated successfully'
                );

        } catch (\Exception $e) {

            \Log::error($e);

            return back()->withErrors(
                $e->getMessage()
            );
        }
    }
}