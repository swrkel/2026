<?php

namespace Modules\ExpensesNew\Http\Controllers\CommandCenter;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ExpensesNew\Services\CommandCenter\ExpenseApprovalWorkbenchService;

class ExpenseApprovalWorkbenchController extends Controller
{
    public function index(Request $request, ExpenseApprovalWorkbenchService $service)
    {
        return view('expensesnew::command-center.approval-workbench', $service->payload($request));
    }

    public function action(Request $request, ExpenseApprovalWorkbenchService $service)
    {
        $request->validate([
            'action' => 'required|in:approve,reject,return,delegate',
            'ids' => 'required|array',
            'comments' => 'nullable|string|max:2000',
        ]);
        return response()->json($service->execute($request));
    }
}
