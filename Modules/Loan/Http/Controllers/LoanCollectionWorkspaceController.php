<?php

namespace Modules\Loan\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;

class LoanCollectionWorkspaceController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Collections Workspace
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        return view(
            'loan::collections.dashboard'
        );
    }
}