<?php

namespace Modules\ExpensesNew\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\ExpensesNew\Entities\Expense;
use Modules\ExpensesNew\Utils\BusinessScope;

class DashboardController extends Controller
{
    public function index()
    {
        $businessId = BusinessScope::businessId();
        $cards = [
            'today' => Expense::where('business_id',$businessId)->whereDate('expense_date', today())->sum('total_amount'),
            'month' => Expense::where('business_id',$businessId)->whereBetween('expense_date',[now()->startOfMonth(), now()->endOfMonth()])->sum('total_amount'),
            'due' => Expense::where('business_id',$businessId)->sum('due_amount'),
            'count' => Expense::where('business_id',$businessId)->count(),
        ];
        return view('expensesnew::dashboard.index', compact('cards'));
    }
}
