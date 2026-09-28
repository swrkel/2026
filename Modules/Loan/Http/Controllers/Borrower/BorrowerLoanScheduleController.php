<?php

namespace Modules\Loan\Http\Controllers\Borrower;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;

use App\Contact;

use Modules\Loan\Models\LoanApplication;

use Modules\Loan\Models\LoanSchedule;

class BorrowerLoanScheduleController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Borrower Loan Schedule
    |--------------------------------------------------------------------------
    */

    public function index($id)
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
        | SECURE LOAN ACCESS
        |--------------------------------------------------------------------------
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

        /*
        |--------------------------------------------------------------------------
        | Get ONLY Borrower's Schedule
        |--------------------------------------------------------------------------
        */

        $schedules = LoanSchedule::where(
            'loan_application_id',
            $loan->id
        )
        ->orderBy('installment_number')
        ->get();

        return view(
            'loan::borrower.schedules.index',
            compact(
                'loan',
                'schedules'
            )
        );
    }
}