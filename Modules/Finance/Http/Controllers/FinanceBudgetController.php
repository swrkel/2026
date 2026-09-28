<?php

namespace Modules\Finance\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Finance\Entities\Account;
use Modules\Finance\Entities\BusinessLocation;
use Modules\Finance\Entities\FinanceBudget;
use Modules\Finance\Services\FinanceAuditService;
use Modules\Finance\Services\FinanceNotificationService;

class FinanceBudgetController extends Controller
{
    public function index(Request $request)
    {
        $business_id = session()->get('user.business_id');

        $locations = BusinessLocation::where('business_id', $business_id)
            ->pluck('name', 'location_id');

        $query = FinanceBudget::where('business_id', $business_id)
            ->with(['location', 'account', 'createdBy'])
            ->latest();

        if (!empty($request->location_id) && $request->location_id != 'all') {
            $query->where('location_id', $request->location_id);
        }

        if (!empty($request->budget_year)) {
            $query->where('budget_year', $request->budget_year);
        }

        if (!empty($request->budget_type)) {
            $query->where('budget_type', $request->budget_type);
        }

        if (!empty($request->status)) {
            $query->where('status', $request->status);
        }

        $budgets = $query->paginate(25);

        return view('finance::budgets.index')
            ->with(compact(
                'budgets',
                'locations'
            ));
    }

    public function create()
    {
        $business_id = session()->get('user.business_id');

        $locations = BusinessLocation::where('business_id', $business_id)
            ->pluck('name', 'location_id');

        $accounts = Account::where('business_id', $business_id)
            ->where('is_closed', 0)
            ->orderBy('name')
            ->pluck('name', 'id');

        return view('finance::budgets.create')
            ->with(compact(
                'locations',
                'accounts'
            ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'budget_name' => 'required|string|max:191',
            'budget_year' => 'required|string|max:20',
            'budget_type' => 'required|string|max:50',
            'allocated_amount' => 'required|numeric|min:0',
        ]);

        $remaining_amount =
            $request->allocated_amount -
            ($request->utilized_amount ?? 0);

        $budget = FinanceBudget::create([
            'business_id' => session()->get('user.business_id'),
            'location_id' => $request->location_id,
            'budget_name' => $request->budget_name,
            'budget_year' => $request->budget_year,
            'budget_month' => $request->budget_month,
            'budget_type' => $request->budget_type,
            'department' => $request->department,
            'account_id' => $request->account_id,
            'allocated_amount' => $request->allocated_amount,
            'utilized_amount' => $request->utilized_amount ?? 0,
            'remaining_amount' => $remaining_amount,
            'alert_threshold' => $request->alert_threshold ?? 80,
            'status' => $request->status ?? 'draft',
            'created_by' => auth()->id(),
        ]);

        FinanceAuditService::log(
            'Finance Budget',
            'Created',
            'Budget created: ' . $budget->budget_name,
            'finance_budgets',
            $budget->id,
            null,
            $budget->toArray(),
            $budget->location_id
        );

        FinanceNotificationService::create(
            'Finance Budget',
            'New Budget Created',
            'Budget created: ' . $budget->budget_name,
            null,
            'medium',
            'finance_budgets',
            $budget->id,
            $budget->location_id
        );

        return redirect()
            ->route('finance.budgets.index')
            ->with('status', [
                'success' => 1,
                'msg' => 'Budget created successfully'
            ]);
    }
}