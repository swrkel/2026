<?php

namespace Modules\Loan\Http\Controllers\Borrower;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;

class BorrowerRestructuringController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Restructuring Requests List
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        return view(
            'loan::borrower.restructuring.index'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Submit Restructuring Request
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        return redirect()
            ->back()
            ->with(
                'status',
                'Restructuring request submitted successfully.'
            );
    }
}