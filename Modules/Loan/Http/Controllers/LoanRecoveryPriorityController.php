<?php

namespace Modules\Loan\Http\Controllers;

use Illuminate\Routing\Controller;

use Modules\Loan\Models\LoanRecoveryPriorityScore;
use Modules\Loan\Services\LoanRecoveryPriorityService;

class LoanRecoveryPriorityController extends Controller
{
    public function index()
    {
        $business_id = session('business.id');

        $scores = LoanRecoveryPriorityScore::where(
            'business_id',
            $business_id
        )
        ->with(['loan', 'customer', 'location'])
        ->orderByDesc('recovery_priority_score')
        ->paginate(25);

        return view(
            'loan::recovery.priority_scores',
            compact('scores')
        );
    }

    public function generate()
    {
        $business_id = session('business.id');

        $service = new LoanRecoveryPriorityService();

        $service->generate($business_id);

        return redirect()
            ->back()
            ->with('status', [
                'success' => 1,
                'msg' => 'Recovery priority scores generated successfully.'
            ]);
    }
}