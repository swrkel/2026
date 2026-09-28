<?php

namespace Modules\Finance\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

use Modules\Finance\Entities\ExecutiveEarlyWarning;
use Modules\Finance\Services\ExecutiveEarlyWarningService;

class ExecutiveEarlyWarningController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | INDEX
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $business_id = session('business.id');

        $warnings = ExecutiveEarlyWarning::where(
            'business_id',
            $business_id
        )
        ->latest()
        ->paginate(25);

        return view(
            'finance::executive_early_warnings.index',
            compact('warnings')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | GENERATE WARNINGS
    |--------------------------------------------------------------------------
    */

    public function generate()
    {
        $business_id = session('business.id');

        $service =
            new ExecutiveEarlyWarningService();

        $service->generate($business_id);

        return redirect()
            ->back()
            ->with(
                'status',
                [
                    'success' => true,
                    'msg' => 'Executive early warnings generated successfully.'
                ]
            );
    }
}