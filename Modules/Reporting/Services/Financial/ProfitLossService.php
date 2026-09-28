<?php

namespace Modules\Reporting\Http\Controllers\Financial;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

use App\BusinessLocation;
use App\User;

use Modules\Reporting\Services\Financial\ProfitLossService;

class ProfitLossController extends Controller
{
    protected $profitLossService;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    */

    public function __construct(
        ProfitLossService $profitLossService
    ) {

        $this->profitLossService =
            $profitLossService;
    }

    /*
    |--------------------------------------------------------------------------
    | Enterprise Profit & Loss Dashboard
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $business_id =
            $request->session()
                ->get('user.business_id');

        $user_id =
            $request->session()
                ->get('user.id');

        /*
        |--------------------------------------------------------------------------
        | Logged User
        |--------------------------------------------------------------------------
        */

        $user = User::find($user_id);

        /*
        |--------------------------------------------------------------------------
        | Multi Branch Governance
        |--------------------------------------------------------------------------
        */

        $allowedLocations = [];

        if (
            !empty(
                $user->location_permissions
            )
        ) {

            $decodedLocations =
                json_decode(
                    $user->location_permissions,
                    true
                );

            if (
                is_array(
                    $decodedLocations
                )
            ) {

                $allowedLocations =
                    $decodedLocations;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Head Office Logic
        |--------------------------------------------------------------------------
        */

        $isHeadOfficeUser = false;

        if (
            empty(
                $allowedLocations
            )
        ) {

            $isHeadOfficeUser = true;
        }

        /*
        |--------------------------------------------------------------------------
        | Date Filters
        |--------------------------------------------------------------------------
        */

        $start_date =
            $request->start_date
            ?? now()
                ->startOfMonth()
                ->toDateString();

        $end_date =
            $request->end_date
            ?? now()
                ->endOfMonth()
                ->toDateString();

        /*
        |--------------------------------------------------------------------------
        | Branch Filter
        |--------------------------------------------------------------------------
        */

        $selected_location_id =
            $request->location_id;

        /*
        |--------------------------------------------------------------------------
        | Restrict Branch Access
        |--------------------------------------------------------------------------
        */

        if (
            !$isHeadOfficeUser
            &&
            !empty($selected_location_id)
        ) {

            if (
                !in_array(
                    $selected_location_id,
                    $allowedLocations
                )
            ) {

                abort(403);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Business Locations
        |--------------------------------------------------------------------------
        */

        $locations =
            BusinessLocation::where(
                    'business_id',
                    $business_id
                );

        if (
            !$isHeadOfficeUser
        ) {

            $locations->whereIn(
                'id',
                $allowedLocations
            );
        }

        $locations =
            $locations
                ->pluck(
                    'name',
                    'id'
                );

        /*
        |--------------------------------------------------------------------------
        | Consolidated vs Branch Reporting
        |--------------------------------------------------------------------------
        */

        $profitLossData =
            $this->profitLossService
                ->generate(

                    $business_id,

                    $start_date,

                    $end_date,

                    $selected_location_id
                );

        /*
        |--------------------------------------------------------------------------
        | Report Mode
        |--------------------------------------------------------------------------
        */

        $report_mode =
            empty($selected_location_id)
            ? 'Consolidated'
            : 'Branch';

        /*
        |--------------------------------------------------------------------------
        | View
        |--------------------------------------------------------------------------
        */

        return view(
            'reporting::financial.profit_loss',
            compact(

                'profitLossData',

                'locations',

                'selected_location_id',

                'start_date',

                'end_date',

                'report_mode',

                'allowedLocations',

                'isHeadOfficeUser'
            )
        );
    }
}