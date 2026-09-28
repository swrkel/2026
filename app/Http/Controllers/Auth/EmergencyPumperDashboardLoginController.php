<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;

class EmergencyPumperDashboardLoginController extends Controller
{
    /**
     * Emergency public entry page for Pumper Dashboard login.
     *
     * This does not authenticate anyone. It only displays the existing
     * pumper passcode login form for the selected business, using the same
     * Auth\PumpOperatorLoginController@login POST endpoint already used by
     * the system.
     */
    public function show(Request $request, $business_id = null)
    {
        $businessId = $business_id ?: $request->input('business_id');
        $companyNumber = $request->input('company_number') ?: $request->input('cc');

        $businessQuery = DB::table('business');

        if (!empty($businessId)) {
            $businessQuery->where('id', $businessId);
        } elseif (!empty($companyNumber)) {
            $businessQuery->where(function ($q) use ($companyNumber) {
                $q->where('company_number', $companyNumber)
                  ->orWhere('ref_no', $companyNumber)
                  ->orWhere('name', $companyNumber);
            });
        }

        $business = $businessQuery->first();

        if (empty($business)) {
            abort(404, 'Business not found for Pumper Dashboard login.');
        }

        $cc = $business->company_number ?? $business->ref_no ?? $business->id;

        $settings = (object) [
            'background_showing_type' => null,
            'uploadFileLLogo' => null,
        ];

        if (class_exists('App\\System')) {
            try {
                $settings->background_showing_type = \App\System::getProperty('background_showing_type');
                $settings->uploadFileLLogo = \App\System::getProperty('uploadFileLLogo');
            } catch (\Throwable $e) {
                // Keep default settings. Login must not fail because of theme settings.
            }
        }

        if (View::exists('petropd::pd_operators.login')) {
            return view('petropd::pd_operators.login')->with(compact('business', 'cc', 'settings'));
        }

        return view('petro::pump_operators.login')->with(compact('business', 'cc', 'settings'));
    }
}
