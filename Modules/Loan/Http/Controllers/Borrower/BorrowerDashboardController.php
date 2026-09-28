<?php

namespace Modules\Loan\Http\Controllers\Borrower;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;

class BorrowerDashboardController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Borrower Dashboard
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        return view(
            'loan::borrower.dashboard'
        );
    }
}