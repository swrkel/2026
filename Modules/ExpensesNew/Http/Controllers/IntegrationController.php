<?php

namespace Modules\ExpensesNew\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\ExpensesNew\Services\Integration\ExpenseIntegrationBridgeService;

class IntegrationController extends Controller
{
    public function index()
    {
        return view('expensesnew::integration.index');
    }
    public function postCost(Request $request, ExpenseIntegrationBridgeService $bridge)
    {
        $validated = $request->validate([
            'business_id' => 'nullable|integer',
            'business_location_id' => 'nullable|integer',
            'source_module' => 'required|string|max:100',
            'source_reference' => 'nullable|string|max:191',
            'amount' => 'required|numeric',
            'currency' => 'nullable|string|max:10',
        ]);
        return response()->json($bridge->postCost($validated));
    }
}
