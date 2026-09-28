<?php

namespace Modules\Loan\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * Deprecated compatibility controller.
 *
 * The old Loan ERP settings page (categories / fees / penalties / governance)
 * has been retired. All settings now route to the clean standalone Loan Setup
 * page handled by LoanSetupController.
 */
class LoanSettingsController extends Controller
{
    public function index(Request $request)
    {
        return app(LoanSetupController::class)->index($request);
    }

    public function update(Request $request)
    {
        return app(LoanSetupController::class)->saveApplicationNo($request);
    }
}
