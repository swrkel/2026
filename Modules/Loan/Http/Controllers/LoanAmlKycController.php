<?php

namespace Modules\Loan\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use Modules\Loan\Models\LoanAmlKycProfile;

class LoanAmlKycController extends Controller
{

    /*
    |--------------------------------------------------------------------------
    | AML / KYC Dashboard
    |--------------------------------------------------------------------------
    */

    public function index()
    {

        $business_id = request()->session()->get('user.business_id');

        $profiles = LoanAmlKycProfile::where(
                'business_id',
                $business_id
            )
            ->with('borrower')
            ->latest()
            ->get();

        return view(
            'loan::aml_kyc.index',
            compact('profiles')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Create AML/KYC Screen
    |--------------------------------------------------------------------------
    */

    public function create()
    {

        $business_id = request()->session()->get('user.business_id');

        $borrowers = DB::table('contacts')
            ->where('business_id', $business_id)
            ->where('type', 'customer')
            ->orderBy('name')
            ->get();

        return view(
            'loan::aml_kyc.create',
            compact('borrowers')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Store AML/KYC Profile
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {

        $business_id = request()->session()->get('user.business_id');

        LoanAmlKycProfile::create([

            'business_id' => $business_id,

            'borrower_id' => $request->borrower_id,

            'kyc_status' => $request->kyc_status,

            'aml_status' => $request->aml_status,

            'risk_rating' => $request->risk_rating,

            'customer_type' => $request->customer_type,

            'pep_flag' => $request->pep_flag ?? 0,

            'sanctions_flag' => $request->sanctions_flag ?? 0,

            'adverse_media_flag' => $request->adverse_media_flag ?? 0,

            'source_of_funds' => $request->source_of_funds,

            'source_of_wealth' => $request->source_of_wealth,

            'occupation' => $request->occupation,

            'annual_income' => $request->annual_income,

            'next_review_date' => $request->next_review_date,

            'compliance_notes' => $request->compliance_notes,

            'created_by' => Auth::id(),

            'updated_by' => Auth::id()

        ]);

        return redirect()
            ->route('loan.aml_kyc.index')
            ->with(
                'success',
                'AML / KYC Profile Created Successfully'
            );
    }

}