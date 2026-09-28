<?php

namespace Modules\Loan\Http\Controllers\Borrower;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;

use App\Contact;

use Modules\Loan\Models\LoanApplication;

class BorrowerLoanController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Borrower Loan List
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $user = Auth::user();

        /*
        |--------------------------------------------------------------------------
        | Get Borrower Contact
        |--------------------------------------------------------------------------
        */

        $contact = Contact::where(
            'user_id',
            $user->id
        )
        ->whereIn('type', ['customer', 'both'])
        ->first();

        /*
        |--------------------------------------------------------------------------
        | No Borrower Contact
        |--------------------------------------------------------------------------
        */

        if (!$contact) {

            abort(
                403,
                'Borrower profile not found.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Get ONLY Borrower's Loans
        |--------------------------------------------------------------------------
        */

        $loans = LoanApplication::where(
            'customer_id',
            $contact->id
        )
        ->latest()
        ->paginate(20);

        return view(
            'loan::borrower.loans.index',
            compact('loans')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Borrower Loan Details
    |--------------------------------------------------------------------------
    */

    public function show($id)
    {
        $user = Auth::user();

        /*
        |--------------------------------------------------------------------------
        | Get Borrower Contact
        |--------------------------------------------------------------------------
        */

        $contact = Contact::where(
            'user_id',
            $user->id
        )
        ->whereIn('type', ['customer', 'both'])
        ->first();

        /*
        |--------------------------------------------------------------------------
        | No Borrower Contact
        |--------------------------------------------------------------------------
        */

        if (!$contact) {

            abort(
                403,
                'Borrower profile not found.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | SECURE LOAN ISOLATION
        |--------------------------------------------------------------------------
        |
        | Borrower can ONLY access own loans.
        |
        */

        $loan = LoanApplication::where(
            'id',
            $id
        )
        ->where(
            'customer_id',
            $contact->id
        )
        ->first();

        /*
        |--------------------------------------------------------------------------
        | Unauthorized Access Protection
        |--------------------------------------------------------------------------
        */

        if (!$loan) {

            abort(
                403,
                'Unauthorized loan access.'
            );
        }

        return view(
            'loan::borrower.loans.show',
            compact('loan')
        );
    }
}