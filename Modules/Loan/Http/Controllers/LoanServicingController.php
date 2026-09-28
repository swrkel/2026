<?php

namespace Modules\Loan\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;

class LoanServicingController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Loan Servicing Workspace
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        return view(
            'loan::servicing.index'
        );
    }
}