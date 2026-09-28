<?php

namespace Modules\Loan\Http\Controllers;

use Illuminate\Routing\Controller;

use Modules\Loan\Models\RecoveryOfficerProductivity;
use Modules\Loan\Services\RecoveryOfficerProductivityService;

class RecoveryOfficerProductivityController extends Controller
{
    public function index()
    {
        $business_id = session('business.id');

        $records = RecoveryOfficerProductivity::where(
            'business_id',
            $business_id
        )
        ->with(['officer', 'location'])
        ->orderBy('ranking_position')
        ->paginate(25);

        return view(
            'loan::recovery_officer_productivity.index',
            compact('records')
        );
    }

    public function generate()
    {
        $business_id = session('business.id');

        $service = new RecoveryOfficerProductivityService();

        $service->generate($business_id);

        return redirect()
            ->back()
            ->with('status', [
                'success' => 1,
                'msg' => 'Recovery officer productivity generated successfully.'
            ]);
    }
}