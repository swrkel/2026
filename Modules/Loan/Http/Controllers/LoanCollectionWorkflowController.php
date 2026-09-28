<?php

namespace Modules\Loan\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

use Modules\Loan\Models\LoanCollectionWorkflow;
use Modules\Loan\Services\LoanCollectionWorkflowService;

class LoanCollectionWorkflowController extends Controller
{
    public function index()
    {
        $business_id = session('business.id');

        $workflows = LoanCollectionWorkflow::where(
            'business_id',
            $business_id
        )
        ->with([
            'loan',
            'customer',
            'location',
            'assignedTo'
        ])
        ->latest()
        ->paginate(25);

        return view(
            'loan::collection_workflows.index',
            compact('workflows')
        );
    }

    public function generate()
    {
        $business_id = session('business.id');

        $service =
            new LoanCollectionWorkflowService();

        $service->generate($business_id);

        return redirect()
            ->back()
            ->with(
                'status',
                [
                    'success' => 1,
                    'msg' =>
                        'Collection workflows generated successfully.'
                ]
            );
    }
}