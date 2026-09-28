<?php

namespace Modules\Loan\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

use Modules\Loan\Models\LoanSanctionsWatchlist;
use Modules\Loan\Models\LoanAmlKycProfile;

class LoanSanctionsWatchlistController extends Controller
{

    /*
    |--------------------------------------------------------------------------
    | Watchlist Dashboard
    |--------------------------------------------------------------------------
    */

    public function index()
    {

        $business_id = request()->session()->get('user.business_id');

        $watchlists = LoanSanctionsWatchlist::where(
                'business_id',
                $business_id
            )
            ->with('amlKycProfile')
            ->latest()
            ->get();

        return view(
            'loan::sanctions_watchlist.index',
            compact('watchlists')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Create Watchlist Entry
    |--------------------------------------------------------------------------
    */

    public function create()
    {

        $business_id = request()->session()->get('user.business_id');

        $profiles = LoanAmlKycProfile::where(
                'business_id',
                $business_id
            )
            ->latest()
            ->get();

        return view(
            'loan::sanctions_watchlist.create',
            compact('profiles')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Store Watchlist Entry
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {

        $business_id = request()->session()->get('user.business_id');

        LoanSanctionsWatchlist::create([

            'business_id' => $business_id,

            'aml_kyc_profile_id' => $request->aml_kyc_profile_id,

            'watchlist_type' => $request->watchlist_type,

            'entity_name' => $request->entity_name,

            'country' => $request->country,

            'match_score' => $request->match_score,

            'risk_level' => $request->risk_level,

            'screening_status' => $request->screening_status,

            'remarks' => $request->remarks,

            'created_by' => Auth::id(),

            'updated_by' => Auth::id()

        ]);

        return redirect()
            ->route('loan.sanctions_watchlist.index')
            ->with(
                'success',
                'Watchlist Entry Created Successfully'
            );
    }

}