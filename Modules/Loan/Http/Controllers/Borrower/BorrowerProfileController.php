<?php

namespace Modules\Loan\Http\Controllers\Borrower;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;

class BorrowerProfileController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Borrower Profile
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        return view(
            'loan::borrower.profile.index'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Update Borrower Profile
    |--------------------------------------------------------------------------
    */

    public function update(Request $request)
    {
        return redirect()
            ->back()
            ->with(
                'status',
                'Profile updated successfully.'
            );
    }
}