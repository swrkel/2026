<?php

namespace Modules\Loan\Http\Controllers\Borrower;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;

class BorrowerSettlementController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Settlement Requests List
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        return view(
            'loan::borrower.settlements.index'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Submit Settlement Request
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        return redirect()
            ->back()
            ->with(
                'status',
                'Settlement request submitted successfully.'
            );
    }
}