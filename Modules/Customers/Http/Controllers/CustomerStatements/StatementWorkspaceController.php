<?php

namespace Modules\Customers\Http\Controllers\CustomerStatements;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Customers\Services\CustomerStatements\StatementWorkspaceService;

class StatementWorkspaceController extends Controller
{
    private StatementWorkspaceService $workspace;

    public function __construct(StatementWorkspaceService $workspace)
    {
        $this->workspace = $workspace;
    }

    public function index(Request $request)
    {
        $businessId = (int) ($request->session()->get('user.business_id')
            ?: $request->session()->get('business.id'));

        abort_if($businessId <= 0, 403, 'Business context is missing.');

        $filters = $this->workspace->filters($businessId);

        return view('customers::customer_statements_v2.index', $filters);
    }

    public function nextAllowedDate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'customer_id' => ['required', 'integer'],
        ]);

        $businessId = (int) ($request->session()->get('user.business_id')
            ?: $request->session()->get('business.id'));

        return response()->json([
            'success' => true,
            'minimum_start_date' => $this->workspace->latestAllowedStartDate(
                $businessId,
                (int) $data['customer_id']
            ),
        ]);
    }
}
