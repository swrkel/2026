<?php

namespace Modules\ExpensesNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ExpensesNew\Entities\Expense;
use Modules\ExpensesNew\Services\ApprovalService;
use Modules\ExpensesNew\Utils\BusinessScope;

class ApprovalController extends Controller
{
    public function index(Request $request)
    {
        $businessId = BusinessScope::businessId();
        $expenses = Expense::where('business_id', $businessId)
            ->whereIn('status', ['submitted', 'manager_approved', 'finance_approved'])
            ->latest('id')
            ->paginate(25);
        return view('expensesnew::approval.index', compact('expenses'));
    }

    public function approve(Request $request, $id, ApprovalService $service)
    {
        $expense = Expense::where('business_id', BusinessScope::businessId())->findOrFail($id);
        $service->approve($expense, $request->input('note'));
        return back()->with('status', __('expensesnew::lang.expense_approved'));
    }

    public function reject(Request $request, $id, ApprovalService $service)
    {
        $expense = Expense::where('business_id', BusinessScope::businessId())->findOrFail($id);
        $service->reject($expense, $request->input('note'));
        return back()->with('status', __('expensesnew::lang.expense_rejected'));
    }
}
