<?php

namespace Modules\Loan\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;

class LoanRepaymentManagementController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Repayment Management Workspace
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        return view(
            'loan::loan_repayments.index'
        );
    }
}