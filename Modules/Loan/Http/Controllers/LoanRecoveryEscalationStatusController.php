<?php

namespace Modules\Loan\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;

class LoanRecoveryEscalationStatusController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Update Escalation Status
    |--------------------------------------------------------------------------
    */

    public function update(
        Request $request,
        $id
    ) {

        return response()->json([

            'success' => true,

            'message' =>
                'Recovery escalation status updated successfully.',

            'escalation_id' => $id
        ]);
    }
}