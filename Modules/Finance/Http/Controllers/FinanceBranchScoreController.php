<?php

namespace Modules\Finance\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Finance\Entities\FinanceBranchScore;
use Modules\Finance\Services\FinanceBranchScoringService;

class FinanceBranchScoreController extends Controller
{
    public function index()
    {
        $business_id = session('business.id');

        $scores = FinanceBranchScore::where(
            'business_id',
            $business_id
        )
        ->with(['location', 'createdBy'])
        ->orderBy('ranking_position')
        ->paginate(20);

        return view(
            'finance::branch_scores.index',
            compact('scores')
        );
    }

    public function generate()
    {
        FinanceBranchScoringService::generate();

        return redirect()
            ->route('finance.branch_scores.index')
            ->with('status', [
                'success' => 1,
                'msg' => 'Branch financial scores generated successfully'
            ]);
    }
}